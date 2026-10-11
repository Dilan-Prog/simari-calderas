<?php

namespace App\Services\Erp;

use App\Models\DocumentLink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * "Flujo del documento" estilo SAP: enlaza documentos del ERP entre sí
 * (Cotización → Pedido → Proyecto → Requerimiento → Orden de compra → …) y
 * reconstruye la cadena completa desde cualquier nodo.
 *
 * Desde un módulo, al crear un documento derivado:
 *   DocumentFlow::link($quote, $salesOrder);
 * En una vista:
 *   <x-erp.document-flow :document="$salesOrder" />
 */
class DocumentFlow
{
    private const MAX_NODES = 60;

    /** Clave de tipo (config/erp_documents.php) de un modelo, o null si no está registrado. */
    public static function typeOf(Model|string $model): ?string
    {
        $class = is_string($model) ? $model : $model::class;
        foreach (config('erp_documents', []) as $key => $def) {
            if ($def['model'] === $class) {
                return $key;
            }
        }

        return null;
    }

    /** Crea (idempotente) la arista $from → $to. Devuelve false si algún modelo no está registrado. */
    public static function link(Model $from, Model $to, ?string $relation = null): bool
    {
        $ft = self::typeOf($from);
        $tt = self::typeOf($to);
        if (!$ft || !$tt || !$from->getKey() || !$to->getKey() || !Schema::hasTable('document_links')) {
            return false;
        }

        DocumentLink::firstOrCreate(
            ['from_type' => $ft, 'from_id' => $from->getKey(), 'to_type' => $tt, 'to_id' => $to->getKey()],
            ['relation' => $relation]
        );

        return true;
    }

    /** Quita todas las aristas de un documento (p. ej. al borrarlo). */
    public static function unlinkAll(Model $doc): void
    {
        $t = self::typeOf($doc);
        if (!$t || !Schema::hasTable('document_links')) {
            return;
        }
        DocumentLink::where(fn ($q) => $q->where('from_type', $t)->where('from_id', $doc->getKey()))
            ->orWhere(fn ($q) => $q->where('to_type', $t)->where('to_id', $doc->getKey()))
            ->delete();
    }

    /**
     * Componente conexo del documento (en ambas direcciones), ordenado por etapa.
     *
     * @return array<int, array{type:string,id:int,label:string,number:string,status:?string,url:?string,current:bool,stage:int}>
     */
    public static function flow(Model $doc): array
    {
        $start = self::typeOf($doc);
        if (!$start || !Schema::hasTable('document_links')) {
            return [];
        }

        $seen = [$start . ':' . $doc->getKey() => [$start, (int) $doc->getKey()]];
        $queue = [[$start, (int) $doc->getKey()]];

        while ($queue && count($seen) < self::MAX_NODES) {
            [$t, $id] = array_shift($queue);
            $edges = DocumentLink::where(fn ($q) => $q->where('from_type', $t)->where('from_id', $id))
                ->orWhere(fn ($q) => $q->where('to_type', $t)->where('to_id', $id))
                ->get();
            foreach ($edges as $e) {
                foreach ([[$e->from_type, (int) $e->from_id], [$e->to_type, (int) $e->to_id]] as $node) {
                    $k = $node[0] . ':' . $node[1];
                    if (!isset($seen[$k])) {
                        $seen[$k] = $node;
                        $queue[] = $node;
                    }
                }
            }
        }

        $defs = config('erp_documents', []);
        $out = [];
        foreach ($seen as [$t, $id]) {
            $def = $defs[$t] ?? null;
            if (!$def || !class_exists($def['model'])) {
                continue;
            }
            $m = $def['model']::find($id);
            if (!$m) {
                continue;
            }
            $out[] = [
                'type'    => $t,
                'id'      => $id,
                'label'   => $def['label'],
                'number'  => (string) ($m->{$def['number']} ?? ('#' . $id)),
                'status'  => $def['status'] ? ($m->{$def['status']} ?? null) : null,
                'url'     => ($def['route'] && Route::has($def['route'])) ? route($def['route'], $id) : null,
                'current' => $t === $start && $id === (int) $doc->getKey(),
                'stage'   => (int) $def['stage'],
            ];
        }

        usort($out, fn ($a, $b) => [$a['stage'], $a['id']] <=> [$b['stage'], $b['id']]);

        return $out;
    }
}
