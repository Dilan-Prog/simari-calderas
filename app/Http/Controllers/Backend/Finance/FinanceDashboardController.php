<?php

namespace App\Http\Controllers\Backend\Finance;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use App\Services\FinanceService;
use Illuminate\Http\Request;

/**
 * Panel de finanzas (FIN1): por cobrar, por pagar, vencido, flujo a 30 días,
 * antigüedad de cartera y lo urgente. Solo lectura; reutiliza FinanceService.
 */
class FinanceDashboardController extends Controller
{
    public function __construct(private FinanceService $finance)
    {
    }

    public function __invoke(Request $request)
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->hasPermission('receivables') || $user->hasPermission('payables') || $user->hasPermission('payments')),
            403
        );

        $rec = $this->finance->summary('receivable');
        $pay = $this->finance->summary('payable');
        $cash = $this->finance->cashflow();
        $aging = $this->finance->agingReport('receivable');

        // Cartera vencida por cliente (todo lo que no es "vigente"), mayor primero.
        $topOverdue = collect($aging['rows'])
            ->map(fn ($r) => $r + ['overdue' => round($r['total'] - $r['current'], 2)])
            ->filter(fn ($r) => $r['overdue'] > 0)
            ->sortByDesc('overdue')->take(5)->values();

        // Flujo a 30 días en 4 tramos semanales (el último abarca hasta el día 30).
        $weeks = [];
        for ($i = 0; $i < 4; $i++) {
            $from = now()->startOfDay()->addDays($i * 7);
            $to = $i === 3 ? now()->startOfDay()->addDays(30) : $from->copy()->addDays(6);
            $weeks[] = ['from' => $from, 'to' => $to, 'in' => 0.0, 'out' => 0.0];
        }
        foreach (['in' => \App\Models\Receivable::class, 'out' => Payable::class] as $key => $class) {
            $class::open()->where('balance', '>', 0)
                ->whereDate('due_date', '>=', now()->toDateString())
                ->whereDate('due_date', '<=', now()->addDays(30)->toDateString())
                ->get(['id', 'due_date', 'balance', 'exchange_rate'])
                ->each(function ($d) use (&$weeks, $key) {
                    $diff = (int) now()->startOfDay()->diffInDays($d->due_date->copy()->startOfDay(), false);
                    $idx = min(3, intdiv(max(0, $diff), 7));
                    $weeks[$idx][$key] += $d->balanceMxn();
                });
        }

        $upcoming = Payable::with('supplier')->open()->where('balance', '>', 0)
            ->orderBy('due_date')->limit(5)->get();

        return view('admin.finance.dashboard', [
            'rec'        => $rec,
            'pay'        => $pay,
            'cash'       => $cash,
            'aging'      => $aging,
            'buckets'    => FinanceService::BUCKETS,
            'topOverdue' => $topOverdue,
            'weeks'      => $weeks,
            'upcoming'   => $upcoming,
        ]);
    }
}
