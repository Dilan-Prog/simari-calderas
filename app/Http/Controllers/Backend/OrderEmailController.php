<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\StoreOrderEmail;
use App\Services\LeadMailService;
use App\Services\StoreOrderMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Ecommerce > Correos de pedidos: activar y redactar los correos automáticos de la tienda,
 * ver la vista previa, mandar una prueba y revisar/reenviar lo que ya salió.
 */
class OrderEmailController extends Controller
{
    /** Los ajustes viven en el grupo "integraciones" (no se listan en Configuración del Sitio). */
    private const GROUP = 'integraciones';

    public function __construct(private StoreOrderMailService $orderMail, private LeadMailService $leadMail)
    {
    }

    public function index()
    {
        $events = [];
        foreach (StoreOrderMailService::EVENTS as $event) {
            $events[$event] = array_merge(config("order_emails.events.$event"), $this->orderMail->eventSettings($event));
        }

        $sender = $this->leadMail->sender();

        return view('admin.order-emails.index', [
            'enabled'      => $this->orderMail->enabled(),
            'events'       => $events,
            'templates'    => $this->orderMail->templates(),
            'tokens'      => config('order_emails.tokens'),
            'footer'       => $this->orderMail->footer(),
            'copyTo'       => $this->orderMail->copyTo(),
            'staffEnabled' => $this->orderMail->staffEnabled(),
            'staffTo'      => $this->orderMail->staffTo(),
            'sender'       => $sender,
            'smtpProblem'  => $this->leadMail->smtpProblem(),
            'logs'         => StoreOrderEmail::with('storeOrder:id,order_number')->latest('id')->limit(40)->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $events = StoreOrderMailService::EVENTS;

        $rules = [
            'enabled' => 'nullable|boolean',
            'copy_to' => 'nullable|string|max:500',
            'footer'  => 'nullable|string|max:2000',
            'staff_enabled' => 'nullable|boolean',
            'staff_to'      => 'nullable|string|max:500',
        ];
        foreach ($events as $event) {
            $rules["events.$event.enabled"] = 'nullable|boolean';
            $rules["events.$event.template"] = ['nullable', Rule::in(array_keys($this->orderMail->templates()))];
            $rules["events.$event.subject"] = 'required|string|max:200';
            $rules["events.$event.heading"] = 'required|string|max:150';
            $rules["events.$event.intro"] = 'required|string|max:3000';
        }
        $data = $request->validate($rules);

        try {
            $this->leadMail->parseCc((string) ($data['copy_to'] ?? ''));
        } catch (RuntimeException $e) {
            return back()->withErrors(['copy_to' => $e->getMessage()])->withInput();
        }

        // Destino del aviso interno: si se deja vacío vuelve al correo por defecto de config/order_emails.php.
        $staffTo = trim((string) ($data['staff_to'] ?? ''));
        foreach (preg_split('/[,;\s]+/', $staffTo, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $mail) {
            if (! filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                return back()->withErrors(['staff_to' => 'El correo «' . $mail . '» no es válido.'])->withInput();
            }
        }

        $this->put('order_emails.staff_enabled', $request->boolean('staff_enabled'), 'boolean');
        $this->put('order_emails.staff_to', $staffTo, 'string');
        $this->put('order_emails.enabled', $request->boolean('enabled'), 'boolean');
        $this->put('order_emails.copy_to', trim((string) ($data['copy_to'] ?? '')), 'string');
        $this->put('order_emails.footer', trim((string) ($data['footer'] ?? '')), 'string');

        foreach ($events as $event) {
            $e = $data['events'][$event];
            $this->put("order_emails.$event.enabled", (bool) ($e['enabled'] ?? false), 'boolean');
            $this->put("order_emails.$event.template", (string) ($e['template'] ?? config('order_emails.default_template', 'clasica')), 'string');
            $this->put("order_emails.$event.subject", trim($e['subject']), 'string');
            $this->put("order_emails.$event.heading", trim($e['heading']), 'string');
            $this->put("order_emails.$event.intro", trim($e['intro']), 'string');
        }

        return redirect()->route('admin.order-emails.index')->with('success', 'Correos de pedidos guardados.');
    }

    /** HTML del correo con un pedido de ejemplo, para verlo antes de activarlo. */
    public function preview(Request $request, string $event): Response
    {
        abort_unless(in_array($event, StoreOrderMailService::EVENTS, true), 404);

        // ?template=clave deja ver una plantilla sin guardarla; si no existe se usa la guardada.
        $data = $this->orderMail->viewData($this->orderMail->sampleOrder($event), $event, $request->query('template'));

        return response(view('emails.store-order', $data)->render());
    }

    public function sendTest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'event' => ['required', Rule::in(StoreOrderMailService::EVENTS)],
            'to'    => 'required|email|max:190',
        ]);

        try {
            $this->orderMail->sendSample($data['event'], $data['to']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['test' => $e->getMessage()])->withInput();
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['test' => 'No se pudo enviar la prueba: ' . $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Correo de prueba enviado a ' . $data['to'] . '.');
    }

    public function resend(StoreOrderEmail $email): RedirectResponse
    {
        $order = $email->storeOrder;
        abort_if($order === null, 404);

        $log = $this->orderMail->send($order, $email->event, auth()->id());

        if ($log->status !== StoreOrderEmail::STATUS_SENT) {
            return back()->withErrors(['resend' => 'No se pudo reenviar: ' . $log->error]);
        }

        return back()->with('success', 'Correo reenviado a ' . $log->to_email . '.');
    }

    private function put(string $key, mixed $value, string $type): void
    {
        Setting::updateOrCreate(['key' => $key], [
            'value'              => is_bool($value) ? ($value ? '1' : '0') : $value,
            'type'               => $type,
            'group_name'         => self::GROUP,
            'updated_by_user_id' => auth()->id(),
        ]);
    }
}
