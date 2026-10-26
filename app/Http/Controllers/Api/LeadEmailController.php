<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Services\LeadMailService;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * POST /api/v1/leads -- registra una solicitud llegada por correo (n8n escuchando el buzón).
 * Token de ApiClient con ability leads:write.
 *
 * Idempotente por `message_id` (cabecera Message-ID del correo): reenviar el mismo correo
 * devuelve el mismo resultado con `duplicate: true` y no crea otro lead ni otra tarea.
 * Si ya hay un lead abierto con ese correo lo actualiza en vez de duplicarlo.
 */
class LeadEmailController extends Controller
{
    public function store(Request $request, LeadService $leads): JsonResponse
    {
        $data = $request->validate([
            'message_id'  => 'required|string|max:255',
            'email'       => 'required|email|max:150',
            'name'        => 'nullable|string|max:150',
            'phone'       => 'nullable|string|max:40',
            'subject'     => 'nullable|string|max:255',
            'message'     => 'nullable|string|max:5000',
            'received_at' => 'nullable|date',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'assigned_to_email' => 'nullable|email|exists:users,email',
            'create_task' => 'nullable|boolean',
        ]);

        // Responsable por correo: no depende del ID, que cambia entre entornos.
        if (empty($data['assigned_to']) && ! empty($data['assigned_to_email'])) {
            $data['assigned_to'] = User::where('email', $data['assigned_to_email'])->value('id');
        }

        $result = $leads->captureFromEmail($data);

        return response()->json(['data' => $result], $result['duplicate'] ? 200 : 201);
    }

    /**
     * POST /api/v1/leads/outbound-email -- correo enviado por administracion@ (carpeta Enviados, n8n).
     * Solo se guarda si el destinatario es un cliente con tarea; si no, responde matched=false y no guarda nada.
     */
    public function storeOutbound(Request $request, LeadService $leads): JsonResponse
    {
        $data = $request->validate([
            'message_id'    => 'required|string|max:255',
            'from_email'    => 'nullable|string|max:190',
            'to'            => 'required',
            'cc'            => 'nullable',
            'subject'       => 'nullable|string|max:500',
            'body'          => 'nullable|string|max:200000',
            'sent_at'       => 'nullable|date',
            'in_reply_to'   => 'nullable|string|max:255',
            'attachments'   => 'nullable|array',
            'attachments.*' => 'nullable|string|max:255',
        ]);

        $result = $leads->recordOutboundEmail($data);

        return response()->json(['data' => $result], $result['matched'] && ! $result['duplicate'] ? 201 : 200);
    }

    /**
     * POST /api/v1/leads/{lead}/send-email -- n8n pide a la plataforma enviar un correo al lead por el SMTP
     * de la empresa. Queda registrado como correo saliente (source platform, sin usuario) y en la tarea.
     *
     * Con `template_id` se toman asunto y cuerpo de una plantilla (EmailTemplate) con los datos del
     * contacto; `subject`/`body` explícitos tienen prioridad sobre la plantilla.
     */
    public function sendEmail(Request $request, Lead $lead, LeadMailService $mail): JsonResponse
    {
        $data = $request->validate([
            'to'          => 'nullable|email|max:190',
            'cc'          => 'nullable',
            'cc.*'        => 'email|max:190',
            'subject'     => 'required_without:template_id|nullable|string|max:255',
            'body'        => 'required_without:template_id|nullable|string|max:100000',
            'template_id' => 'nullable|integer|exists:email_templates,id',
            'close_task'  => 'nullable|boolean',
            'quote_ids'   => 'nullable|array',
            'quote_ids.*' => 'integer',
        ]);

        $data['to'] = trim((string) ($data['to'] ?? '')) ?: (string) $lead->contact_email;

        if (! empty($data['template_id']) && (blank($data['subject'] ?? null) || blank($data['body'] ?? null))) {
            $rendered = $this->renderTemplate((int) $data['template_id'], $lead);
            $data['subject'] = filled($data['subject'] ?? null) ? $data['subject'] : $rendered['subject'];
            $data['body'] = filled($data['body'] ?? null) ? $data['body'] : $rendered['body'];
        }

        try {
            $email = $mail->send($lead, [
                'to'         => $data['to'],
                'cc'         => $data['cc'] ?? [],
                'subject'    => $data['subject'] ?? '',
                'body'       => $data['body'] ?? '',
                'quote_ids'  => $data['quote_ids'] ?? [],
                'close_task' => (bool) ($data['close_task'] ?? false),
            ], null);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => [
            'outbound_email_id' => $email->id,
            'message_id'        => $email->message_id,
            'task_id'           => $email->task_id,
            'lead_id'           => $lead->id,
        ]], 201);
    }

    /** Plantilla con los tokens del contacto del lead resueltos (asunto + texto plano). */
    private function renderTemplate(int $templateId, Lead $lead): array
    {
        $recipient = $lead->customer ?: (new Customer())->forceFill([
            'first_name' => (string) $lead->contact_name,
            'last_name'  => '',
            'email'      => $lead->contact_email,
            'company'    => $lead->contact_company,
        ]);

        $rendered = app(EmailTemplateService::class)->render(EmailTemplate::findOrFail($templateId), $recipient);

        $html = (string) $rendered['html'];
        $html = preg_replace('/<(style|script|head)\b[^>]*>.*?<\/\1>/is', '', $html);
        $html = preg_replace('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '$2 ($1)', $html);
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = preg_replace('/<\/(p|div|tr|h[1-6]|li|table)>/i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/ *\n */', "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return ['subject' => trim((string) $rendered['subject']), 'body' => trim($text)];
    }
}
