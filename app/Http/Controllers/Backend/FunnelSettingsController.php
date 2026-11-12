<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\WhatsappAccount;
use App\Services\LeadMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Embudo de Venta > Configuración: con qué WhatsApp se atiende y desde qué correo se contesta.
 *
 *  - WhatsApp: cuenta preferida (Setting funnel.whatsapp_account_id). La usan la bandeja de leads y el
 *    embudo cuando el lead aún no tiene conversación, y es la primera en los chats nuevos.
 *  - Correo: el buzón de respuestas (mismas claves mail.inbox.* que Integraciones > Correo > Buzón de la
 *    bandeja de leads). Sin buzón, las respuestas salen del remitente general (no-reply@).
 */
class FunnelSettingsController extends Controller
{
    public function edit(LeadMailService $mail)
    {
        $mailbox = $mail->inboxMailbox();
        $sender = $mail->sender();
        $domain = Str::after((string) config('mail.from.address'), '@');

        return view('admin.whatsapp-funnel.settings', [
            'accounts'        => WhatsappAccount::active()->orderBy('name')->get(),
            'selectedAccount' => (int) Setting::get('funnel.whatsapp_account_id'),
            'sender'          => $sender,
            'usesDefault'     => $sender['uses_default'],
            'values'          => [
                'from_address' => (string) Setting::get('mail.inbox.from_address'),
                'from_name'    => (string) Setting::get('mail.inbox.from_name'),
                'username'     => (string) Setting::get('mail.inbox.username'),
            ],
            'hasPassword'     => filled(Setting::get('mail.inbox.password')),
            'mailboxReady'    => $mailbox !== null,
            'suggestion'      => $domain !== '' ? 'administracion@' . $domain : null,
            'smtpProblem'     => $mail->smtpProblem(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'whatsapp_account_id' => ['nullable', 'integer', 'exists:whatsapp_accounts,id'],
            'mail_from_address'   => ['nullable', 'email', 'max:255'],
            'mail_from_name'      => ['nullable', 'string', 'max:255'],
            'mail_username'       => ['nullable', 'string', 'max:190'],
            'mail_password'       => ['nullable', 'string', 'max:255'],
            'clear_mailbox'       => ['nullable', 'boolean'],
        ]);

        // WhatsApp: solo una cuenta activa puede quedar como preferida.
        $accountId = $data['whatsapp_account_id'] ?? null;
        if ($accountId && ! WhatsappAccount::active()->whereKey($accountId)->exists()) {
            throw ValidationException::withMessages(['whatsapp_account_id' => 'Esa cuenta de WhatsApp está inactiva.']);
        }
        $this->save('funnel.whatsapp_account_id', $accountId ? (string) $accountId : null);

        // Correo.
        if ($request->boolean('clear_mailbox')) {
            foreach (['from_address', 'from_name', 'username', 'password'] as $k) {
                $this->save('mail.inbox.' . $k, null);
            }

            return redirect()->route('admin.whatsapp-funnel.settings')
                ->with('success', 'Listo: las respuestas volverán a salir del remitente general del sistema.');
        }

        $address = trim((string) ($data['mail_from_address'] ?? ''));
        if ($address !== '') {
            $username = trim((string) ($data['mail_username'] ?? '')) ?: $address;
            $hasPassword = filled(Setting::get('mail.inbox.password'));
            if (! $hasPassword && ! filled($data['mail_password'] ?? null)) {
                throw ValidationException::withMessages(['mail_password' => 'Captura la contraseña del buzón (la que se usa para entrar a ese correo).']);
            }

            $this->save('mail.inbox.from_address', $address);
            $this->save('mail.inbox.from_name', $data['mail_from_name'] ?? null);
            $this->save('mail.inbox.username', $username);
            if (filled($data['mail_password'] ?? null)) {
                $this->save('mail.inbox.password', Crypt::encryptString($data['mail_password']));
            }
        }

        return redirect()->route('admin.whatsapp-funnel.settings')->with('success', 'Configuración del embudo guardada.');
    }

    /** Manda un correo de prueba con el remitente efectivo, para confirmar que sale de administracion@ y no de no-reply@. */
    public function testMail(Request $request, LeadMailService $mail)
    {
        $data = $request->validate(['test_to' => ['required', 'email', 'max:190']]);

        if ($problem = $mail->smtpProblem()) {
            return back()->with('error', $problem);
        }

        $sender = $mail->sender();

        try {
            $mailer = $sender['mailbox'] ? $mail->inboxMailer($sender['mailbox']) : Mail::mailer();
            $mailer->raw(
                'Este es un correo de prueba del Embudo de Venta. Si lo recibes, las respuestas a tus clientes saldrán desde ' . $sender['address'] . '.',
                function ($m) use ($data, $sender) {
                    $m->to($data['test_to'])->subject('Prueba de correo · Embudo de Venta')->from($sender['address'], $sender['name']);
                }
            );
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'No se pudo enviar la prueba: ' . Str::limit(trim($e->getMessage()), 200));
        }

        return back()->with('success', 'Prueba enviada a ' . $data['test_to'] . ' desde ' . $sender['address'] . '.');
    }

    private function save(string $key, ?string $value): void
    {
        Setting::updateOrCreate(['key' => $key], [
            'value'              => $value,
            'type'               => 'string',
            'group_name'         => 'integraciones',
            'is_public'          => false,
            'updated_by_user_id' => auth()->id(),
        ]);
    }
}
