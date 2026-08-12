<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * CRUD de "Metas" (admin). Catálogo chico — sin paginación pesada, mismo
 * estilo de resource controller que DealController (redirects + flash),
 * a diferencia de PaymentMethodController que usa modales/JSON.
 */
class GoalController extends Controller
{
    // Únicas métricas soportadas hoy por Goal::progress(). `metric_type` es
    // string libre en BD (no enum) para poder agregar métricas nuevas sin
    // migración — esta lista es la única fuente de verdad de validación.
    private const METRIC_TYPES = ['deals_won_amount', 'deals_won_count', 'emails_sent'];

    /**
     * Pocos registros esperados (metas activas/históricas), así que calcular
     * progress() en el loop del index es aceptable — evita tener que
     * mantener una query agregada aparte solo para esta pantalla.
     */
    public function index()
    {
        $goals = Goal::with('owner')
            ->orderByDesc('period_start')
            ->get();

        foreach ($goals as $goal) {
            $goal->progressData = $goal->progress();
        }

        return view('admin.goals.index', compact('goals'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();

        return view('admin.goals.create', compact('users'));
    }

    private function validateGoal(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'metric_type' => 'required|string|in:' . implode(',', self::METRIC_TYPES),
            'target_value' => 'required|numeric|min:0',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'owner_id' => 'nullable|exists:users,id',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateGoal($request);

        $goal = Goal::create($data);

        return redirect()->route('admin.goals.index')
            ->with('success', "Meta \"{$goal->name}\" creada exitosamente.");
    }

    public function edit(Goal $goal)
    {
        $users = User::orderBy('name')->get();

        return view('admin.goals.edit', compact('goal', 'users'));
    }

    public function update(Request $request, Goal $goal)
    {
        $data = $this->validateGoal($request);

        $goal->update($data);

        return redirect()->route('admin.goals.index')
            ->with('success', "Meta \"{$goal->name}\" actualizada exitosamente.");
    }

    public function destroy(Goal $goal)
    {
        $goal->delete();

        return redirect()->route('admin.goals.index')
            ->with('success', "Meta \"{$goal->name}\" eliminada.");
    }
}
