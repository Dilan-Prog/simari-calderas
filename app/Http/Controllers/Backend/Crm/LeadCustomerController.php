<?php

namespace App\Http\Controllers\Backend\Crm;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\WhatsappConversation;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "Agregar como cliente": convierte un lead (WhatsApp, correo, llamada, formulario o manual) en un
 * registro de la base de clientes, con los datos que se capturan en el formulario (no inventados),
 * o lo vincula a un cliente que ya existe para no duplicar.
 */
class LeadCustomerController extends Controller
{
    public const DOCUMENT_TYPES = ['ine' => 'INE', 'pasaporte' => 'Pasaporte', 'curp' => 'CURP', 'cfdi' => 'CFDI'];
    public const SOURCES = ['whatsapp' => 'WhatsApp', 'web' => 'Sitio web', 'admin' => 'Administración', 'campaña' => 'Campaña', 'referido' => 'Referido'];

    /** Origen del cliente que mejor corresponde al canal por el que llegó el lead. */
    private static function sourceFor(Lead $lead): string
    {
        return match ($lead->source) {
            'whatsapp'   => 'whatsapp',
            'formulario' => 'web',
            default      => 'admin',
        };
    }

    /** Fragmento HTML del modal (se pide por fetch desde la bandeja). */
    public function form(Lead $lead, LeadService $leads): View
    {
        $lead->loadMissing('customer');

        return view('admin.leads-inbox._customer_modal', [
            'lead'      => $lead,
            'duplicate' => $lead->customer_id ? null : $leads->findCustomer($lead->phone_normalized, $lead->contact_email),
            'prefill'   => [
                'full_name' => $lead->contact_name,
                'company'   => $lead->contact_company,
                'email'     => $lead->contact_email,
                'website'   => $lead->contact_website,
                'phone'     => $lead->contact_phone ?: $lead->phone_normalized,
                'source'    => self::sourceFor($lead),
            ],
            'documentTypes' => self::DOCUMENT_TYPES,
            'sources'       => self::SOURCES,
        ]);
    }

    public function store(Request $request, Lead $lead): RedirectResponse|JsonResponse
    {
        $back = redirect()->route('admin.leads-inbox.index', ['lead' => $lead->id]);

        if ($lead->customer_id) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Este lead ya está ligado a un cliente.', 'errors' => ['lead' => ['Este lead ya está ligado a un cliente.']]], 422);
            }

            return $back->with('error', 'Este lead ya está ligado a un cliente.');
        }

        $data = $request->validate([
            'link_customer_id' => 'nullable|integer|exists:customers,id',
            'full_name'        => 'required_without:link_customer_id|nullable|string|max:200',
            'company'          => 'nullable|string|max:150',
            'email'            => ['nullable', 'email', 'max:150', Rule::unique('customers', 'email')],
            'phone'            => 'nullable|string|max:30',
            'website'          => 'nullable|string|max:255',
            'rfc'              => 'nullable|string|max:20',
            'tipo_persona'     => 'nullable|in:fisica,moral',
            'document_type'    => 'nullable|in:' . implode(',', array_keys(self::DOCUMENT_TYPES)),
            'source'           => 'nullable|in:' . implode(',', array_keys(self::SOURCES)),
            'notes'            => 'nullable|string|max:1000',
        ], [
            'email.unique'              => 'Ese correo ya pertenece a otro cliente. Usa "Vincular a este cliente" para ligar el lead a él.',
            'full_name.required_without' => 'Escribe el nombre del cliente.',
        ]);

        if (! empty($data['link_customer_id'])) {
            $customer = Customer::findOrFail($data['link_customer_id']);
            $message = 'Lead vinculado al cliente ' . trim($customer->first_name . ' ' . $customer->last_name) . '.';
        } else {
            $parts = explode(' ', trim(preg_replace('/\s+/', ' ', $data['full_name'])), 2);
            $customer = Customer::create([
                'first_name'    => $parts[0],
                'last_name'     => $parts[1] ?? '',
                'company'       => $data['company'] ?? '',
                'website'       => LeadService::normalizeWebsite($data['website'] ?? null),
                'email'         => ! empty($data['email']) ? strtolower($data['email']) : null,
                'phone'         => $data['phone'] ?? '',
                'rfc'           => $data['rfc'] ?? null,
                'tipo_persona'  => $data['tipo_persona'] ?? null,
                'document_type' => $data['document_type'] ?? '',
                'source'        => $data['source'] ?? self::sourceFor($lead),
                'status'        => 'active',
                'notes'         => $data['notes'] ?? null,
                'visitor_uuid'  => $lead->visitor_uuid,
            ]);
            $message = 'Cliente agregado: ' . trim($customer->first_name . ' ' . $customer->last_name) . '.';
        }

        $this->linkLead($lead, $customer, $data);

        // Desde la ventana de la bandeja se envia por fetch: se responde con la direccion a la que volver.
        if ($request->expectsJson()) {
            session()->flash('success', $message);

            return response()->json(['redirect' => route('admin.leads-inbox.index', ['lead' => $lead->id])]);
        }

        return $back->with('success', $message);
    }

    /** Liga el lead (y su conversación de WhatsApp) al cliente y completa los datos de contacto que faltaban. */
    private function linkLead(Lead $lead, Customer $customer, array $data): void
    {
        $lead->customer_id = $customer->id;
        $lead->contact_name ??= trim($customer->first_name . ' ' . $customer->last_name) ?: null;
        $lead->contact_email ??= $customer->email ? LeadService::normalizeEmail($customer->email) : null;
        $lead->contact_website ??= $customer->website ?: null;
        if (! $lead->phone_normalized && $customer->phone) {
            $lead->contact_phone = $customer->phone;
            $lead->phone_normalized = LeadService::normalizePhone($customer->phone);
        }
        $lead->save();

        if ($lead->whatsapp_conversation_id) {
            WhatsappConversation::where('id', $lead->whatsapp_conversation_id)->whereNull('customer_id')->update(['customer_id' => $customer->id]);
        }
    }
}
