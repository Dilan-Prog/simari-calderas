<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Negocios de un lead (puede tener varios a la vez): cada uno se crea solo si el lead ya es cliente, con los datos del cliente ya dados de alta,
 * y se le ligan cotizaciones (el monto del negocio sale de la cotizacion mas reciente).
 */
class LeadDealService
{
    /**
     * @param  array{name?:?string,amount?:mixed,currency?:?string,expected_close_date?:?string,notes?:?string,owner_id?:?int}  $data
     */
    public function createForLead(Lead $lead, array $data, ?User $user = null): Deal
    {
        $customer = $lead->customer_id ? $lead->customer()->first() : null;
        if (! $customer) {
            throw ValidationException::withMessages(['lead' => 'Para crear un negocio primero agrega al lead como cliente.']);
        }

        $pipeline = Pipeline::defaultForDeals();
        if (! $pipeline) {
            throw ValidationException::withMessages(['lead' => 'No hay un embudo de Negocios activo con etapas para crear el negocio.']);
        }

        $customerName = trim($customer->first_name . ' ' . $customer->last_name);
        $deal = app(DealService::class)->create([
            'pipeline_id'            => $pipeline->id,
            'name'                   => trim((string) ($data['name'] ?? '')) ?: $this->defaultName($lead, $customer->company ?: $customerName),
            'amount'                 => $data['amount'] ?? 0,
            'currency'               => $data['currency'] ?? 'MXN',
            'expected_close_date'    => $data['expected_close_date'] ?? null,
            'owner_id'               => $data['owner_id'] ?? $lead->assigned_to ?? $user?->id,
            'customer_id'            => $customer->id,
            'lead_id'                => $lead->id,
            'company_snapshot'       => $customer->company ?: null,
            'contact_snapshot_name'  => $customerName,
            'contact_snapshot_email' => $customer->email,
            'contact_snapshot_phone' => $customer->phone ?: null,
            'source'                 => $lead->source,
            'status'                 => 'open',
            'notes'                  => $data['notes'] ?? null,
        ]);

        // leads.deal_id queda como negocio principal / mas reciente.
        $lead->deal_id = $deal->id;
        $lead->save();
        if (in_array($lead->status, ['nuevo', 'contactado'], true)) {
            app(LeadService::class)->changeStatus($lead, 'calificado');
        }

        return $deal;
    }

    /** "Negocio - <empresa>", y " (2)", " (3)"... si el lead ya tiene negocios con ese nombre. */
    private function defaultName(Lead $lead, string $company): string
    {
        $base = 'Negocio - ' . $company;
        $taken = Deal::withTrashed()->where('lead_id', $lead->id)->pluck('name')->all();
        $name = $base;
        for ($n = 2; in_array($name, $taken, true); $n++) {
            $name = $base . ' (' . $n . ')';
        }

        return $name;
    }

    /** El negocio pertenece al lead: deals.lead_id, o el legacy leads.deal_id. */
    public function belongsToLead(Lead $lead, Deal $deal): bool
    {
        return (int) $deal->lead_id === (int) $lead->id
            || (! $deal->lead_id && $lead->deal_id && (int) $lead->deal_id === (int) $deal->id);
    }

    /** Liga la cotizacion al negocio y toma su total como monto del negocio. */
    public function attachQuote(Deal $deal, Quote $quote): void
    {
        $deal->quotes()->syncWithoutDetaching([$quote->id]);
        app(DealService::class)->update($deal, ['amount' => $quote->total, 'currency' => $quote->currency ?: $deal->currency]);
    }

    /** Quita la cotizacion del negocio; el monto pasa a la cotizacion ligada mas reciente (si queda alguna). */
    public function detachQuote(Deal $deal, Quote $quote): void
    {
        $deal->quotes()->detach($quote->id);

        $latest = $deal->quotes()->orderByDesc('deal_quote.id')->first();
        if ($latest) {
            app(DealService::class)->update($deal, ['amount' => $latest->total, 'currency' => $latest->currency ?: $deal->currency]);
        }
    }
}
