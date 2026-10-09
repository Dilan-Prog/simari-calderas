<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryFilterGroup;
use App\Models\CategoryFilterOption;
use App\Models\Products;
use App\Services\Catalog\TagNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

/**
 * Administración de los FILTROS TÉCNICOS de una categoría (catálogo público
 * estilo Mercado Libre). Cada grupo (ej. "Tamaño DIN") tiene opciones que
 * apuntan a una etiqueta de producto (products.tags); los grupos de una
 * categoría aplican a ella y a sus descendientes.
 *
 * Contadores: "products_count" y las etiquetas sugeridas se calculan sobre los
 * productos PUBLICADOS (is_active = 1 y publish_on_website = 1) del SUBÁRBOL de
 * la categoría, es decir, los que realmente verá el cliente en el catálogo.
 */
class CategoryFilterController extends Controller
{
    private const MAX_GROUPS = 10;
    private const MAX_OPTIONS = 15;
    private const MAX_SUGGESTIONS = 40;

    /** GET /categorias/{id}/filtros */
    public function show(string $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $payload = $this->payload($category);
        unset($payload['_ctx']);

        return response()->json($payload);
    }

    /** PUT /categorias/{id}/filtros */
    public function update(Request $request, string $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $input = $this->cleanInput((array) $request->input('groups', []));
        $validator = $this->validator($input);

        $existingGroups = CategoryFilterGroup::where('category_id', $category->id)->with('options')->get()->keyBy('id');
        $validator->after(function (ValidatorInstance $v) use ($input, $existingGroups) {
            $this->validateOwnership($v, $input, $existingGroups);
            $this->validateUniqueness($v, $input);
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Revisa los datos marcados en el formulario.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($category, $input, $existingGroups) {
            $this->sync($category, $input, $existingGroups);
        });

        $payload = $this->payload($category->fresh());

        return response()->json([
            'success'  => true,
            'groups'   => $payload['groups'],
            'inherited' => $payload['inherited'],
            'tag_suggestions' => $payload['tag_suggestions'],
            'warnings' => $this->warnings($category, $payload),
        ]);
    }

    // ---------------------------------------------------------------------
    // Entrada / validación
    // ---------------------------------------------------------------------

    /** Recorta textos y fija la forma esperada (el orden del arreglo manda). */
    private function cleanInput(array $groups): array
    {
        $out = [];
        foreach (array_values($groups) as $g) {
            if (! is_array($g)) {
                $g = [];
            }
            $options = [];
            foreach (array_values((array) ($g['options'] ?? [])) as $o) {
                if (! is_array($o)) {
                    $o = [];
                }
                $options[] = [
                    'id'        => isset($o['id']) && $o['id'] !== '' ? $o['id'] : null,
                    'label'     => isset($o['label']) ? trim((string) $o['label']) : null,
                    'tag'       => isset($o['tag']) ? trim((string) $o['tag']) : null,
                    'is_active' => array_key_exists('is_active', $o) ? $o['is_active'] : true,
                ];
            }
            $out[] = [
                'id'        => isset($g['id']) && $g['id'] !== '' ? $g['id'] : null,
                'name'      => isset($g['name']) ? trim((string) $g['name']) : null,
                'is_active' => array_key_exists('is_active', $g) ? $g['is_active'] : true,
                'options'   => $options,
            ];
        }

        return $out;
    }

    private function validator(array $input): ValidatorInstance
    {
        $rules = [
            'groups'                        => ['present', 'array', 'max:' . self::MAX_GROUPS],
            'groups.*.id'                   => ['nullable', 'integer'],
            'groups.*.name'                 => ['required', 'string', 'max:60'],
            'groups.*.is_active'            => ['nullable', 'boolean'],
            'groups.*.options'              => ['required', 'array', 'min:1', 'max:' . self::MAX_OPTIONS],
            'groups.*.options.*.id'         => ['nullable', 'integer'],
            'groups.*.options.*.label'      => ['required', 'string', 'max:80'],
            'groups.*.options.*.tag'        => ['required', 'string', 'max:80'],
            'groups.*.options.*.is_active'  => ['nullable', 'boolean'],
        ];

        $messages = [
            'groups.present'                      => 'No se recibieron los grupos de filtros.',
            'groups.array'                        => 'El formato de los grupos no es válido.',
            'groups.max'                          => 'Una categoría admite máximo ' . self::MAX_GROUPS . ' grupos de filtros.',
            'groups.*.name.required'              => 'El nombre del grupo es obligatorio.',
            'groups.*.name.max'                   => 'El nombre del grupo no puede tener más de 60 caracteres.',
            'groups.*.options.required'           => 'Agrega al menos una opción al grupo.',
            'groups.*.options.min'                => 'Agrega al menos una opción al grupo.',
            'groups.*.options.max'                => 'Un grupo admite máximo ' . self::MAX_OPTIONS . ' opciones.',
            'groups.*.options.array'              => 'El formato de las opciones no es válido.',
            'groups.*.options.*.label.required'   => 'La etiqueta visible es obligatoria.',
            'groups.*.options.*.label.max'        => 'La etiqueta visible no puede tener más de 80 caracteres.',
            'groups.*.options.*.tag.required'     => 'La etiqueta de producto es obligatoria.',
            'groups.*.options.*.tag.max'          => 'La etiqueta de producto no puede tener más de 80 caracteres.',
            'groups.*.id.integer'                 => 'El identificador del grupo no es válido.',
            'groups.*.options.*.id.integer'       => 'El identificador de la opción no es válido.',
            'groups.*.is_active.boolean'          => 'El estado del grupo no es válido.',
            'groups.*.options.*.is_active.boolean' => 'El estado de la opción no es válido.',
        ];

        return Validator::make(['groups' => $input], $rules, $messages);
    }

    /** Los ids recibidos deben ser de esta categoría y no repetirse. */
    private function validateOwnership(ValidatorInstance $v, array $input, $existingGroups): void
    {
        $knownOptionIds = $existingGroups->flatMap(fn ($g) => $g->options->pluck('id'))->all();
        $seenGroups = [];
        $seenOptions = [];

        foreach ($input as $gi => $g) {
            if ($g['id'] !== null) {
                if (! $existingGroups->has((int) $g['id']) || in_array((int) $g['id'], $seenGroups, true)) {
                    $v->errors()->add("groups.$gi.name", 'Este grupo ya no existe o no pertenece a la categoría. Cierra y vuelve a abrir el modal.');
                }
                $seenGroups[] = (int) $g['id'];
            }
            foreach ($g['options'] as $oi => $o) {
                if ($o['id'] !== null) {
                    if (! in_array((int) $o['id'], $knownOptionIds, true) || in_array((int) $o['id'], $seenOptions, true)) {
                        $v->errors()->add("groups.$gi.options.$oi.label", 'Esta opción ya no existe o no pertenece a la categoría. Cierra y vuelve a abrir el modal.');
                    }
                    $seenOptions[] = (int) $o['id'];
                }
            }
        }
    }

    /** Nombre de grupo único (normalizado) y etiqueta única en TODA la categoría. */
    private function validateUniqueness(ValidatorInstance $v, array $input): void
    {
        $groupNames = [];
        $tags = [];

        foreach ($input as $gi => $g) {
            $name = (string) ($g['name'] ?? '');
            if ($name !== '') {
                $key = TagNormalizer::normalize($name);
                if (isset($groupNames[$key])) {
                    $v->errors()->add("groups.$gi.name", "Ya existe otro grupo llamado «{$name}» en esta categoría.");
                } else {
                    $groupNames[$key] = $gi;
                }
            }

            foreach ($g['options'] as $oi => $o) {
                $tag = (string) ($o['tag'] ?? '');
                if ($tag === '') {
                    continue;
                }
                $key = TagNormalizer::normalize($tag);
                if ($key === '') {
                    $v->errors()->add("groups.$gi.options.$oi.tag", 'La etiqueta de producto debe contener letras o números.');
                    continue;
                }
                if (isset($tags[$key])) {
                    [$pgi, $poi] = $tags[$key];
                    $where = $pgi === $gi
                        ? 'en el mismo grupo'
                        : 'en el grupo «' . ($input[$pgi]['name'] ?? '') . '»';
                    $v->errors()->add(
                        "groups.$gi.options.$oi.tag",
                        "La etiqueta «{$tag}» ya se usa {$where} de esta categoría; cada etiqueta solo puede aparecer una vez."
                    );
                } else {
                    $tags[$key] = [$gi, $oi];
                }
            }
        }
    }

    // ---------------------------------------------------------------------
    // Guardado
    // ---------------------------------------------------------------------

    private function sync(Category $category, array $input, $existingGroups): void
    {
        $keptGroupIds = collect($input)->pluck('id')->filter()->map(fn ($i) => (int) $i)->all();
        $keptOptionIds = collect($input)->flatMap(fn ($g) => collect($g['options'])->pluck('id'))
            ->filter()->map(fn ($i) => (int) $i)->all();

        // 1) Opciones que ya no vienen (Eloquent: dispara listeners de caché; si una
        // se movió a otro grupo viene en $keptOptionIds y no se borra).
        foreach ($existingGroups as $group) {
            foreach ($group->options as $option) {
                if (! in_array($option->id, $keptOptionIds, true)) {
                    $option->delete();
                }
            }
        }

        // 2) Evita choques transitorios del índice único (group_id, tag_normalized)
        //    al intercambiar etiquetas: las opciones conservadas se "aparcan" con
        //    un valor temporal y se re-normalizan al guardarlas abajo.
        if ($keptOptionIds) {
            CategoryFilterOption::whereIn('id', $keptOptionIds)
                ->update(['tag_normalized' => DB::raw("CONCAT('~', id)")]);
        }

        // 3) Crear / actualizar con el orden del arreglo.
        foreach ($input as $gi => $g) {
            $group = $g['id'] !== null ? CategoryFilterGroup::find((int) $g['id']) : new CategoryFilterGroup();
            $group->category_id = $category->id;
            $group->name = $g['name'];
            $group->sort_order = $gi;
            $group->is_active = (bool) $g['is_active'];
            $group->save();

            foreach ($g['options'] as $oi => $o) {
                $option = $o['id'] !== null ? CategoryFilterOption::find((int) $o['id']) : new CategoryFilterOption();
                $option->group_id = $group->id;
                $option->label = $o['label'];
                $option->tag = $o['tag'];
                $option->sort_order = $oi;
                $option->is_active = (bool) $o['is_active'];
                $option->save();
            }
        }

        // 4) Ahora sí: borrar los grupos ausentes (ya sin opciones).
        foreach ($existingGroups as $group) {
            if (! in_array($group->id, $keptGroupIds, true)) {
                $group->delete();
            }
        }
    }

    // ---------------------------------------------------------------------
    // Lectura (GET y respuesta del PUT)
    // ---------------------------------------------------------------------

    private function payload(Category $category): array
    {
        $all = Category::get(['id', 'parent_id', 'name'])->keyBy('id');

        // Ancestros (de más lejano a más cercano) y subárbol, con un solo SELECT.
        $ancestors = [];
        $node = $category;
        $guard = 0;
        while ($node->parent_id && $all->has($node->parent_id) && $guard++ < 20) {
            $node = $all[$node->parent_id];
            array_unshift($ancestors, $node);
        }
        $childrenOf = $all->groupBy('parent_id');
        $subtreeIds = $this->descendantIds($category->id, $childrenOf);
        $descendantIds = array_values(array_diff($subtreeIds, [$category->id]));

        $path = collect($ancestors)->pluck('name')->push($category->name)->implode(' › ');

        // Conteo de productos por etiqueta normalizada (una sola consulta).
        [$counts, $labels] = $this->tagUsage($subtreeIds);

        $ownGroups = CategoryFilterGroup::where('category_id', $category->id)
            ->with('options')->orderBy('sort_order')->orderBy('id')->get();

        $ancestorGroups = CategoryFilterGroup::whereIn('category_id', collect($ancestors)->pluck('id'))
            ->with('options')->orderBy('sort_order')->orderBy('id')->get()->groupBy('category_id');

        $inherited = [];
        foreach ($ancestors as $anc) {
            $groups = $ancestorGroups->get($anc->id, collect());
            if ($groups->isEmpty()) {
                continue;
            }
            $inherited[] = [
                'category_id'   => $anc->id,
                'category_name' => $anc->name,
                'groups'        => $groups->map(fn ($g) => $this->formatGroup($g, $counts))->values()->all(),
            ];
        }

        // Etiquetas ya cubiertas (propias + heredadas) para no sugerirlas de nuevo.
        $covered = $ownGroups->merge($ancestorGroups->flatten())
            ->flatMap(fn ($g) => $g->options->pluck('tag_normalized'))
            ->flip()->all();

        $suggestions = collect($counts)
            ->reject(fn ($count, $norm) => isset($covered[$norm]))
            ->map(fn ($count, $norm) => ['tag' => $labels[$norm] ?? $norm, 'count' => $count])
            ->sortBy([['count', 'desc'], ['tag', 'asc']])
            ->take(self::MAX_SUGGESTIONS)
            ->values()
            ->all();

        return [
            'category' => ['id' => $category->id, 'name' => $category->name, 'path' => $path],
            'groups'   => $ownGroups->map(fn ($g) => $this->formatGroup($g, $counts))->values()->all(),
            'inherited' => $inherited,
            'tag_suggestions' => $suggestions,
            // Interno (lo usa warnings(); show() lo retira antes de responder).
            '_ctx' => [
                'ancestor_ids'   => collect($ancestors)->pluck('id')->all(),
                'descendant_ids' => $descendantIds,
            ],
        ];
    }

    /** IDs de la categoría y todos sus descendientes (sin consultas extra). */
    private function descendantIds(int $id, $childrenOf, int $depth = 0): array
    {
        $ids = [$id];
        if ($depth > 20) {
            return $ids;
        }
        foreach ($childrenOf->get($id, collect()) as $child) {
            $ids = array_merge($ids, $this->descendantIds($child->id, $childrenOf, $depth + 1));
        }

        return $ids;
    }

    /**
     * Uso de etiquetas en los productos publicados de las categorías dadas.
     *
     * @return array{0: array<string,int>, 1: array<string,string>} conteo por
     *         etiqueta normalizada y el texto más usado de cada una.
     */
    private function tagUsage(array $categoryIds): array
    {
        $rows = Products::whereIn('category_id', $categoryIds)
            ->where('is_active', true)
            ->where('publish_on_website', true)
            ->whereNotNull('tags')
            ->pluck('tags');

        $counts = [];
        $variants = [];
        foreach ($rows as $tags) {
            $seen = [];
            foreach ((array) $tags as $raw) {
                if (! is_string($raw) || trim($raw) === '') {
                    continue;
                }
                $norm = TagNormalizer::normalize($raw);
                if ($norm === '' || isset($seen[$norm])) {
                    continue;
                }
                $seen[$norm] = true;
                $counts[$norm] = ($counts[$norm] ?? 0) + 1;
                $text = trim($raw);
                $variants[$norm][$text] = ($variants[$norm][$text] ?? 0) + 1;
            }
        }

        $labels = [];
        foreach ($variants as $norm => $texts) {
            arsort($texts);
            $labels[$norm] = (string) array_key_first($texts);
        }

        return [$counts, $labels];
    }

    private function formatGroup(CategoryFilterGroup $group, array $counts): array
    {
        return [
            'id'         => $group->id,
            'name'       => $group->name,
            'sort_order' => (int) $group->sort_order,
            'is_active'  => (bool) $group->is_active,
            'options'    => $group->options->map(fn ($o) => [
                'id'             => $o->id,
                'label'          => $o->label,
                'tag'            => $o->tag,
                'sort_order'     => (int) $o->sort_order,
                'is_active'      => (bool) $o->is_active,
                'products_count' => $counts[$o->tag_normalized] ?? 0,
            ])->values()->all(),
        ];
    }

    // ---------------------------------------------------------------------
    // Avisos NO bloqueantes
    // ---------------------------------------------------------------------

    private function warnings(Category $category, array $payload): array
    {
        $warnings = [];
        $ancestorIds = $payload['_ctx']['ancestor_ids'];
        $descendantIds = $payload['_ctx']['descendant_ids'];

        // 1) Opciones sin productos en el subárbol.
        foreach ($payload['groups'] as $group) {
            foreach ($group['options'] as $opt) {
                if ($opt['products_count'] === 0) {
                    $warnings[] = [
                        'type'    => 'no_products',
                        'message' => "La opción «{$opt['label']}» (grupo «{$group['name']}») no tiene productos publicados con la etiqueta «{$opt['tag']}»; al elegirla el catálogo quedará vacío.",
                    ];
                }
            }
        }

        $own = [];
        foreach ($payload['groups'] as $group) {
            foreach ($group['options'] as $opt) {
                $own[] = ['norm' => TagNormalizer::normalize($opt['tag']), 'tag' => $opt['tag'], 'group' => $group['name']];
            }
        }

        // 2) Etiqueta repetida en un ancestro o en un descendiente.
        $related = CategoryFilterGroup::whereIn('category_id', array_merge($ancestorIds, $descendantIds))
            ->with(['options', 'category:id,name'])->get();
        $byNorm = [];
        foreach ($related as $g) {
            $isAncestor = in_array($g->category_id, $ancestorIds, true);
            foreach ($g->options as $o) {
                $byNorm[$o->tag_normalized][] = [
                    'kind'     => $isAncestor ? 'ancestor' : 'descendant',
                    'category' => $g->category?->name ?? '',
                    'group'    => $g->name,
                ];
            }
        }
        foreach ($own as $o) {
            foreach ($byNorm[$o['norm']] ?? [] as $hit) {
                $rel = $hit['kind'] === 'ancestor' ? 'la categoría superior' : 'la subcategoría';
                $warnings[] = [
                    'type'    => $hit['kind'] === 'ancestor' ? 'ancestor_duplicate' : 'descendant_duplicate',
                    'message' => "La etiqueta «{$o['tag']}» también está configurada en {$rel} «{$hit['category']}» (grupo «{$hit['group']}»); los filtros se mostrarían duplicados.",
                ];
            }
        }

        // 3) Etiquetas casi iguales (iguales al quitar los espacios).
        $squashed = [];
        $pool = $own;
        foreach ($related as $g) {
            foreach ($g->options as $o) {
                $pool[] = ['norm' => $o->tag_normalized, 'tag' => $o->tag, 'group' => $g->name];
            }
        }
        foreach ($pool as $item) {
            $key = str_replace(' ', '', $item['norm']);
            $squashed[$key][$item['norm']] = $item['tag'];
        }
        $ownKeys = array_flip(array_map(fn ($o) => str_replace(' ', '', $o['norm']), $own));
        foreach ($squashed as $key => $variants) {
            if (count($variants) > 1 && isset($ownKeys[$key])) {
                $warnings[] = [
                    'type'    => 'similar',
                    'message' => 'Estas etiquetas son casi iguales y NO coinciden entre sí: ' . collect($variants)->map(fn ($t) => "«{$t}»")->implode(', ') . '. Revisa que los productos usen la misma escritura.',
                ];
            }
        }

        return $warnings;
    }
}
