<?php

namespace App\Services;

use App\Models\ImageDuplicateGroup;
use App\Models\ImageDuplicateGroupImage;
use App\Models\ImageDuplicateScan;
use Illuminate\Support\Facades\DB;

/**
 * Escaneo bajo demanda (botón, no automático): hashea cada ruta física
 * distinta del catálogo (con caché) y agrupa por componentes conexos de
 * distancia de Hamming ≤ umbral. Reemplaza los grupos "pending" de
 * escaneos previos en cada corrida, pero respeta los ya marcados
 * "resolved"/"dismissed" (un grupo dismissed no reaparece mientras su
 * conjunto exacto de rutas no cambie).
 */
class DuplicateImageScanner
{
    public function __construct(
        protected ImageReferenceService $referenceService,
        protected ImagePerceptualHashService $hashService,
    ) {
    }

    public function run(?int $triggeredBy = null): ImageDuplicateScan
    {
        $scan = ImageDuplicateScan::create([
            'status' => 'running',
            'triggered_by' => $triggeredBy,
            'started_at' => now(),
        ]);

        try {
            $urls = $this->referenceService->distinctLocalImageUrls();
            $hashes = [];

            foreach ($urls as $url) {
                $path = $this->referenceService->resolveDiskPath($url);

                if (!$path) {
                    continue;
                }

                $hash = $this->hashService->getOrRefresh($url, $path);

                if ($hash) {
                    $hashes[$url] = $hash;
                }
            }

            $clusters = $this->clusterByHammingDistance($hashes);

            $groupsCreated = DB::transaction(function () use ($clusters, $scan) {
                ImageDuplicateGroup::where('status', 'pending')->delete();

                $dismissedSets = ImageDuplicateGroup::where('status', 'dismissed')
                    ->with('images')
                    ->get()
                    ->map(fn (ImageDuplicateGroup $g) => $g->images->pluck('image_url')->sort()->values()->all())
                    ->all();

                $created = 0;

                foreach ($clusters as $cluster) {
                    $urlsInCluster = collect($cluster)->keys()->sort()->values()->all();

                    $isDismissed = collect($dismissedSets)->contains(fn ($set) => $set === $urlsInCluster);

                    if ($isDismissed) {
                        continue;
                    }

                    $group = ImageDuplicateGroup::create([
                        'scan_id' => $scan->id,
                        'representative_phash' => array_values($cluster)[0],
                        'image_count' => count($cluster),
                        'status' => 'pending',
                    ]);

                    foreach ($cluster as $url => $hash) {
                        ImageDuplicateGroupImage::create([
                            'group_id' => $group->id,
                            'image_url' => $url,
                            'phash' => $hash,
                        ]);
                    }

                    $created++;
                }

                return $created;
            });

            $scan->update([
                'status' => 'completed',
                'images_scanned' => count($hashes),
                'groups_found' => $groupsCreated,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $scan->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }

        return $scan->fresh();
    }

    /**
     * Agrupa por componentes conexos (union-find): dos imágenes entran al
     * mismo grupo si su distancia de Hamming ≤ umbral, transitivamente.
     * Comparación por pares O(n²) — aceptable para cientos/pocos miles de
     * imágenes; documentado como límite a revisar si el catálogo crece
     * mucho más.
     *
     * @param  array<string, string>  $hashes  image_url => phash
     * @return array<int, array<string, string>> clusters (url => phash), solo con 2+ miembros
     */
    protected function clusterByHammingDistance(array $hashes): array
    {
        $urls = array_keys($hashes);
        $parent = array_combine($urls, $urls);

        $find = function (string $x) use (&$find, &$parent) {
            while ($parent[$x] !== $x) {
                $parent[$x] = $parent[$parent[$x]];
                $x = $parent[$x];
            }

            return $x;
        };

        $union = function (string $a, string $b) use ($find, &$parent) {
            $rootA = $find($a);
            $rootB = $find($b);

            if ($rootA !== $rootB) {
                $parent[$rootB] = $rootA;
            }
        };

        for ($i = 0; $i < count($urls); $i++) {
            for ($j = $i + 1; $j < count($urls); $j++) {
                if ($this->hashService->isMatch($hashes[$urls[$i]], $hashes[$urls[$j]])) {
                    $union($urls[$i], $urls[$j]);
                }
            }
        }

        $groups = [];

        foreach ($urls as $url) {
            $root = $find($url);
            $groups[$root][$url] = $hashes[$url];
        }

        return array_values(array_filter($groups, fn ($g) => count($g) >= 2));
    }
}
