<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

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

    public function index()
    {
        $values = [];
        foreach ($this->mailKeys as $key) {
            $values[$key] = Setting::get($key);
        }

        $hasPassword = (bool) Setting::get('mail.password');

        return view('admin.integrations.index', compact('values', 'hasPassword'));
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

            Mail::html(
                '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f2f3f5">'
                . '<tr><td align="center" style="padding:24px 12px;">'
                . '<table role="presentation" width="480" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:480px;max-width:100%;">'
                . '<tr><td align="left" bgcolor="#ffffff" style="padding:22px;">'
                . '<img src="' . $logoUrl . '" width="180" alt="Logo Equiterm" style="display:block;border:0;max-width:100%;height:auto;">'
                . '</td></tr>'
                . '<tr><td align="left" bgcolor="#ffffff" style="padding:0 22px 22px;font-family:Arial,sans-serif;font-size:14px;line-height:1.6;color:#141516;">'
                . 'Este es un correo de prueba enviado desde el módulo de Integraciones de Equiterm Industries.<br><br>'
                . 'Si lo estás leyendo (y ves el logo arriba), la configuración SMTP funciona correctamente.'
                . '</td></tr>'
                . '</table></td></tr></table>',
                function ($message) use ($request) {
                    $message->to($request->input('test_email'))
                        ->subject('Correo de prueba — Equiterm Industries');
                }
            );
        } catch (\Throwable $e) {
            return back()->with('mail_test_error', 'Falló el envío: ' . $e->getMessage());
        }

        return back()->with('mail_test_success', 'Correo de prueba enviado a ' . $request->input('test_email') . '. Revisa la bandeja (y spam).');
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
