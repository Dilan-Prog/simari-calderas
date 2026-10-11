<?php

namespace App\Services;

use App\Models\Bom;
use App\Models\BomLine;
use App\Models\Products;
use App\Models\SupplierProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Listas de materiales (BOM): alta/edición transaccional, versiones,
 * explosión multinivel y costeo estándar.
 *
 * Consumido también por MRP y Producción: mantener las firmas de
 * explode() y standardCost().
 */
class BomService
{
    public const MAX_DEPTH = 8;

    /** @var array<int, Bom|null> caché por ejecución de la BOM vigente */
    private array $activeCache = [];

    // ───────────────────────── Altas / ediciones ─────────────────────────

    /**
     * @param array $data  product_id, name?, is_active?, yield_quantity?, notes?
     * @param array $lines [['component_product_id','quantity','unit','scrap_percent','notes'], ...]
     */
    public function create(array $data, array $lines): Bom
    {
        $productId = (int) $data['product_id'];
        $this->validateLines($productId, $lines);

        return DB::transaction(function () use ($data, $lines, $productId) {
            $version = (int) Bom::where('product_id', $productId)->max('version') + 1;
            $hasActive = Bom::where('product_id', $productId)->where('is_active', true)->exists();
            $wantsActive = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : !$hasActive;

            $bom = Bom::create([
                'product_id'     => $productId,
                'version'        => $version,
                'name'           => $data['name'] ?? null,
                'is_active'      => false,
                'yield_quantity' => $this->yield($data['yield_quantity'] ?? 1),
                'notes'          => $data['notes'] ?? null,
            ]);
            $this->syncLines($bom, $lines);

            if ($wantsActive) {
                $this->activate($bom);
            }
            $this->activeCache = [];

            return $bom->fresh('lines');
        });
    }

    public function update(Bom $bom, array $data, array $lines): Bom
    {
        $this->validateLines($bom->product_id, $lines);

        return DB::transaction(function () use ($bom, $data, $lines) {
            $bom->update([
                'name'           => $data['name'] ?? null,
                'yield_quantity' => $this->yield($data['yield_quantity'] ?? $bom->yield_quantity),
                'notes'          => $data['notes'] ?? null,
            ]);
            $this->syncLines($bom, $lines);

            if (!empty($data['is_active']) && !$bom->is_active) {
                $this->activate($bom);
            } elseif (array_key_exists('is_active', $data) && !$data['is_active'] && $bom->is_active) {
                $bom->update(['is_active' => false]);
            }
            $this->activeCache = [];

            return $bom->fresh('lines');
        });
    }

    /** Marca como vigente esta versión y desactiva las demás del producto. */
    public function activate(Bom $bom): Bom
    {
        $bom = $bom->fresh('lines');
        foreach ($bom->lines as $line) {
            if ($this->reaches($line->component_product_id, $bom->product_id)) {
                throw ValidationException::withMessages([
                    'lines' => 'No se puede activar: un componente depende de este mismo producto (ciclo).',
                ]);
            }
        }

        DB::transaction(function () use ($bom) {
            Bom::where('product_id', $bom->product_id)->where('id', '!=', $bom->id)->where('is_active', true)
                ->get()->each->update(['is_active' => false]);
            $bom->update(['is_active' => true]);
        });
        $this->activeCache = [];

        return $bom->fresh();
    }

    /** Copia la receta como una versión nueva (max + 1), sin marcarla vigente. */
    public function duplicate(Bom $bom): Bom
    {
        return DB::transaction(function () use ($bom) {
            $copy = Bom::create([
                'product_id'     => $bom->product_id,
                'version'        => (int) Bom::where('product_id', $bom->product_id)->max('version') + 1,
                'name'           => $bom->name,
                'is_active'      => false,
                'yield_quantity' => $bom->yield_quantity,
                'notes'          => $bom->notes,
            ]);
            foreach ($bom->lines as $i => $line) {
                $copy->lines()->create([
                    'component_product_id' => $line->component_product_id,
                    'quantity'             => $line->quantity,
                    'unit'                 => $line->unit,
                    'scrap_percent'        => $line->scrap_percent,
                    'notes'                => $line->notes,
                    'sort_order'           => $line->sort_order,
                ]);
            }

            return $copy->fresh('lines');
        });
    }

    private function syncLines(Bom $bom, array $lines): void
    {
        $bom->lines()->delete();
        $order = 0;
        foreach ($lines as $line) {
            if (empty($line['component_product_id'])) {
                continue;
            }
            $bom->lines()->create([
                'component_product_id' => (int) $line['component_product_id'],
                'quantity'             => max(0.0001, (float) ($line['quantity'] ?? 1)),
                'unit'                 => ($line['unit'] ?? null) ?: null,
                'scrap_percent'        => min(999.99, max(0, (float) ($line['scrap_percent'] ?? 0))),
                'notes'                => ($line['notes'] ?? null) ?: null,
                'sort_order'           => $order++,
            ]);
        }
    }

    private function yield(mixed $v): float
    {
        $v = (float) $v;

        return $v > 0 ? $v : 1.0;
    }

    /** El componente no puede ser el propio producto ni generar ciclo en ningún nivel. */
    public function validateLines(int $productId, array $lines): void
    {
        foreach ($lines as $line) {
            $cid = (int) ($line['component_product_id'] ?? 0);
            if (!$cid) {
                continue;
            }
            if ($cid === $productId) {
                throw ValidationException::withMessages(['lines' => 'Un producto no puede ser componente de sí mismo.']);
            }
            if ($this->reaches($cid, $productId)) {
                $name = Products::find($cid)?->name ?? "#{$cid}";
                throw ValidationException::withMessages([
                    'lines' => "El componente «{$name}» ya contiene a este producto en su propia receta (ciclo).",
                ]);
            }
        }
    }

    /** ¿Desde $fromId, bajando por recetas vigentes, se llega a $targetId? */
    private function reaches(int $fromId, int $targetId, array &$seen = []): bool
    {
        if ($fromId === $targetId) {
            return true;
        }
        if (isset($seen[$fromId])) {
            return false;
        }
        $seen[$fromId] = true;

        $bom = Bom::where('product_id', $fromId)->where('is_active', true)->first();
        if (!$bom) {
            return false;
        }
        foreach ($bom->lines()->pluck('component_product_id') as $cid) {
            if ($this->reaches((int) $cid, $targetId, $seen)) {
                return true;
            }
        }

        return false;
    }

    // ───────────────────────── Explosión y costeo ─────────────────────────

    public function activeBom(Products $product): ?Bom
    {
        if (!array_key_exists($product->id, $this->activeCache)) {
            $this->activeCache[$product->id] = Bom::with('lines.component')
                ->where('product_id', $product->id)->where('is_active', true)->first();
        }

        return $this->activeCache[$product->id];
    }

    /**
     * Explosión multinivel de la BOM vigente: componentes hoja con la cantidad
     * total requerida (incluye merma), agregando duplicados.
     *
     * @return array<int, array{product_id:int, product:Products, quantity:float, unit:?string}>
     */
    public function explode(Products $product, float $quantity = 1.0): array
    {
        $acc = [];
        $this->walk($product, $quantity, 1, [$product->id], $acc);

        return array_values($acc);
    }

    private function walk(Products $product, float $qty, int $depth, array $path, array &$acc): void
    {
        $bom = $this->activeBom($product);
        if (!$bom) {
            return;
        }
        $yield = (float) $bom->yield_quantity > 0 ? (float) $bom->yield_quantity : 1.0;

        foreach ($bom->lines as $line) {
            $comp = $line->component;
            if (!$comp) {
                continue;
            }
            $need = $qty * ((float) $line->quantity / $yield) * (1 + (float) $line->scrap_percent / 100);

            $expandable = $depth < self::MAX_DEPTH
                && !in_array($comp->id, $path, true)
                && $this->activeBom($comp)
                && $this->activeBom($comp)->lines->isNotEmpty();

            if ($expandable) {
                $this->walk($comp, $need, $depth + 1, array_merge($path, [$comp->id]), $acc);
                continue;
            }

            if (isset($acc[$comp->id])) {
                $acc[$comp->id]['quantity'] += $need;
            } else {
                $acc[$comp->id] = [
                    'product_id' => $comp->id,
                    'product'    => $comp,
                    'quantity'   => $need,
                    'unit'       => $line->unit,
                ];
            }
        }
    }

    /** Costo estándar de fabricar UNA unidad del producto (con merma y subensambles). */
    public function standardCost(Products $product): float
    {
        return round($this->costOf($product, 1, [$product->id]), 4);
    }

    private function costOf(Products $product, int $depth, array $path): float
    {
        $bom = $this->activeBom($product);
        if (!$bom) {
            return 0.0;
        }
        $yield = (float) $bom->yield_quantity > 0 ? (float) $bom->yield_quantity : 1.0;
        $total = 0.0;
        foreach ($bom->lines as $line) {
            if ($line->component) {
                $total += $this->lineCost($line, $depth, $path, $yield);
            }
        }

        return $total;
    }

    private function lineCost(BomLine $line, int $depth, array $path, float $yield): float
    {
        $qty = ((float) $line->quantity / $yield) * (1 + (float) $line->scrap_percent / 100);

        return $qty * $this->unitCost($line->component, $depth, $path);
    }

    /** Costo unitario de un componente: subensamble → su costo estándar; si no, cost o último precio de proveedor. */
    public function unitCost(Products $product, int $depth = 1, array $path = []): float
    {
        $bom = $this->activeBom($product);
        if ($bom && $bom->lines->isNotEmpty() && $depth < self::MAX_DEPTH && !in_array($product->id, $path, true)) {
            return $this->costOf($product, $depth + 1, array_merge($path, [$product->id]));
        }

        return $this->purchaseCost($product);
    }

    /** Costo de compra: Products.cost (a MXN) o último precio del proveedor. */
    public function purchaseCost(Products $product): float
    {
        if ($product->cost !== null && (float) $product->cost > 0) {
            return $product->convertToMxn((float) $product->cost);
        }

        $sp = SupplierProduct::where('product_id', $product->id)->whereNotNull('cost')->where('cost', '>', 0)
            ->orderByDesc('is_primary')->orderByDesc('updated_at')->orderByDesc('id')->first();

        return $sp ? $product->convertToMxn((float) $sp->cost) : 0.0;
    }

    // ───────────────────────── Árbol para la vista ─────────────────────────

    /**
     * Árbol multinivel de una receta concreta (no necesariamente vigente).
     * Cada nodo: product, line, quantity (acumulada por unidad fabricada del
     * raíz × $qty), unit, scrap, unit_cost, cost (costo de la rama), children.
     */
    public function tree(Bom $bom, float $qty = 1.0): array
    {
        $bom->loadMissing('lines.component');

        return $this->treeNodes($bom, $qty, 1, [$bom->product_id]);
    }

    private function treeNodes(Bom $bom, float $qty, int $depth, array $path): array
    {
        $yield = (float) $bom->yield_quantity > 0 ? (float) $bom->yield_quantity : 1.0;
        $nodes = [];
        foreach ($bom->lines as $line) {
            $comp = $line->component;
            if (!$comp) {
                continue;
            }
            $need = $qty * ((float) $line->quantity / $yield) * (1 + (float) $line->scrap_percent / 100);
            $sub = $this->activeBom($comp);
            $cyclic = in_array($comp->id, $path, true);
            $expand = $sub && $sub->lines->isNotEmpty() && !$cyclic && $depth < self::MAX_DEPTH;

            $children = $expand ? $this->treeNodes($sub, $need, $depth + 1, array_merge($path, [$comp->id])) : [];
            $unitCost = $expand ? array_sum(array_column($children, 'cost')) / max($need, 0.0000001) : $this->purchaseCost($comp);

            $nodes[] = [
                'product'       => $comp,
                'line'          => $line,
                'quantity'      => $need,
                'unit'          => $line->unit,
                'scrap'         => (float) $line->scrap_percent,
                'is_assembly'   => $expand,
                'cyclic'        => $cyclic,
                'unit_cost'     => $unitCost,
                'cost'          => $expand ? array_sum(array_column($children, 'cost')) : $need * $unitCost,
                'missing_cost'  => !$expand && $unitCost <= 0,
                'children'      => $children,
            ];
        }

        return $nodes;
    }

    /** Costo estándar de una receta concreta (por unidad fabricada). */
    public function costOfBom(Bom $bom): float
    {
        $bom->loadMissing('lines.component', 'product');
        $yield = (float) $bom->yield_quantity > 0 ? (float) $bom->yield_quantity : 1.0;
        $total = 0.0;
        foreach ($bom->lines as $line) {
            if ($line->component) {
                $total += $this->lineCost($line, 1, [$bom->product_id], $yield);
            }
        }

        return round($total, 4);
    }
}
