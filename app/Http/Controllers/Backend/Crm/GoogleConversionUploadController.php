<?php

namespace App\Http\Controllers\Backend\Crm;

use App\Http\Controllers\Controller;
use App\Models\GoogleConversionUpload as Upload;
use App\Services\GoogleConversionService;
use Illuminate\Http\Request;

class GoogleConversionUploadController extends Controller
{
    public function __construct(private GoogleConversionService $service)
    {
    }

    public function index(Request $request)
    {
        $q = Upload::query()->with(['lead', 'deal']);

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('action')) {
            $q->where('action', $request->action);
        }
        if ($request->filled('operation')) {
            $q->where('operation', $request->operation);
        }
        if ($request->boolean('por_vencer')) {
            $q->where('status', 'pendiente')
                ->whereIn('identifier_type', ['gclid', 'gbraid', 'wbraid'])
                ->whereBetween('gclid_expires_at', [now(), now()->addDays((int) config('google_conversions.expiring_days', 14))]);
        }
        if ($request->filled('desde')) {
            $q->whereDate('created_at', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $q->whereDate('created_at', '<=', $request->hasta);
        }
        if ($request->filled('q')) {
            $term = trim($request->q);
            $q->where(function ($w) use ($term) {
                $w->where('order_id', 'like', "%{$term}%")->orWhere('identifier', 'like', "%{$term}%");
            });
        }

        $chips = [];
        $map = [
            'status'    => ['Estado', Upload::STATUSES],
            'action'    => ['Acción', Upload::ACTIONS],
            'operation' => ['Operación', Upload::OPERATIONS],
            'desde'     => ['Desde', []],
            'hasta'     => ['Hasta', []],
            'q'         => ['Búsqueda', []],
        ];
        foreach ($map as $key => [$label, $options]) {
            if ($request->filled($key)) {
                $chips[] = [
                    'label'  => $label,
                    'value'  => $options[$request->input($key)] ?? $request->input($key),
                    'remove' => $request->fullUrlWithoutQuery([$key, 'page']),
                ];
            }
        }
        if ($request->boolean('por_vencer')) {
            $chips[] = ['label' => 'GCLID', 'value' => 'por vencer', 'remove' => $request->fullUrlWithoutQuery(['por_vencer', 'page'])];
        }

        return view('admin.google-conversions.index', [
            'uploads' => $q->orderByDesc('id')->paginate(25)->withQueryString(),
            'summary' => $this->service->summary(),
            'chips'   => $chips,
            'configured' => $this->service->uploader()->isConfigured(),
        ]);
    }

    public function show(Upload $upload)
    {
        $upload->load(['lead', 'deal', 'parent', 'adjustments']);

        return view('admin.google-conversions.show', ['upload' => $upload]);
    }

    public function retry(Upload $upload)
    {
        if (! $upload->canRetry()) {
            return back()->with('error', 'Solo se pueden reintentar subidas pendientes o fallidas.');
        }

        $this->service->retry($upload);

        return back()->with('success', 'Reintento encolado.');
    }

    public function retryFailed()
    {
        $n = 0;
        Upload::where('status', 'fallida')->get()->each(function (Upload $u) use (&$n) {
            $this->service->retry($u);
            $n++;
        });

        return back()->with('success', "{$n} subidas reintentadas.");
    }
}
