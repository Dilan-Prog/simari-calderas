<?php

namespace App\Services;

use App\Mail\LeadReplyMail;
use App\Models\Lead;
use App\Models\LeadInboundEmail;
use App\Models\LeadOutboundEmail;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Responder por correo a un lead desde la bandeja (SMTP de Integraciones; si hay "Buzón de la bandeja
 * de leads" configurado, sale con ese remitente —administracion@— y con su usuario/contraseña).
 *
 * Contrato ($data de send): to, cc (array|string, opcional), subject, body (texto plano),
 * quote_ids (int[], cotizaciones del cliente del lead), files (UploadedFile[]), close_task (bool).
 *
 * Nota: el SMTP NO deja copia en la carpeta Enviados del buzón; el flujo de n8n que registra esa
 * carpeta, si vuelve a ver el mismo correo, lo ignora porque el Message-ID generado aquí ya existe.
 */
class LeadMailService
{
    public const MAX_CC = 5;

    public function __construct(private LeadService $leads)
    {
    }

    // ── Estado / contexto ───────────────────────────────────────────────

    /**
     * Buzón propio de la bandeja (Integraciones > Buzón de la bandeja de leads), o null si no aplica.
     * Solo aplica con el transporte SMTP real (mail.default = smtp) y con usuario, contraseña y
     * remitente capturados; una contraseña que no se puede descifrar cuenta como "no configurado".
     * Host, puerto y cifrado son los del SMTP general.
     *
     * @return array{address:string,name:?string,username:string,password:string}|null
     */
    public function inboxMailbox(): ?array
    {
        if (config('mail.default') !== 'smtp') {
            return null;
        }

        $address = trim((string) Setting::get('mail.inbox.from_address'));
        $username = trim((string) Setting::get('mail.inbox.username'));
        $stored = Setting::get('mail.inbox.password');
        if ($address === '' || $username === '' || blank($stored) || ! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        try {
            $password = Crypt::decryptString((string) $stored);
        } catch (DecryptException) {
            return null;
        }
        if ($password === '') {
            return null;
        }

        $name = trim((string) Setting::get('mail.inbox.from_name'));

        return [
            'address'  => $address,
            'name'     => $name !== '' ? $name : (config('mail.from.name') ? (string) config('mail.from.name') : null),
            'username' => $username,
            'password' => $password,
        ];
    }

    /**
     * Remitente efectivo de las respuestas: el buzón de la bandeja si está configurado; si no, el
     * remitente general del sistema.
     *
     * @return array{address:string,name:?string,uses_default:bool,mailbox:?array}
     */
    public function sender(): array
    {
        $mailbox = $this->inboxMailbox();
        if ($mailbox) {
            return ['address' => $mailbox['address'], 'name' => $mailbox['name'], 'uses_default' => false, 'mailbox' => $mailbox];
        }

        $name = config('mail.from.name');

        return [
            'address'      => (string) config('mail.from.address'),
            'name'         => $name ? (string) $name : null,
            'uses_default' => true,
            'mailbox'      => null,
        ];
    }

    /** Motivo por el que no se puede enviar por SMTP, o null si todo está listo. */
    public function smtpProblem(): ?string
    {
        if (blank($this->sender()['address'])) {
            return 'No hay un remitente configurado. Captúralo en Integraciones > Correo.';
        }

        // Solo se exige host cuando el transporte real es SMTP (en pruebas/entornos con log o array no aplica).
        if (config('mail.default') !== 'smtp') {
            return null;
        }

        $saved = Setting::get('mail.host');
        $host = strtolower(trim((string) config('mail.mailers.smtp.host')));
        if (blank($saved) && ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'], true))) {
            return 'El correo saliente (SMTP) no está configurado. Configúralo en Integraciones > Correo.';
        }

        return null;
    }

    public function signature(): string
    {
        return trim((string) Setting::get('mail.signature', ''));
    }

    /** Último correo entrante del lead (define el hilo al que se responde). */
    public function latestInbound(Lead $lead): ?LeadInboundEmail
    {
        return LeadInboundEmail::where('lead_id', $lead->id)->latest('id')->first();
    }

    /**
     * Tarea a la que se liga la respuesta: la del correo entrante, si sigue abierta; si no, la última
     * tarea abierta del lead. Con $allowClosed, si no hay ninguna abierta, la del correo entrante aunque
     * ya esté cerrada (para dejar el movimiento en su historial).
     */
    public function replyTask(Lead $lead, bool $allowClosed = false): ?Task
    {
        $inbound = $this->latestInbound($lead);
        $inboundTask = $inbound?->task_id ? Task::find($inbound->task_id) : null;

        if ($inboundTask && $inboundTask->status !== 'closed') {
            return $inboundTask;
        }

        $open = $lead->tasks()->where('status', '!=', 'closed')->latest('id')->first();
        if ($open) {
            return $open;
        }

        return $allowClosed ? $inboundTask : null;
    }

    // ── Envío ───────────────────────────────────────────────────────────

    /**
     * Envía el correo de forma síncrona y, solo si salió, lo registra (correo saliente, evento de la
     * tarea, actividad del lead y estado).
     *
     * @throws RuntimeException con mensaje en español si no se puede enviar
     */
    public function send(Lead $lead, array $data, ?User $user = null): LeadOutboundEmail
    {
        if ($problem = $this->smtpProblem()) {
            throw new RuntimeException($problem);
        }

        $to = strtolower(trim((string) ($data['to'] ?? '')));
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El destinatario no es un correo válido.');
        }
        $cc = $this->parseCc($data['cc'] ?? [], $to);

        $subject = trim((string) ($data['subject'] ?? ''));
        $body = trim((string) ($data['body'] ?? ''));
        if ($subject === '' || $body === '') {
            throw new RuntimeException('El asunto y el mensaje son obligatorios.');
        }

        $sender = $this->sender();
        $fromAddress = $sender['address'];
        $fromName = $sender['name'];
        $domain = Str::after($fromAddress, '@') ?: 'localhost';
        $messageId = '<' . Str::uuid() . '@' . $domain . '>';
        $inbound = $this->latestInbound($lead);
        $inReplyTo = $inbound?->message_id ?: null;

        $files = array_merge($this->quoteFiles($lead, (array) ($data['quote_ids'] ?? [])), $this->uploadedFiles((array) ($data['files'] ?? [])));

        $mail = new LeadReplyMail(
            $fromAddress,
            $fromName ? (string) $fromName : null,
            $subject,
            $body,
            $this->signature(),
            $messageId,
            $inReplyTo,
            $files,
        );

        try {
            $pending = $sender['mailbox'] ? $this->inboxMailer($sender['mailbox'])->to($to) : Mail::to($to);
            if ($cc) {
                $pending->cc($cc);
            }
            $pending->send($mail);
        } catch (\Throwable $e) {
            report($e);

            throw new RuntimeException('No se pudo enviar el correo: ' . Str::limit(trim((string) $e->getMessage()), 200), 0, $e);
        }

        try {
            return DB::transaction(fn () => $this->record($lead, $user, [
                'message_id'  => $messageId,
                'in_reply_to' => $inReplyTo,
                'from'        => strtolower($fromAddress),
                'to'          => $to,
                'recipients'  => array_merge([$to], $cc),
                'subject'     => $subject,
                'body'        => $body,
                'attachments' => array_column($files, 'name'),
                'close_task'  => (bool) ($data['close_task'] ?? false),
            ]));
        } catch (\Throwable $e) {
            report($e);

            throw new RuntimeException('El correo se envió, pero no se pudo registrar en el historial: ' . Str::limit(trim((string) $e->getMessage()), 200), 0, $e);
        }
    }

    private function record(Lead $lead, ?User $user, array $d): LeadOutboundEmail
    {
        $now = now();
        $task = $this->replyTask($lead, true);

        $email = LeadOutboundEmail::create([
            'message_id'      => $d['message_id'],
            'lead_id'         => $lead->id,
            'task_id'         => $task?->id,
            'from_email'      => $d['from'],
            'to_email'        => $d['to'],
            'recipients'      => $d['recipients'],
            'subject'         => mb_substr($d['subject'], 0, 255),
            'body'            => mb_substr($d['body'], 0, 100000),
            'attachments'     => $d['attachments'] ?: null,
            'sent_at'         => $now,
            'source'          => LeadOutboundEmail::SOURCE_PLATFORM,
            'sent_by_user_id' => $user?->id,
            'in_reply_to'     => $d['in_reply_to'],
        ]);

        if ($task) {
            $task->logEvent('sent_email', 'Correo enviado a ' . $d['to'] . ': ' . mb_substr($d['subject'], 0, 120), $user?->id, $email->id);

            // Mismo camino que TaskController::update al marcar la tarea como cerrada.
            if ($d['close_task'] && $task->status !== 'closed') {
                $task->update(['status' => 'closed', 'closed_at' => $task->closed_at ?: $now]);
                $task->logEvent('status', 'Estatus actualizado a «' . Task::statusLabel('closed') . '»', $user?->id);
            }
        }

        $lead->first_contact_at ??= $now;
        $lead->last_activity_at = $now;
        $lead->save();
        LeadService::clearReplyPending($lead);
        if ($lead->status === 'nuevo') {
            $this->leads->changeStatus($lead, 'contactado');
        }

        return $email;
    }

    // ── Auxiliares ──────────────────────────────────────────────────────

    /**
     * Mailer en tiempo de ejecución con las credenciales del buzón de la bandeja y el servidor, puerto
     * y cifrado del SMTP general (ya resueltos por MailSettingsServiceProvider en mail.mailers.smtp).
     * Se recrea en cada envío para no reutilizar una conexión con credenciales viejas.
     * `mail.lead_inbox_transport` solo existe para poder sustituir el transporte (pruebas).
     */
    private function inboxMailer(array $mailbox): MailerContract
    {
        $smtp = (array) config('mail.mailers.smtp', []);

        config(['mail.mailers.lead_inbox' => [
            'transport'  => (string) config('mail.lead_inbox_transport', 'smtp'),
            'host'       => $smtp['host'] ?? null,
            'port'       => $smtp['port'] ?? null,
            'scheme'     => $smtp['scheme'] ?? null,
            'encryption' => $smtp['encryption'] ?? null,
            'username'   => $mailbox['username'],
            'password'   => $mailbox['password'],
            'timeout'    => 20,
        ]]);

        Mail::purge('lead_inbox');

        return Mail::mailer('lead_inbox');
    }

    /** @return array<int,string> correos de copia válidos, únicos y distintos de $to */
    public function parseCc(array|string|null $cc, string $to = ''): array
    {
        $items = is_array($cc) ? $cc : preg_split('/[,;\s]+/', (string) $cc);
        $out = [];
        foreach ($items as $item) {
            $item = strtolower(trim((string) $item));
            if ($item === '') {
                continue;
            }
            if (! filter_var($item, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('El correo de copia «' . $item . '» no es válido.');
            }
            if ($item !== $to && ! in_array($item, $out, true)) {
                $out[] = $item;
            }
        }
        if (count($out) > self::MAX_CC) {
            throw new RuntimeException('Máximo ' . self::MAX_CC . ' correos en copia.');
        }

        return $out;
    }

    /**
     * PDF de cada cotización seleccionada (misma salida que el portal: sin notas internas).
     *
     * @return array<int,array{data:string,name:string,mime:string}>
     */
    private function quoteFiles(Lead $lead, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (! $ids) {
            return [];
        }

        $quotes = $lead->customer_id
            ? Quote::where('customer_id', $lead->customer_id)->whereIn('id', $ids)->get()
            : collect();
        if ($quotes->count() !== count($ids)) {
            throw new RuntimeException('Alguna cotización seleccionada no pertenece al cliente de este lead.');
        }

        $files = [];
        foreach ($quotes as $quote) {
            $quote->load('items');
            $quote->notes = null; // las notas internas nunca salen (solo en memoria, no se guarda)
            $files[] = [
                'data' => Pdf::loadView('admin.quotes.pdf', ['quote' => $quote])->setPaper('a4', 'portrait')->output(),
                'name' => $quote->quote_number . '.pdf',
                'mime' => 'application/pdf',
            ];
        }

        return $files;
    }

    /**
     * @param  array<int,mixed>  $files
     * @return array<int,array{data:string,name:string,mime:string}>
     */
    private function uploadedFiles(array $files): array
    {
        $out = [];
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }
            $out[] = [
                'data' => (string) file_get_contents($file->getRealPath()),
                'name' => $file->getClientOriginalName() ?: 'adjunto',
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
            ];
        }

        return $out;
    }
}
