<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\Quote;
use App\Models\Setting;
use App\Models\WhatsappAccount;
use App\Services\EmailTemplateService;
use App\Services\EmailTrackingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class IntegrationController extends Controller
{
    /**
     * Claves del grupo 'integraciones' que administra esta pantalla.
     * La contraseña se guarda encriptada (Crypt) y nunca se re-imprime
     * en el formulario; el resto son strings planos.
     */
    protected array $mailKeys = [
        'mail.host', 'mail.port', 'mail.username', 'mail.encryption',
        'mail.from_address', 'mail.from_name',
    ];

    /**
     * Cada "rol" es una Aplicación distinta en el panel de desarrolladores
     * de Mercado Pago (Public Key + Access Token + Webhook Secret propios) --
     * ver el comentario largo en config/services.php. Agregar una 3ra
     * aplicación en el futuro es solo agregar una entrada aquí (+ su propio
     * .env de respaldo en config/services.php) -- index()/updateMercadoPago()
     * y la vista ya iteran esta lista genéricamente.
     */
    protected array $mercadoPagoRoles = [
        'api'          => 'Checkout API (Tarjeta)',
        'checkout_pro' => 'Checkout Pro (Mercado Pago)',
    ];

    public function index()
    {
        $values = [];
        foreach ($this->mailKeys as $key) {
            $values[$key] = Setting::get($key);
        }

        $hasPassword = (bool) Setting::get('mail.password');

        // Un sub-array por rol -- ver $mercadoPagoRoles arriba. La vista
        // itera esto en un @foreach, así que agregar una 3ra aplicación no
        // requiere tocar esta vista salvo agregar la entrada al array de
        // arriba.
        $mercadoPago = [];
        foreach ($this->mercadoPagoRoles as $role => $label) {
            $mode = Setting::get("mercadopago.{$role}.mode", 'sandbox');
            $mode = in_array($mode, ['sandbox', 'live'], true) ? $mode : 'sandbox';
            $hasSandboxAccessToken = (bool) Setting::get("mercadopago.{$role}.sandbox_access_token");
            $hasLiveAccessToken = (bool) Setting::get("mercadopago.{$role}.live_access_token");

            $mercadoPago[$role] = [
                'label'                     => $label,
                'mode'                      => $mode,
                'sandbox_public_key'        => Setting::get("mercadopago.{$role}.sandbox_public_key"),
                'live_public_key'           => Setting::get("mercadopago.{$role}.live_public_key"),
                'has_sandbox_access_token'  => $hasSandboxAccessToken,
                'has_live_access_token'     => $hasLiveAccessToken,
                'has_webhook_secret'        => (bool) Setting::get("mercadopago.{$role}.webhook_secret"),
                // "Conectada" para ESTE rol: hay credenciales guardadas
                // para el modo que de verdad está activo ahora mismo.
                'configured'                => $mode === 'live' ? $hasLiveAccessToken : $hasSandboxAccessToken,
            ];
        }

        // "Conectada" en la barra lateral: basta con que la aplicación de
        // Tarjeta (api) ya funcione -- es el mínimo viable para cobrar algo;
        // Checkout Pro se puede terminar de configurar después sin que el
        // punto se vea "apagado" si ya se puede cobrar con tarjeta.
        $mercadoPagoConfigured = $mercadoPago['api']['configured'] ?? false;

        $webhooks = \App\Models\Webhook::with('credential')->orderBy('name')->get();

        // La antigua pantalla standalone "Cuentas de WhatsApp" se fusionó
        // aquí como 2 paneles separados -- uno por connection_type, cada
        // uno con su propia tabla/CRUD/modal (ver
        // resources/views/admin/integrations/partials/_whatsapp_*).
        $metaWhatsappAccounts = WhatsappAccount::where('connection_type', 'meta_cloud_api')->latest()->get();
        $qrWhatsappAccounts = WhatsappAccount::where('connection_type', 'baileys_qr')->latest()->get();

        $metaWhatsappActiveCount = $metaWhatsappAccounts->where('is_active', true)->count();
        $qrWhatsappConnectedCount = $qrWhatsappAccounts->where('session_status', 'connected')->count();

        // Se conserva para no romper nada más que siga usando este total
        // combinado (p.ej. reportes/estadísticas que lean esta variable).
        $whatsappActiveCount = \App\Models\WhatsappAccount::where('is_active', true)->count();

        return view('admin.integrations.index', compact(
            'values',
            'hasPassword',
            'webhooks',
            'whatsappActiveCount',
            'metaWhatsappAccounts',
            'qrWhatsappAccounts',
            'metaWhatsappActiveCount',
            'qrWhatsappConnectedCount',
            'mercadoPago',
            'mercadoPagoConfigured',
        ));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'mail_host'         => ['nullable', 'string', 'max:255'],
            'mail_port'         => ['nullable', 'integer', 'between:1,65535'],
            'mail_username'     => ['nullable', 'string', 'max:255'],
            'mail_password'     => ['nullable', 'string', 'max:255'],
            'mail_encryption'   => ['nullable', 'in:ssl,tls,none'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name'    => ['nullable', 'string', 'max:255'],
        ]);

        $this->saveSetting('mail.host', $data['mail_host'] ?? null);
        $this->saveSetting('mail.port', $data['mail_port'] ?? null, 'integer');
        $this->saveSetting('mail.username', $data['mail_username'] ?? null);
        $this->saveSetting('mail.encryption', $data['mail_encryption'] ?? null);
        $this->saveSetting('mail.from_address', $data['mail_from_address'] ?? null);
        $this->saveSetting('mail.from_name', $data['mail_from_name'] ?? null);

        // Campo vacío = conservar la contraseña actual (así el admin puede
        // editar el resto sin re-capturarla).
        if (filled($data['mail_password'] ?? null)) {
            $this->saveSetting('mail.password', Crypt::encryptString($data['mail_password']));
        }

        return back()->with('success', 'Configuración de correo guardada.');
    }

    public function updateMercadoPago(Request $request)
    {
        $data = $request->validate([
            'mercadopago'                        => ['required', 'array'],
            'mercadopago.*.mode'                 => ['required', 'in:sandbox,live'],
            'mercadopago.*.sandbox_public_key'    => ['nullable', 'string', 'max:255'],
            'mercadopago.*.sandbox_access_token'  => ['nullable', 'string', 'max:255'],
            'mercadopago.*.live_public_key'       => ['nullable', 'string', 'max:255'],
            'mercadopago.*.live_access_token'     => ['nullable', 'string', 'max:255'],
            'mercadopago.*.webhook_secret'        => ['nullable', 'string', 'max:255'],
        ]);

        // Un solo formulario guarda todos los roles a la vez (uno por
        // Aplicación de Mercado Pago -- ver $mercadoPagoRoles) -- si el
        // admin solo tocó los campos de un rol, el resto llega vacío y el
        // guard "campo vacío = conservar valor actual" de abajo hace que no
        // se pierda nada de los otros roles.
        foreach ($this->mercadoPagoRoles as $role => $label) {
            if (! isset($data['mercadopago'][$role])) {
                continue;
            }

            $roleData = $data['mercadopago'][$role];

            $this->saveSetting("mercadopago.{$role}.mode", $roleData['mode']);
            $this->saveSetting("mercadopago.{$role}.sandbox_public_key", $roleData['sandbox_public_key'] ?? null);
            $this->saveSetting("mercadopago.{$role}.live_public_key", $roleData['live_public_key'] ?? null);

            // Campo vacío = conservar el valor actual (mismo criterio que
            // mail.password) -- aplica por separado a cada uno de los 2
            // access token y al webhook secret de ESTE rol.
            if (filled($roleData['sandbox_access_token'] ?? null)) {
                $this->saveSetting("mercadopago.{$role}.sandbox_access_token", Crypt::encryptString($roleData['sandbox_access_token']));
            }
            if (filled($roleData['live_access_token'] ?? null)) {
                $this->saveSetting("mercadopago.{$role}.live_access_token", Crypt::encryptString($roleData['live_access_token']));
            }
            if (filled($roleData['webhook_secret'] ?? null)) {
                $this->saveSetting("mercadopago.{$role}.webhook_secret", Crypt::encryptString($roleData['webhook_secret']));
            }
        }

        return back()->with('success', 'Configuración de Mercado Pago guardada.');
    }

    public function sendTestMail(Request $request)
    {
        $request->validate(['test_email' => ['required', 'email']]);

        // MailSettingsServiceProvider ya aplicó a esta petición la config
        // guardada en BD, así que este envío prueba exactamente lo guardado.
        // HTML (no Mail::raw, que es texto plano y no puede llevar imagen)
        // con el mismo logo que ya usan las plantillas reales, para que la
        // prueba también confirme que el logo carga -- no solo la conexión.
        try {
            $logoUrl = asset('images/logo/Negro-color/Recurso%205equiterm-logo-negro-color-3x.png');

            $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f2f3f5">'
                . '<tr><td align="center" style="padding:24px 12px;">'
                . '<table role="presentation" width="480" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:480px;max-width:100%;">'
                . '<tr><td align="left" bgcolor="#ffffff" style="padding:22px;">'
                . '<img src="' . $logoUrl . '" width="180" alt="Logo Equiterm" style="display:block;border:0;max-width:100%;height:auto;">'
                . '</td></tr>'
                . '<tr><td align="left" bgcolor="#ffffff" style="padding:0 22px 22px;font-family:Arial,sans-serif;font-size:14px;line-height:1.6;color:#141516;">'
                . 'Este es un correo de prueba enviado desde el módulo de Integraciones de Equiterm Industries.<br><br>'
                . 'Si lo estás leyendo (y ves el logo arriba), la configuración SMTP funciona correctamente.'
                . '</td></tr>'
                . '</table></td></tr></table>';

            app(EmailTrackingService::class)->sendTracked(
                $request->input('test_email'),
                ['subject' => 'Correo de prueba — Equiterm Industries', 'html' => $html],
                ['guest_email' => $request->input('test_email'), 'guest_name' => 'Prueba SMTP']
            );
        } catch (\Throwable $e) {
            return back()->with('mail_test_error', 'Falló el envío: ' . $e->getMessage());
        }

        return back()->with('mail_test_success', 'Correo de prueba enviado a ' . $request->input('test_email') . '. Revisa la bandeja (y spam).');
    }

    /**
     * Diagnóstico: reproduce EXACTAMENTE el mismo camino de código que usa
     * una automatización real al mandar "Enviar correo" con
     * attach_source=quote_pdf (Pdf::loadView('admin.quotes.pdf') + adjunto
     * vía MarketingEmailMailable) -- pero disparado a mano, a la direccion
     * que se indique, contra una cotización real tomada al azar. Sirve para
     * aislar si el PDF adjunto se genera/envía bien desde el código, sin
     * depender de que corra el motor de workflows.
     */
    public function sendTestQuoteEmail(Request $request)
    {
        $request->validate(['test_email' => ['required', 'email']]);

        $quote = Quote::with('items')->inRandomOrder()->first();

        if (!$quote) {
            return back()->with('mail_test_error', 'No hay ninguna cotización en la base de datos para usar de prueba.');
        }

        try {
            $template = EmailTemplate::where('system_key', 'quote_manual_send')->firstOrFail();

            $rendered = app(EmailTemplateService::class)->render(
                $template,
                $quote->customer,
                null,
                $quote,
                $quote->customer ? null : $quote->guest_name,
                $quote->customer ? null : $quote->guest_email,
            );

            $pdf = Pdf::loadView('admin.quotes.pdf', ['quote' => $quote])->setPaper('a4', 'portrait');

            app(EmailTrackingService::class)->sendTracked(
                $request->input('test_email'),
                $rendered,
                ['guest_email' => $request->input('test_email'), 'guest_name' => 'Prueba PDF automatización'],
                [
                    'content'  => $pdf->output(),
                    'filename' => "{$quote->quote_number}.pdf",
                    'mime'     => 'application/pdf',
                ]
            );
        } catch (\Throwable $e) {
            return back()->with('mail_test_error', 'Falló el envío: ' . $e->getMessage());
        }

        return back()->with('mail_test_success', "Correo de prueba con PDF de la cotización {$quote->quote_number} enviado a " . $request->input('test_email') . '. Revisa la bandeja (y spam).');
    }

    protected function saveSetting(string $key, $value, string $type = 'string'): void
    {
        Setting::updateOrCreate(['key' => $key], [
            'value'              => $value,
            'type'               => $type,
            'group_name'         => 'integraciones',
            'is_public'          => false,
            'updated_by_user_id' => auth()->id(),
        ]);
    }
}
