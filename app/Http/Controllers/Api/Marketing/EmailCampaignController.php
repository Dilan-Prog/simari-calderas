<?php

namespace App\Http\Controllers\Api\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmailCampaignResource;
use App\Http\Resources\EmailSendResource;
use App\Models\EmailCampaign;
use App\Models\EmailSend;
use App\Services\EmailCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Wrapper delgado sobre EmailCampaignService::send() -- NO reimplementa el
 * renderizado/envío. send() ya es asíncrono del lado del servicio (crea un
 * EmailSend por destinatario suscrito y despacha SendMarketingEmailJob por
 * cada uno vía DB::transaction), este controlador solo lo dispara y refleja
 * el estado actualizado de la campaña.
 *
 * sends() cubre `GET /api/v1/email-sends` del plan (Fase 6): lectura de
 * aperturas/clicks para que N8N pueda hacer polling de resultados de
 * campaña, filtrable por `campaign_id`.
 */
class EmailCampaignController extends Controller
{
    public function send(EmailCampaign $campaign): JsonResponse
    {
        app(EmailCampaignService::class)->send($campaign);

        return response()->json(['data' => new EmailCampaignResource($campaign->fresh())]);
    }

    public function sends(Request $request): JsonResponse
    {
        $request->validate([
            'campaign_id' => ['nullable', 'integer'],
        ]);

        $emailSends = EmailSend::query()
            ->whereNotNull('email_campaign_id')
            ->when($request->filled('campaign_id'), fn ($q) => $q->where('email_campaign_id', $request->integer('campaign_id')))
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 50));

        return response()->json([
            'data' => EmailSendResource::collection($emailSends->items()),
            'meta' => [
                'current_page' => $emailSends->currentPage(),
                'last_page'    => $emailSends->lastPage(),
                'per_page'     => $emailSends->perPage(),
                'total'        => $emailSends->total(),
            ],
        ]);
    }
}
