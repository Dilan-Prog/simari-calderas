<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ServicePage;
use App\Models\Setting;
use App\Models\SurveyCard;
use App\Models\SurveyQuestion;
use App\Services\SatisfactionSurveyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

/** Panel "Encuesta de satisfacción" dentro de Configuración ERP: tarjetas, variables y textos. */
class SurveySettingController extends Controller
{
    public const REPORT_TYPES = [
        'chemical_analysis', 'maintenance_preventive', 'maintenance_corrective', 'inspection',
        'cleaning', 'calibration', 'activity_report', 'custom',
    ];

    private function back(string $msg): RedirectResponse
    {
        return redirect(route('admin.erp-settings.index') . '#encuesta')->with('success', $msg);
    }

    // ── Tarjetas ────────────────────────────────────────────────────────────

    private function cardRules(): array
    {
        return [
            'label' => 'required|string|max:60',
            'stars' => 'required|integer|between:1,5',
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public function storeCard(Request $request): RedirectResponse
    {
        $data = $request->validate($this->cardRules());
        $data['is_active'] = true;
        $data['sort_order'] = (int) SurveyCard::max('sort_order') + 1;
        SurveyCard::create($data);

        return $this->back('Tarjeta agregada.');
    }

    public function updateCard(Request $request, SurveyCard $card): RedirectResponse
    {
        $data = $request->validate($this->cardRules());
        $data['is_active'] = $request->boolean('is_active');
        $card->update($data);

        return $this->back('Tarjeta actualizada.');
    }

    public function destroyCard(SurveyCard $card): RedirectResponse
    {
        $card->delete(); // las calificaciones guardan snapshot del texto; la FK queda en null

        return $this->back('Tarjeta eliminada.');
    }

    public function moveCard(Request $request, SurveyCard $card): RedirectResponse
    {
        $this->move($card, SurveyCard::query(), $request->input('direction'));

        return $this->back('Orden actualizado.');
    }

    // ── Variables de la encuesta ────────────────────────────────────────────

    public function storeQuestion(Request $request): RedirectResponse
    {
        $data = $request->validate(['label' => 'required|string|max:120', 'kind' => 'nullable|in:positive,negative']);
        $data['kind'] = $data['kind'] ?? SurveyQuestion::KIND_POSITIVE;
        SurveyQuestion::create($data + ['is_active' => true, 'sort_order' => (int) SurveyQuestion::max('sort_order') + 1]);

        return $this->back('Variable agregada.');
    }

    public function updateQuestion(Request $request, SurveyQuestion $question): RedirectResponse
    {
        $data = $request->validate(['label' => 'required|string|max:120', 'kind' => 'nullable|in:positive,negative']);
        if (empty($data['kind'])) {
            unset($data['kind']); // sin tipo en la petición: conserva el actual
        }
        $question->update($data + ['is_active' => $request->boolean('is_active')]);

        return $this->back('Variable actualizada.');
    }

    public function destroyQuestion(SurveyQuestion $question): RedirectResponse
    {
        $question->delete();

        return $this->back('Variable eliminada.');
    }

    public function moveQuestion(Request $request, SurveyQuestion $question): RedirectResponse
    {
        $this->move($question, SurveyQuestion::query(), $request->input('direction'));

        return $this->back('Orden actualizado.');
    }

    /** Intercambia posición con el vecino (up|down) y normaliza sort_order. */
    private function move(Model $item, $query, ?string $direction): void
    {
        $ordered = $query->orderBy('sort_order')->orderBy('id')->get()->values();
        $i = $ordered->search(fn ($r) => $r->id === $item->id);
        $j = $direction === 'up' ? $i - 1 : ($direction === 'down' ? $i + 1 : $i);

        if ($i === false || $j < 0 || $j >= $ordered->count() || $j === $i) {
            return;
        }

        $tmp = $ordered[$i];
        $ordered[$i] = $ordered[$j];
        $ordered[$j] = $tmp;

        foreach ($ordered as $pos => $row) {
            if ((int) $row->sort_order !== $pos + 1) {
                $row->forceFill(['sort_order' => $pos + 1])->save();
            }
        }
    }

    // ── Textos y ajustes ────────────────────────────────────────────────────

    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'survey_title'         => 'required|string|max:120',
            'survey_subtitle'      => 'nullable|string|max:255',
            'survey_questions_title' => 'nullable|string|max:255',
            'survey_comment_label' => 'required|string|max:120',
            'survey_submit_label'  => 'required|string|max:60',
            'survey_thanks_message' => 'required|string|max:500',
            'survey_good_title'    => 'nullable|string|max:120',
            'survey_bad_title'     => 'nullable|string|max:120',
            'survey_thanks_good'   => 'nullable|string|max:500',
            'survey_thanks_bad'    => 'nullable|string|max:500',
            'survey_name_label'    => 'required|string|max:60',
            'service_by_type'      => 'nullable|array',
            'service_by_type.*'    => 'nullable|integer|exists:service_pages,id',
        ]);

        $map = [];
        foreach ((array) $request->input('service_by_type', []) as $type => $pageId) {
            if ((int) $pageId > 0 && in_array($type, self::REPORT_TYPES, true)) {
                $map[$type] = (int) $pageId;
            }
        }

        $values = [
            'survey.enabled'                 => [$request->boolean('survey_enabled') ? '1' : '0', 'boolean'],
            'survey.title'                   => [$request->input('survey_title'), 'string'],
            'survey.subtitle'                => [$request->input('survey_subtitle') ?? '', 'string'],
            'survey.questions_title'         => [$request->input('survey_questions_title') ?? '', 'string'],
            'survey.comment_label'           => [$request->input('survey_comment_label'), 'string'],
            'survey.submit_label'            => [$request->input('survey_submit_label'), 'string'],
            'survey.thanks_message'          => [$request->input('survey_thanks_message'), 'string'],
            'survey.name_label'              => [$request->input('survey_name_label'), 'string'],
            'survey.good_title'              => [$request->input('survey_good_title') ?? '', 'string'],
            'survey.bad_title'               => [$request->input('survey_bad_title') ?? '', 'string'],
            'survey.thanks_good'             => [$request->input('survey_thanks_good') ?? '', 'string'],
            'survey.thanks_bad'              => [$request->input('survey_thanks_bad') ?? '', 'string'],
            'survey.default_service_by_type' => [json_encode($map), 'json'],
        ];

        foreach ($values as $key => [$value, $type]) {
            Setting::updateOrCreate(['key' => $key], [
                'value' => $value, 'type' => $type, 'group_name' => 'survey', 'updated_by_user_id' => auth()->id(),
            ]);
            Setting::set($key, $value, auth()->id()); // invalida la memoización de Setting::get
        }

        return $this->back('Ajustes de la encuesta guardados.');
    }

    /** Datos para el partial de Configuración ERP. */
    public static function panelData(): array
    {
        return [
            'cards'        => SurveyCard::orderBy('sort_order')->orderBy('id')->get(),
            'questions'    => SurveyQuestion::orderBy('sort_order')->orderBy('id')->get(),
            'servicePages' => ServicePage::orderBy('name')->get(['id', 'name']),
            'texts'        => collect(array_keys(SatisfactionSurveyService::DEFAULT_SETTINGS))
                ->mapWithKeys(fn ($k) => [$k => app(SatisfactionSurveyService::class)->text($k)])->all(),
            'enabled'      => app(SatisfactionSurveyService::class)->enabled(),
            'map'          => app(SatisfactionSurveyService::class)->defaultServiceMap(),
            'reportTypes'  => self::REPORT_TYPES,
        ];
    }
}
