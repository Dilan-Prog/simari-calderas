<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ServiceRating;
use App\Models\ServiceReport;
use App\Models\Setting;
use App\Models\SurveyCard;
use App\Models\SurveyQuestion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Califica este servicio": tarjetas de calificación + encuesta de satisfacción.
 * Todo el comportamiento vive aquí; los controladores solo delegan.
 */
class SatisfactionSurveyService
{
    /** Clave => [valor, tipo]. Fuente única de los valores por defecto de survey.*. */
    public const DEFAULT_SETTINGS = [
        'survey.enabled'                 => ['1', 'boolean'],
        'survey.title'                   => ['Califica este servicio', 'string'],
        'survey.subtitle'                => ['¿Qué te pareció el servicio que recibiste?', 'string'],
        'survey.questions_title'         => ['Ayúdanos con una encuesta rápida', 'string'],
        'survey.comment_label'           => ['Cuéntanos más (opcional)', 'string'],
        'survey.submit_label'            => ['Enviar calificación', 'string'],
        'survey.thanks_message'          => ['¡Gracias por tu opinión! Nos ayuda a mejorar cada servicio.', 'string'],
        'survey.name_label'              => ['Cliente', 'string'],
        'survey.default_service_by_type' => ['{}', 'json'],
    ];

    public const COMMENT_MAX = 1000;

    public function text(string $key): string
    {
        $default = self::DEFAULT_SETTINGS[$key][0] ?? '';
        $value = Setting::get($key, $default);

        return is_string($value) && trim($value) !== '' ? $value : (string) $default;
    }

    public function enabled(): bool
    {
        return (bool) Setting::get('survey.enabled', true);
    }

    public function cards(): Collection
    {
        return SurveyCard::active()->get();
    }

    public function questions(): Collection
    {
        return SurveyQuestion::active()->get();
    }

    /** @return array<string,int> tipo de reporte => service_page_id */
    public function defaultServiceMap(): array
    {
        $map = Setting::get('survey.default_service_by_type', []);

        return is_array($map) ? array_filter($map, fn ($v) => (int) $v > 0) : [];
    }

    /** Reporte firmado + encuesta activa (con tarjetas) + sin calificación enviada. */
    public function isRateable(ServiceReport $report): bool
    {
        if (!$this->enabled() || $report->status !== 'signed') {
            return false;
        }

        if ($this->cards()->isEmpty()) {
            return false;
        }

        return !ServiceRating::where('service_report_id', $report->id)
            ->where('status', ServiceRating::STATUS_SUBMITTED)->exists();
    }

    /** Calificación enviada del reporte (si existe). */
    public function submittedFor(ServiceReport $report): ?ServiceRating
    {
        return ServiceRating::with('answers')
            ->where('service_report_id', $report->id)
            ->where('status', ServiceRating::STATUS_SUBMITTED)->first();
    }

    /** Nombre del cliente tal como lo trae el servicio (snapshot, nunca del request). */
    public function customerNameFor(ServiceReport $report): string
    {
        $name = trim((string) $report->customer_name);

        if ($name === '' && $report->customer_id) {
            $c = $report->customer ?? Customer::find($report->customer_id);
            $name = $c ? ($c->company ?: trim("{$c->first_name} {$c->last_name}")) : '';
        }

        if ($name === '' && $report->service_id) {
            $svc = $report->service;
            $c = $svc?->customer;
            $name = $c ? ($c->company ?: trim("{$c->first_name} {$c->last_name}")) : '';
        }

        return $name !== '' ? $name : 'Cliente';
    }

    /** service_page_id: el del reporte, o el mapeo por tipo, o el del servicio técnico / cotización. */
    public function servicePageIdFor(ServiceReport $report): ?int
    {
        if ($report->service_page_id) {
            return (int) $report->service_page_id;
        }

        $mapped = $this->defaultServiceMap()[$report->service_type] ?? null;
        if ($mapped) {
            return (int) $mapped;
        }

        $svc = $report->service;
        if ($svc?->service_page_id) {
            return (int) $svc->service_page_id;
        }

        $quote = $svc?->fromQuote;

        return $quote?->service_page_id ? (int) $quote->service_page_id : null;
    }

    /** Crea o lee la fila de calificación del reporte (pending por defecto). */
    public function ratingFor(ServiceReport $report): ServiceRating
    {
        $rating = ServiceRating::firstOrCreate(
            ['service_report_id' => $report->id],
            [
                'service_page_id' => $this->servicePageIdFor($report),
                'customer_id'     => $report->customer_id,
                'customer_name'   => $this->customerNameFor($report),
                'status'          => ServiceRating::STATUS_PENDING,
            ]
        );

        // Mientras no se envíe, los snapshots siguen al reporte.
        if ($rating->status !== ServiceRating::STATUS_SUBMITTED) {
            $rating->fill([
                'customer_id'     => $report->customer_id,
                'customer_name'   => $this->customerNameFor($report),
                'service_page_id' => $rating->service_page_id ?: $this->servicePageIdFor($report),
            ]);
            if ($rating->isDirty()) {
                $rating->save();
            }
        }

        return $rating;
    }

    /**
     * Guarda la calificación. El nombre del cliente NUNCA se toma de $data.
     *
     * @param array{card_id:int|string|null, answers?:array<int|string,mixed>|null, comment?:?string} $data
     * @throws ValidationException
     */
    public function submit(ServiceReport $report, array $data): ServiceRating
    {
        if ($report->status !== 'signed') {
            throw ValidationException::withMessages(['report' => 'Solo se pueden calificar reportes firmados.']);
        }
        if (!$this->enabled()) {
            throw ValidationException::withMessages(['report' => 'La encuesta de satisfacción no está activa.']);
        }

        $card = SurveyCard::active()->where('id', (int) ($data['card_id'] ?? 0))->first();
        if (!$card) {
            throw ValidationException::withMessages(['card_id' => 'Elige una de las tarjetas de calificación disponibles.']);
        }

        $comment = isset($data['comment']) ? trim((string) $data['comment']) : '';
        if (mb_strlen($comment) > self::COMMENT_MAX) {
            throw ValidationException::withMessages(['comment' => 'El comentario no puede pasar de ' . self::COMMENT_MAX . ' caracteres.']);
        }

        $answers = [];
        foreach ((array) ($data['answers'] ?? []) as $questionId => $score) {
            if ($score === null || $score === '') {
                continue; // responder cada variable es opcional
            }
            $question = SurveyQuestion::active()->where('id', (int) $questionId)->first();
            if (!$question) {
                throw ValidationException::withMessages(['answers' => 'Una de las variables de la encuesta ya no está disponible.']);
            }
            if (filter_var($score, FILTER_VALIDATE_INT) === false || (int) $score < 1 || (int) $score > 5) {
                throw ValidationException::withMessages(['answers' => "La calificación de «{$question->label}» debe estar entre 1 y 5."]);
            }
            $answers[$question->id] = ['question' => $question, 'score' => (int) $score];
        }

        return DB::transaction(function () use ($report, $card, $comment, $answers) {
            $this->ratingFor($report); // asegura la fila
            $rating = ServiceRating::where('service_report_id', $report->id)->lockForUpdate()->first();

            if ($rating->status === ServiceRating::STATUS_SUBMITTED) {
                throw ValidationException::withMessages(['report' => 'Este servicio ya fue calificado.']);
            }

            $rating->update([
                'rating'         => $card->stars,
                'survey_card_id' => $card->id,
                'card_label'     => $card->label,
                'comment'        => $comment !== '' ? $comment : null,
                'status'         => ServiceRating::STATUS_SUBMITTED,
                'submitted_at'   => now(),
            ]);

            $rating->answers()->delete();
            foreach ($answers as $a) {
                $rating->answers()->create([
                    'survey_question_id' => $a['question']->id,
                    'question_label'     => $a['question']->label,
                    'score'              => $a['score'],
                ]);
            }

            return $rating->load('answers');
        });
    }

    /** "Ahora no": queda disponible para calificar después desde el portal. */
    public function skip(ServiceReport $report): ServiceRating
    {
        $rating = $this->ratingFor($report);

        if ($rating->status !== ServiceRating::STATUS_SUBMITTED) {
            $rating->update(['status' => ServiceRating::STATUS_SKIPPED]);
        }

        return $rating;
    }

    /**
     * Datos de la pantalla "Califica este servicio" (compartida por admin y portal).
     * Devuelve null si el reporte no puede calificarse ni tiene calificación enviada.
     */
    public function screenData(ServiceReport $report, bool $justSubmitted = false): ?array
    {
        $submitted = $this->submittedFor($report);

        if (!$submitted && !$this->isRateable($report)) {
            return null;
        }

        $rating = $submitted ?: $this->ratingFor($report);
        $rating->loadMissing('card');

        $texts = [];
        foreach (array_keys(self::DEFAULT_SETTINGS) as $key) {
            $texts[$key] = $this->text($key);
        }

        return [
            'report'        => $report,
            'rating'        => $rating,
            'done'          => (bool) $submitted,
            'justSubmitted' => $justSubmitted,
            'cards'         => $this->cards(),
            'questions'     => $this->questions(),
            'texts'         => $texts,
            'commentMax'    => self::COMMENT_MAX,
        ];
    }

    /** Reportes firmados del cliente que aún no tienen calificación enviada. */
    public function pendingFor(Customer $customer): Collection
    {
        if (!$this->enabled()) {
            return new Collection();
        }

        return ServiceReport::where('customer_id', $customer->id)
            ->where('status', 'signed')
            ->whereDoesntHave('rating', fn ($q) => $q->where('status', ServiceRating::STATUS_SUBMITTED))
            ->latest('service_date')
            ->get();
    }
}
