<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ImageDuplicateGroup;
use App\Models\ImageDuplicateScan;
use App\Services\DuplicateImageScanner;
use App\Services\ImageReferenceService;
use App\Support\UploadPath;
use App\Traits\ImageUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DuplicateImageController extends Controller
{
    use ImageUploadTrait;

    public function __construct(
        protected ImageReferenceService $referenceService,
        protected DuplicateImageScanner $scanner,
    ) {
    }

    public function scan(Request $request)
    {
        $scan = $this->scanner->run(auth()->id());

        return response()->json([
            'success' => $scan->status === 'completed',
            'scan' => [
                'id' => $scan->id,
                'status' => $scan->status,
                'images_scanned' => $scan->images_scanned,
                'groups_found' => $scan->groups_found,
                'error_message' => $scan->error_message,
                'finished_at' => optional($scan->finished_at)->diffForHumans(),
            ],
        ]);
    }

    public function lastScan()
    {
        $scan = ImageDuplicateScan::latest('id')->first();

        return response()->json([
            'scan' => $scan ? [
                'id' => $scan->id,
                'status' => $scan->status,
                'images_scanned' => $scan->images_scanned,
                'groups_found' => ImageDuplicateGroup::where('status', 'pending')->count(),
                'finished_at' => optional($scan->finished_at)->diffForHumans(),
            ] : null,
        ]);
    }

    public function searchLibrary(Request $request)
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        $query = mb_strtolower(trim((string) $request->input('q', '')));

        $refs = collect($this->referenceService->listAll());

        if ($query !== '') {
            $refs = $refs->filter(fn ($r) => str_contains(mb_strtolower($r->label), $query)
                || str_contains(mb_strtolower($r->sublabel), $query)
                || str_contains(mb_strtolower($r->imageUrl), $query));
        }

        $results = $refs
            ->unique('imageUrl')
            ->take(30)
            ->map(fn ($r) => [
                'image_url' => $r->imageUrl,
                'url' => UploadPath::url($r->imageUrl),
                'label' => $r->label,
                'sublabel' => $r->sublabel,
            ])
            ->values();

        return response()->json(['results' => $results]);
    }

    public function apply(Request $request, ImageDuplicateGroup $group)
    {
        if ($group->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Este grupo ya no está pendiente.'], 422);
        }

        $groupImageUrls = $group->images()->pluck('image_url')->all();

        $data = $request->validate([
            'remove_image_urls' => ['required', 'array', 'min:1'],
            'remove_image_urls.*' => ['string'],
            'replacement_image_url' => ['nullable', 'string'],
            'new_image' => ['nullable', 'mimes:jpg,jpeg,png,gif,bmp,webp', 'max:8192'],
        ]);

        $removeUrls = array_values(array_unique($data['remove_image_urls']));

        foreach ($removeUrls as $url) {
            if (!in_array($url, $groupImageUrls, true)) {
                return response()->json(['success' => false, 'message' => 'Una de las imágenes a eliminar no pertenece a este grupo.'], 422);
            }
        }

        if ($request->hasFile('new_image')) {
            $paths = $this->uploadImages([$request->file('new_image')], 'uploads');

            if (empty($paths[0])) {
                return response()->json(['success' => false, 'message' => 'No se pudo procesar la imagen subida.'], 422);
            }

            $newImageUrl = $paths[0];
        } elseif (!empty($data['replacement_image_url'])) {
            $newImageUrl = $data['replacement_image_url'];

            $diskPath = $this->referenceService->resolveDiskPath($newImageUrl);

            if (!$diskPath || !file_exists($diskPath)) {
                return response()->json(['success' => false, 'message' => 'La imagen de reemplazo elegida no existe en disco.'], 422);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Selecciona una imagen de reemplazo (del grupo, de la biblioteca, o sube una nueva).'], 422);
        }

        if (in_array($newImageUrl, $removeUrls, true)) {
            return response()->json(['success' => false, 'message' => 'La imagen de reemplazo no puede ser una de las que estás eliminando.'], 422);
        }

        DB::transaction(function () use ($removeUrls, $newImageUrl, $group) {
            foreach ($removeUrls as $removeUrl) {
                foreach ($this->referenceService->findReferences($removeUrl) as $ref) {
                    $this->referenceService->rewriteReference($ref, $newImageUrl);
                }
            }

            $group->update(['status' => 'resolved']);
        });

        $deleted = [];
        foreach ($removeUrls as $removeUrl) {
            if ($this->referenceService->deletePhysicalFileIfOrphaned($removeUrl)) {
                $deleted[] = $removeUrl;
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($removeUrls) . ' imagen(es) consolidada(s).',
            'deleted_files' => $deleted,
        ]);
    }

    public function dismiss(ImageDuplicateGroup $group)
    {
        $group->update(['status' => 'dismissed']);

        return response()->json(['success' => true]);
    }
}
