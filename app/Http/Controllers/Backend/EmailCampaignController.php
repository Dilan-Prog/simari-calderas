<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\EmailCampaign;
use App\Models\EmailList;
use App\Models\EmailTemplate;
use App\Services\EmailCampaignService;
use Illuminate\Http\Request;

class EmailCampaignController extends Controller
{
    public function __construct(private EmailCampaignService $emailCampaignService)
    {
    }

    public function index()
    {
        $emailCampaigns = EmailCampaign::with(['template', 'list'])
            ->latest()
            ->paginate(15);

        return view('admin.email-campaigns.index', compact('emailCampaigns'));
    }

    public function create()
    {
        $templates = EmailTemplate::orderBy('name')->get();
        $lists = EmailList::orderBy('name')->get();

        return view('admin.email-campaigns.create', compact('templates', 'lists'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'template_id' => 'required|exists:email_templates,id',
            'name'        => 'required|string|max:255',
            'list_id'     => 'required|exists:email_lists,id',
        ]);

        $data['status'] = 'draft';

        $emailCampaign = EmailCampaign::create($data);

        return redirect()->route('admin.email-campaigns.show', $emailCampaign)
            ->with('success', "Campaña \"{$emailCampaign->name}\" creada exitosamente.");
    }

    public function show(EmailCampaign $emailCampaign)
    {
        $emailCampaign->load(['template', 'list']);

        $sends = $emailCampaign->sends()->with('clicks')->get();

        $metrics = [
            'total'         => $sends->count(),
            'opened'        => $sends->whereNotNull('opened_at')->count(),
            'clicked'       => $sends->whereNotNull('clicked_at')->count(),
            'bounced'       => $sends->whereNotNull('bounced_at')->count(),
            'unsubscribed'  => $sends->whereNotNull('unsubscribed_at')->count(),
        ];

        // Clickmap: agrupa todos los clicks (de todos los sends de la
        // campaña) por url, contando cuántas veces se dio click en cada una.
        $clickMap = $sends->flatMap(fn ($send) => $send->clicks)
            ->groupBy('url')
            ->map(fn ($clicks, $url) => [
                'url'   => $url,
                'count' => $clicks->count(),
            ])
            ->sortByDesc('count')
            ->values();

        return view('admin.email-campaigns.show', compact('emailCampaign', 'metrics', 'clickMap'));
    }

    /**
     * Endpoint que dispara el envío inmediato de la campaña (encola un
     * EmailSend + SendMarketingEmailJob por destinatario suscrito).
     */
    public function send(EmailCampaign $emailCampaign)
    {
        app(EmailCampaignService::class)->send($emailCampaign);

        return redirect()->route('admin.email-campaigns.show', $emailCampaign)
            ->with('success', "Campaña \"{$emailCampaign->name}\" enviada.");
    }

    /**
     * Programa la campaña para envío diferido. El envío real lo realiza el
     * comando email-campaigns:send-scheduled (ver
     * app/Console/Commands/SendScheduledCampaigns.php), programado en
     * Kernel::schedule() para correr cada minuto y despachar las campañas
     * cuyo scheduled_at ya venció.
     */
    public function schedule(Request $request, EmailCampaign $emailCampaign)
    {
        $data = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $emailCampaign->status = 'scheduled';
        $emailCampaign->scheduled_at = $data['scheduled_at'];
        $emailCampaign->save();

        return redirect()->route('admin.email-campaigns.show', $emailCampaign)
            ->with('success', "Campaña \"{$emailCampaign->name}\" programada para {$emailCampaign->scheduled_at}.");
    }

    public function destroy(EmailCampaign $emailCampaign)
    {
        $emailCampaign->delete();

        return redirect()->route('admin.email-campaigns.index')
            ->with('success', 'Campaña eliminada.');
    }
}
