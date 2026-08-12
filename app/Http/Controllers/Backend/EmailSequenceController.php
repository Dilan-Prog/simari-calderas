<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\EmailSequence;
use App\Models\EmailSequenceStep;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailSequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailSequenceController extends Controller
{
    public function __construct(private EmailSequenceService $emailSequenceService)
    {
    }

    public function index()
    {
        $emailSequences = EmailSequence::with(['owner', 'steps'])->latest()->get();

        return view('admin.email-sequences.index', compact('emailSequences'));
    }

    public function create()
    {
        $templates = EmailTemplate::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('admin.email-sequences.create', compact('templates', 'users'));
    }

    /**
     * Crea la sequence junto con sus steps anidados en la misma request
     * (mismo patrón que PipelineController::store con stages anidadas).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'owner_id'              => 'nullable|exists:users,id',
            'is_active'             => 'nullable|boolean',
            'steps'                 => 'nullable|array',
            'steps.*.template_id'   => 'required|exists:email_templates,id',
            'steps.*.order'         => 'required|integer|min:1',
            'steps.*.delay_days'    => 'nullable|integer|min:0',
        ]);

        $emailSequence = DB::transaction(function () use ($data) {
            $sequence = EmailSequence::create([
                'name'      => $data['name'],
                'owner_id'  => $data['owner_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            foreach ($data['steps'] ?? [] as $step) {
                EmailSequenceStep::create([
                    'sequence_id' => $sequence->id,
                    'order'       => $step['order'],
                    'template_id' => $step['template_id'],
                    'delay_days'  => $step['delay_days'] ?? 0,
                ]);
            }

            return $sequence;
        });

        return redirect()->route('admin.email-sequences.edit', $emailSequence)
            ->with('success', "Secuencia \"{$emailSequence->name}\" creada exitosamente.");
    }

    public function edit(EmailSequence $emailSequence)
    {
        $emailSequence->load('steps.template');
        $templates = EmailTemplate::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $customers = Customer::where('status', 'active')->orderBy('first_name')->get();

        return view('admin.email-sequences.edit', compact('emailSequence', 'templates', 'users', 'customers'));
    }

    public function update(Request $request, EmailSequence $emailSequence)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'owner_id'  => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
        ]);

        $emailSequence->update([
            'name'      => $data['name'],
            'owner_id'  => $data['owner_id'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        return redirect()->route('admin.email-sequences.edit', $emailSequence)
            ->with('success', "Secuencia \"{$emailSequence->name}\" actualizada exitosamente.");
    }

    public function destroy(EmailSequence $emailSequence)
    {
        $emailSequence->delete();

        return redirect()->route('admin.email-sequences.index')
            ->with('success', 'Secuencia eliminada.');
    }

    /**
     * Agrega un step al final de la sequence (o en el order indicado).
     */
    public function addStep(Request $request, EmailSequence $emailSequence)
    {
        $data = $request->validate([
            'template_id' => 'required|exists:email_templates,id',
            'order'       => 'nullable|integer|min:1',
            'delay_days'  => 'nullable|integer|min:0',
        ]);

        $order = $data['order'] ?? (($emailSequence->steps()->max('order') ?? 0) + 1);

        $step = EmailSequenceStep::create([
            'sequence_id' => $emailSequence->id,
            'order'       => $order,
            'template_id' => $data['template_id'],
            'delay_days'  => $data['delay_days'] ?? 0,
        ]);

        return redirect()->route('admin.email-sequences.edit', $emailSequence)
            ->with('success', 'Paso agregado a la secuencia.');
    }

    public function removeStep(EmailSequenceStep $step)
    {
        $emailSequence = $step->sequence;

        $step->delete();

        return redirect()->route('admin.email-sequences.edit', $emailSequence)
            ->with('success', 'Paso eliminado de la secuencia.');
    }

    /**
     * Inscribe manualmente a un customer en la sequence (delega en
     * EmailSequenceService::enroll).
     */
    public function enrollCustomer(Request $request, EmailSequence $emailSequence)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $customer = Customer::findOrFail($data['customer_id']);

        app(EmailSequenceService::class)->enroll($emailSequence, $customer);

        return redirect()->route('admin.email-sequences.edit', $emailSequence)
            ->with('success', "{$customer->first_name} inscrito en la secuencia \"{$emailSequence->name}\".");
    }
}
