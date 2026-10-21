<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\WhatsappAccount;
use App\Models\WhatsappMessage;
use App\Services\WhatsappService;
use App\Support\WhatsappWebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Http;

/**
 * Pestaña "Probar conexión" de Integraciones > WhatsApp API: comprueba una cuenta de
 * Meta Cloud API contra la Graph API real, manda un mensaje de prueba y muestra si el
 * webhook está llegando. No crea conversaciones ni mensajes en el CRM (solo prueba);
 * lo que el destinatario conteste sí entra por el webhook normal.
 */
class WhatsappConnectionTestController extends Controller
{
    private function graph(string $path): string
    {
        return WhatsappService::GRAPH_API_BASE . '/' . WhatsappService::GRAPH_API_VERSION . '/' . ltrim($path, '/');
    }

    /** Traduce el error de Meta a un mensaje con el paso a seguir. */
    private function explain(HttpResponse $response): array
    {
        $error = $response->json('error') ?? [];
        $code = (int) ($error['code'] ?? 0);
        $sub = (int) ($error['error_subcode'] ?? 0);
        $message = (string) ($error['message'] ?? ('HTTP ' . $response->status()));

        $hint = match (true) {
            $code === 190 => 'El Access Token no es válido o ya expiró. En una app de prueba el token temporal dura 24 horas: genera uno nuevo en Meta for Developers > WhatsApp > API Setup y reemplázalo en la cuenta (Credenciales > Editar).',
            $code === 100 => 'Meta no reconoce ese ID. Revisa que el Phone Number ID sea el de WhatsApp > API Setup (no el número de teléfono) y que el token pertenezca a la misma app.',
            in_array($code, [10, 200, 3], true) => 'El token no tiene permiso para esta cuenta. Usa un token de la misma app con los permisos whatsapp_business_messaging y whatsapp_business_management.',
            $code === 131030 => 'Ese número destinatario no está en la lista de permitidos. Con el número de prueba solo puedes escribir a números agregados y verificados en WhatsApp > API Setup (máximo 5).',
            in_array($code, [131047, 131051], true) => 'Fuera de la ventana de 24 horas: solo puedes mandar una plantilla (usa hello_world para la prueba) hasta que el contacto te escriba.',
            in_array($code, [132000, 132001], true) => 'La plantilla no existe o no está aprobada en ese idioma. Para la prueba usa hello_world en en_US.',
            $code === 133010 => 'El número emisor no está registrado en WhatsApp Cloud API.',
            $code === 131026 => 'No se pudo entregar: el número no tiene WhatsApp o no aceptó los términos.',
            $code === 4 || $code === 80007 => 'Meta limitó las llamadas por un momento. Espera unos minutos e intenta de nuevo.',
            default => null,
        };

        return [
            'code'    => $code ?: null,
            'subcode' => $sub ?: null,
            'message' => $message,
            'hint'    => $hint,
        ];
    }

    private function check(string $key, string $label, string $status, string $detail = ''): array
    {
        return ['key' => $key, 'label' => $label, 'status' => $status, 'detail' => $detail];
    }

    /**
     * Revisa la configuración guardada y la conexión real con Meta (número, token y
     * plantillas), más el estado del webhook. status: ok | warn | fail.
     */
    public function connection(WhatsappAccount $whatsappAccount): JsonResponse
    {
        $a = $whatsappAccount;
        $checks = [];
        $phone = null;

        $checks[] = $this->check('phone_number_id', 'Phone Number ID capturado', filled($a->phone_number_id) ? 'ok' : 'fail',
            filled($a->phone_number_id) ? $a->phone_number_id : 'Falta: cópialo de Meta for Developers > WhatsApp > API Setup.');
        $checks[] = $this->check('access_token', 'Access Token capturado', filled($a->access_token) ? 'ok' : 'fail',
            filled($a->access_token) ? 'Guardado (cifrado).' : 'Falta el Access Token.');
        $checks[] = $this->check('verify_token', 'Token de verificación del webhook', filled($a->webhook_verify_token) ? 'ok' : 'warn',
            filled($a->webhook_verify_token) ? 'Capturado.' : 'Falta: es el texto que tú inventas y pegas igual en Meta al configurar el webhook.');
        $checks[] = $this->check('app_secret', 'App Secret (firma del webhook)', filled($a->app_secret) ? 'ok' : 'warn',
            filled($a->app_secret) ? 'Capturado: se valida la firma de cada mensaje entrante.' : 'Sin App Secret no se valida la firma de los mensajes entrantes. Cópialo de Meta for Developers > Configuración de la app > Básica.');
        $checks[] = $this->check('waba_id', 'WhatsApp Business Account ID', filled($a->whatsapp_business_account_id) ? 'ok' : 'warn',
            filled($a->whatsapp_business_account_id) ? $a->whatsapp_business_account_id : 'Falta: se necesita para leer tus plantillas.');
        $checks[] = $this->check('is_active', 'Cuenta activa', $a->is_active ? 'ok' : 'warn', $a->is_active ? '' : 'La cuenta está marcada como inactiva.');

        if (filled($a->phone_number_id) && filled($a->access_token)) {
            try {
                $res = Http::withToken($a->access_token)->timeout(10)->get($this->graph($a->phone_number_id), [
                    'fields' => 'display_phone_number,verified_name,quality_rating,code_verification_status',
                ]);

                if ($res->successful()) {
                    $phone = $res->json();
                    $detail = trim(($phone['display_phone_number'] ?? '') . ' · ' . ($phone['verified_name'] ?? '') .
                        (isset($phone['quality_rating']) ? ' · calidad ' . $phone['quality_rating'] : ''), ' ·');
                    $checks[] = $this->check('meta', 'Conexión con Meta (número y token)', 'ok', $detail ?: 'Respondió correctamente.');
                } else {
                    $err = $this->explain($res);
                    $checks[] = $this->check('meta', 'Conexión con Meta (número y token)', 'fail',
                        trim(($err['hint'] ?? '') . ' [Meta: ' . $err['message'] . ']'));
                }
            } catch (\Throwable $e) {
                $checks[] = $this->check('meta', 'Conexión con Meta (número y token)', 'fail',
                    'No se pudo contactar a graph.facebook.com desde el servidor: ' . $e->getMessage());
            }
        } else {
            $checks[] = $this->check('meta', 'Conexión con Meta (número y token)', 'fail', 'Primero captura el Phone Number ID y el Access Token.');
        }

        // Plantillas (necesita el WABA ID y que el token sea válido).
        $metaOk = collect($checks)->firstWhere('key', 'meta')['status'] ?? 'fail';
        if (filled($a->whatsapp_business_account_id) && $metaOk === 'ok') {
            try {
                $res = Http::withToken($a->access_token)->timeout(10)->get($this->graph($a->whatsapp_business_account_id . '/message_templates'), [
                    'fields' => 'name,status,language',
                    'limit'  => 100,
                ]);

                if ($res->successful()) {
                    $templates = collect($res->json('data') ?? []);
                    $hello = $templates->firstWhere('name', 'hello_world');
                    $checks[] = $this->check('templates', 'Plantillas de la cuenta', 'ok',
                        $templates->count() . ' plantilla(s)' . ($hello ? ' · "hello_world" disponible para la prueba' : ' · "hello_world" no aparece (créala o usa otra aprobada)'));
                } else {
                    $err = $this->explain($res);
                    $checks[] = $this->check('templates', 'Plantillas de la cuenta', 'warn',
                        trim(($err['hint'] ?? 'No se pudieron leer las plantillas.') . ' [Meta: ' . $err['message'] . ']'));
                }
            } catch (\Throwable $e) {
                $checks[] = $this->check('templates', 'Plantillas de la cuenta', 'warn', 'No se pudieron leer: ' . $e->getMessage());
            }
        }

        $checks = array_merge($checks, $this->webhookChecks($a));

        $critical = collect($checks)->whereIn('key', ['phone_number_id', 'access_token', 'meta']);

        return response()->json([
            'ok'     => $critical->every(fn ($c) => $c['status'] === 'ok'),
            'phone'  => $phone,
            'checks' => $checks,
        ]);
    }

    /** Checks del webhook a partir de la bitácora (se reutiliza en connection() y webhookStatus()). */
    private function webhookChecks(WhatsappAccount $a): array
    {
        $checks = [];

        $verified = WhatsappWebhookLog::verifiedAt();
        $failed = WhatsappWebhookLog::verifyFailedAt();
        $checks[] = $this->check('webhook_verified', 'Meta verificó la URL del webhook', $verified ? 'ok' : 'warn',
            $verified
                ? 'Verificado el ' . \Illuminate\Support\Carbon::parse($verified)->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s')
                : ($failed
                    ? 'Meta intentó verificar pero el token no coincidió con el de ninguna cuenta (revisa el "Token de verificación del webhook").'
                    : 'Aún no. En Meta > WhatsApp > Configuration pega la Callback URL y el token de verificación, y pulsa "Verify and save" (la URL debe ser pública con HTTPS).'));

        $rejected = WhatsappWebhookLog::lastRejected();
        if ($rejected) {
            $checks[] = $this->check('webhook_rejected', 'Llamadas rechazadas', 'fail',
                \Illuminate\Support\Carbon::parse($rejected['at'])->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s') . ' · ' . $rejected['reason']);
        }

        $event = WhatsappWebhookLog::lastEvent($a->phone_number_id);
        $checks[] = $this->check('webhook_events', 'Llegan eventos de este número', $event ? 'ok' : 'warn',
            $event
                ? 'Último: ' . \Illuminate\Support\Carbon::parse($event['at'])->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s')
                    . ' · ' . $event['messages'] . ' mensaje(s), ' . $event['statuses'] . ' estado(s)'
                : 'Aún no. Suscribe el campo "messages" en Meta > WhatsApp > Configuration y escribe un mensaje al número de prueba desde un teléfono verificado.');

        return $checks;
    }

    /**
     * Mensaje de prueba directo a Meta (sin crear conversación en el CRM). Con plantilla
     * (hello_world en en_US funciona desde el primer momento con el número de prueba) o
     * con texto libre (solo si el contacto te escribió en las últimas 24 horas).
     */
    public function send(WhatsappAccount $whatsappAccount, Request $request): JsonResponse
    {
        $data = $request->validate([
            'to'       => ['required', 'string', 'max:30'],
            'mode'     => ['nullable', 'in:template,text'],
            'template' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_]+$/'],
            'language' => ['nullable', 'in:en_US,es_MX,es,es_ES,en'],
            'text'     => ['required_if:mode,text', 'nullable', 'string', 'max:1000'],
        ]);

        $to = preg_replace('/\D+/', '', $data['to']);
        if (strlen($to) < 8 || strlen($to) > 15) {
            return response()->json(['success' => false, 'message' => 'El número destinatario debe llevar código de país y de 8 a 15 dígitos (ej. 5214491234567).'], 422);
        }

        if (blank($whatsappAccount->phone_number_id) || blank($whatsappAccount->access_token)) {
            return response()->json(['success' => false, 'message' => 'Faltan el Phone Number ID o el Access Token de la cuenta.'], 422);
        }

        $mode = $data['mode'] ?? 'template';
        $payload = ['messaging_product' => 'whatsapp', 'to' => $to];

        if ($mode === 'text') {
            $payload += ['type' => 'text', 'text' => ['preview_url' => false, 'body' => $data['text']]];
        } else {
            $payload += [
                'type'     => 'template',
                'template' => [
                    'name'     => $data['template'] ?? 'hello_world',
                    'language' => ['code' => $data['language'] ?? 'en_US'],
                ],
            ];
        }

        try {
            $res = Http::withToken($whatsappAccount->access_token)->timeout(15)
                ->post($this->graph($whatsappAccount->phone_number_id . '/messages'), $payload);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo contactar a Meta desde el servidor: ' . $e->getMessage()], 502);
        }

        if (!$res->successful()) {
            return response()->json(['success' => false, 'error' => $this->explain($res)], 422);
        }

        return response()->json([
            'success'    => true,
            'message_id' => $res->json('messages.0.id'),
            'wa_id'      => $res->json('contacts.0.wa_id'),
            'to'         => $to,
            'mode'       => $mode,
        ]);
    }

    /** Estado del webhook (para refrescar sin volver a llamar a Meta). */
    public function webhookStatus(WhatsappAccount $whatsappAccount): JsonResponse
    {
        $last = WhatsappMessage::query()
            ->whereHas('conversation', fn ($q) => $q->where('account_id', $whatsappAccount->id))
            ->where('sender_type', 'contact')
            ->latest('id')
            ->first();

        return response()->json([
            'callback_url' => url('/whatsapp/webhook'),
            'checks'       => $this->webhookChecks($whatsappAccount),
            'last_inbound' => $last ? [
                'at'      => optional($last->created_at)->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s'),
                'preview' => \Illuminate\Support\Str::limit((string) $last->content, 80),
            ] : null,
        ]);
    }
}
