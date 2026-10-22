<?php

namespace App\Http\Controllers\Backend\Crm;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Support\LeadStages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Cambia la etapa de un lead dentro del Embudo de Venta (la misma que se ve en la pantalla "Embudo de Venta"). */
class LeadStageController extends Controller
{
    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate(['pipeline_stage_id' => 'required|integer|exists:pipeline_stages,id']);

        $stage = PipelineStage::with('pipeline')->findOrFail($data['pipeline_stage_id']);
        if ($stage->pipeline?->channel !== Pipeline::CHANNEL_WHATSAPP) {
            return back()->with('error', 'Esa etapa no pertenece al Embudo de Venta.');
        }

        LeadStages::move($lead, $stage);

        return redirect()->route('admin.leads-inbox.index', ['lead' => $lead->id])->with('success', 'Etapa actualizada: ' . $stage->name . '.');
    }
}
