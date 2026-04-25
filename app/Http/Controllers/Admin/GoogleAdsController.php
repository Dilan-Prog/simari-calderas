<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoogleConversion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleAdsController extends Controller
{
    // Column index → DB column map for DataTables ordering
    private const COLUMNS = [
        0 => 'id',
        1 => 'gclid',
        2 => 'conversion_name',
        3 => 'conversion_value',
        4 => 'currency_code',
        5 => 'order_id',
        6 => 'status',
        7 => 'conversion_time',
        8 => 'created_at',
    ];

    public function index()
    {
        $totalConversions = GoogleConversion::count();
        $totalValue       = (float) GoogleConversion::sum('conversion_value');
        $todayConversions = GoogleConversion::whereDate('conversion_time', today())->count();
        $statusBreakdown  = GoogleConversion::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('admin.google-ads.index', compact(
            'totalConversions',
            'totalValue',
            'todayConversions',
            'statusBreakdown'
        ));
    }

    public function datatable(Request $request): JsonResponse
    {
        $query = GoogleConversion::query();

        // Date range filter on conversion_time
        if ($request->filled('date_from')) {
            $query->whereDate('conversion_time', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('conversion_time', '<=', $request->date_to);
        }

        // Global search across key fields
        $search = $request->input('search.value');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('gclid', 'like', "%{$search}%")
                  ->orWhere('conversion_name', 'like', "%{$search}%")
                  ->orWhere('order_id', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $recordsTotal    = GoogleConversion::count();
        $recordsFiltered = $query->count();

        // Ordering
        $colIndex  = (int) $request->input('order.0.column', 0);
        $direction = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $column    = self::COLUMNS[$colIndex] ?? 'id';
        $query->orderBy($column, $direction);

        // Pagination
        $start = max(0, (int) $request->input('start', 0));
        $length = max(1, (int) $request->input('length', 10));
        $rows = $query->skip($start)->take($length)->get();

        $data = $rows->map(function (GoogleConversion $c) {
            [$label, $badgeClass] = match ($c->status) {
                'stored'  => ['Almacenado', 'status-stored'],
                'pending' => ['Pendiente',  'status-pending'],
                'sent'    => ['Enviado',    'status'],
                'failed'  => ['Fallido',    'status-failed'],
                default   => [$c->status,   ''],
            };

            $badge = '<span class="users-manager-badge ' . $badgeClass . '">' . $label . '</span>';

            $viewUrl = route('admin.google-ads.show', $c->id);
            $actions = <<<HTML
<div class="header-right-user-manager">
    <a href="{$viewUrl}" class="table-users-manager-action-btn edit" title="Ver detalle">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    </a>
</div>
HTML;

            $gclid = strlen($c->gclid) > 22 ? substr($c->gclid, 0, 22) . '…' : $c->gclid;

            return [
                $c->id,
                e($gclid),
                e($c->conversion_name),
                '$' . number_format((float) $c->conversion_value, 2),
                e($c->currency_code),
                e($c->order_id ?? '—'),
                $badge,
                $c->conversion_time?->format('d/m/Y H:i') ?? '—',
                $c->created_at->format('d/m/Y H:i'),
                $actions,
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data->values(),
        ]);
    }

    public function show(string $id)
    {
        $conversion = GoogleConversion::findOrFail($id);
        return view('admin.google-ads.show', compact('conversion'));
    }
}
