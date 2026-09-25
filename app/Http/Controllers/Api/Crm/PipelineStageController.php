<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Http\Resources\PipelineStageResource;
use App\Models\PipelineStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fase 3 (API N8N) — CRM / Pipeline Stages. Solo lectura: le da a N8N el
 * catálogo de etapas (con su id) para poder armar el payload de
 * POST /deals/{deal}/move-stage sin tener que adivinar ids desde el panel.
 */
class PipelineStageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PipelineStage::query()->orderBy('pipeline_id')->orderBy('order');

        if ($request->filled('pipeline_id')) {
            $query->where('pipeline_id', $request->pipeline_id);
        }

        return response()->json(['data' => PipelineStageResource::collection($query->get())]);
    }
}
