<?php

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmailTemplateResource;
use App\Models\EmailTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Solo lectura -- las plantillas se crean/editan en el panel admin
 * (Backend\EmailTemplateController, con su builder visual de bloques). N8N
 * solo necesita poder leerlas (ej. para tomar el html_body y mandarlo por su
 * cuenta, o para elegir una plantilla por system_key antes de disparar una
 * campaña existente vía POST /email-campaigns/{campaign}/send).
 */
class EmailTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = EmailTemplate::query();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        $perPage = min((int) $request->input('per_page', 25), 100) ?: 25;
        $templates = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'data' => EmailTemplateResource::collection($templates->items()),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page'    => $templates->lastPage(),
                'per_page'     => $templates->perPage(),
                'total'        => $templates->total(),
            ],
        ]);
    }

    public function show(EmailTemplate $emailTemplate): JsonResponse
    {
        return response()->json(['data' => new EmailTemplateResource($emailTemplate)]);
    }
}
