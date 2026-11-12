<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Receivable;
use App\Models\SalesOrder;
use App\Models\StoreOrder;

/**
 * Listas para los selects del formulario de Cuentas por cobrar / por pagar: en lugar de teclear el número
 * interno del documento de origen (o del proyecto, o de la orden de compra), se elige de una lista.
 * Cada opción lleva el cliente/proveedor al que pertenece para poder filtrar la lista en pantalla.
 */
class FinanceOriginOptions
{
    /** Máximo de documentos por tipo (los más recientes); el documento ya ligado siempre se incluye. */
    private const LIMIT = 300;

    /**
     * Documentos de origen de una cuenta por cobrar, agrupados por tipo.
     *
     * @return array<string, array{label:string, items:array<int, array{value:string,label:string,party:?int}>}>
     */
    public static function receivableOrigins(?Receivable $doc = null): array
    {
        $groups = [];

        $groups['sales_order'] = ['label' => 'Pedidos', 'items' => self::items(
            SalesOrder::with('customer:id,first_name,last_name,company')->latest('id')->limit(self::LIMIT)->get(),
            self::linked($doc, 'sales_order', SalesOrder::class, ['customer:id,first_name,last_name,company']),
            fn ($o) => [$o->order_number . ' · ' . self::customerName($o->customer), $o->customer_id],
            'sales_order'
        )];

        $groups['quote'] = ['label' => 'Cotizaciones', 'items' => self::items(
            Quote::with('customer:id,first_name,last_name,company')->latest('id')->limit(self::LIMIT)->get(),
            self::linked($doc, 'quote', Quote::class, ['customer:id,first_name,last_name,company']),
            fn ($q) => [
                $q->quote_number . ' · ' . ($q->customer ? self::customerName($q->customer) : ($q->guest_company ?: ($q->guest_name ?: 'Sin cliente')))
                    . ' · $' . number_format((float) $q->total, 2) . ' ' . ($q->currency ?: 'MXN'),
                $q->customer_id,
            ],
            'quote'
        )];

        $groups['project'] = ['label' => 'Proyectos', 'items' => self::items(
            Project::latest('id')->limit(self::LIMIT)->get(),
            self::linked($doc, 'project', Project::class),
            fn ($p) => [$p->project_number . ' · ' . $p->name, $p->customer_id],
            'project'
        )];

        $groups['store_order'] = ['label' => 'Órdenes de tienda', 'items' => self::items(
            StoreOrder::latest('id')->limit(self::LIMIT)->get(),
            self::linked($doc, 'store_order', StoreOrder::class),
            fn ($o) => [
                $o->order_number . ' · ' . ($o->contact_name ?: 'Sin nombre') . ' · $' . number_format((float) $o->total, 2) . ' ' . ($o->currency ?: 'MXN'),
                $o->customer_id,
            ],
            'store_order'
        )];

        return $groups;
    }

    /**
     * Proyectos para el select "Proyecto" de una cuenta por cobrar.
     *
     * @return array<int, array{value:int,label:string,party:?int}>
     */
    public static function projects(?Receivable $doc = null): array
    {
        $rows = Project::latest('id')->limit(self::LIMIT)->get();
        if ($doc?->project_id && ! $rows->contains('id', $doc->project_id) && ($extra = Project::find($doc->project_id))) {
            $rows->push($extra);
        }

        return $rows->map(fn ($p) => ['value' => $p->id, 'label' => $p->project_number . ' · ' . $p->name, 'party' => $p->customer_id])->values()->all();
    }

    /**
     * Órdenes de compra para el select de una cuenta por pagar.
     *
     * @return array<int, array{value:int,label:string,party:?int}>
     */
    public static function purchaseOrders(?int $currentId = null): array
    {
        $rows = PurchaseOrder::with('supplier:id,company_name,contact_name')->latest('id')->limit(self::LIMIT)->get();
        if ($currentId && ! $rows->contains('id', $currentId) && ($extra = PurchaseOrder::with('supplier:id,company_name,contact_name')->find($currentId))) {
            $rows->push($extra);
        }

        return $rows->map(fn ($o) => [
            'value' => $o->id,
            'label' => $o->po_number . ' · ' . ($o->supplier?->company_name ?: ($o->supplier?->contact_name ?: 'Sin proveedor'))
                . ' · $' . number_format((float) $o->total, 2) . ' ' . ($o->currency ?: 'MXN'),
            'party' => $o->supplier_id,
        ])->values()->all();
    }

    /** Valida y separa "tipo:id" (valor del select de origen). null si viene vacío o no es válido. */
    public static function parseOrigin(?string $value): ?array
    {
        if ($value === null || $value === '' || ! preg_match('/^([a-z_]+):(\d+)$/', $value, $m)) {
            return null;
        }

        return isset(Receivable::SOURCE_TYPES[$m[1]]) ? ['type' => $m[1], 'id' => (int) $m[2]] : null;
    }

    // ── Interno ─────────────────────────────────────────────────────────

    /** Documento ya ligado a la cuenta (por si quedó fuera de los 300 más recientes). */
    private static function linked(?Receivable $doc, string $type, string $class, array $with = [])
    {
        if (! $doc || $doc->source_type !== $type || ! $doc->source_id) {
            return null;
        }

        return $class::with($with)->find($doc->source_id);
    }

    private static function items($rows, $linked, \Closure $describe, string $type): array
    {
        if ($linked && ! $rows->contains('id', $linked->id)) {
            $rows = $rows->push($linked);
        }

        return $rows->map(function ($row) use ($describe, $type) {
            [$label, $party] = $describe($row);

            return ['value' => $type . ':' . $row->id, 'label' => $label, 'party' => $party ? (int) $party : null];
        })->values()->all();
    }

    private static function customerName(?Customer $c): string
    {
        if (! $c) {
            return 'Sin cliente';
        }

        return $c->company ?: (trim($c->first_name . ' ' . $c->last_name) ?: 'Sin cliente');
    }
}
