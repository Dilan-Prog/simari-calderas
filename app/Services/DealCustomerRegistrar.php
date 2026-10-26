<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\Lead;

/**
 * Registra como cliente los datos de contacto capturados a mano en un negocio ("Sin cliente registrado").
 * Antes esos datos solo quedaban como texto del negocio y nunca se creaba el cliente.
 */
class DealCustomerRegistrar
{
    public function __construct(private LeadService $leads)
    {
    }

    /** ¿Hay datos suficientes para registrar al cliente? (al menos el nombre de contacto) */
    public static function canRegister(array $data): bool
    {
        return empty($data['customer_id']) && trim((string) ($data['contact_snapshot_name'] ?? '')) !== '';
    }

    /**
     * Crea el cliente con los datos de contacto, o liga uno existente si ya hay un cliente con ese correo/telefono
     * (no se duplica).
     *
     * @param  array{company_snapshot?:?string,contact_snapshot_name?:?string,contact_snapshot_email?:?string,contact_snapshot_phone?:?string,source?:?string}  $data
     * @return array{customer:Customer,created:bool}
     */
    public function fromContact(array $data): array
    {
        $email = LeadService::normalizeEmail($data['contact_snapshot_email'] ?? null);
        $phoneRaw = trim((string) ($data['contact_snapshot_phone'] ?? ''));
        $phone = LeadService::normalizePhone($phoneRaw);

        $existing = $this->leads->findCustomer($phone, $email);
        if ($existing) {
            return ['customer' => $existing, 'created' => false];
        }

        // Si el correo ya es de un usuario del equipo del portal (y ningún titular lo tiene), no puede ser el
        // correo de un cliente: se registra sin correo (el del contacto sigue en el negocio).
        if ($email && \App\Rules\EmailNotPortalMember::taken($email)) {
            $email = null;
        }

        $parts = explode(' ', trim(preg_replace('/\s+/', ' ', (string) $data['contact_snapshot_name'])), 2);

        $customer = Customer::create([
            'first_name'    => $parts[0],
            'last_name'     => $parts[1] ?? '',
            'company'       => trim((string) ($data['company_snapshot'] ?? '')),
            'email'         => $email,
            'phone'         => $phoneRaw,
            'document_type' => '',
            'source'        => 'admin',
            'status'        => 'active',
        ]);

        return ['customer' => $customer, 'created' => true];
    }

    /** Si el negocio viene de un lead que aun no es cliente, el lead pasa a ser de ese cliente. */
    public function linkLeadOf(Deal $deal, Customer $customer): void
    {
        Lead::where('deal_id', $deal->id)->whereNull('customer_id')->update(['customer_id' => $customer->id]);
    }
}
