<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\QuoteTerm;
use App\Models\Setting;
use App\Models\ServiceType;
use App\Models\ServiceReportType;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;

class ErpSettingController extends Controller
{
    public function index()
    {
        // Grupo dedicado 'erp' — separado de Configuración del Sitio general
        // y de 'integraciones' (que tiene su propio manejo de credenciales).
        // Por ahora no hay ningún ajuste erp.* sembrado; la vista debe
        // mostrar un estado vacío razonable en ese caso.
        // Los términos del cotizador tienen su propio panel (textarea); fuera de la lista
        // genérica para que un input de una línea no les quite los saltos de línea.
        $settings = Setting::where('group_name', 'erp')
            ->where('key', '!=', self::QUOTE_TERMS_KEY)
            ->orderBy('key')->get();

        $serviceTypes = ServiceType::orderBy('name')->get();

        // service_report_types lo migra otro agente en paralelo — si esa
        // migración todavía no corrió, no tumbamos toda la pantalla por eso.
        $serviceReportTypes = Schema::hasTable('service_report_types')
            ? ServiceReportType::orderBy('sort_order')->orderBy('label')->get()
            : collect();

        $quoteTerms = (string) Setting::get(self::QUOTE_TERMS_KEY, '');
        $quoteTermOptions = QuoteTerm::ordered()->get();

        return view('admin.erp-settings.index', compact('settings', 'serviceTypes', 'serviceReportTypes', 'quoteTerms', 'quoteTermOptions'));
    }

    // ── Términos y condiciones del cotizador ────────────────────────────────

    /** Texto que se precarga en cada cotización nueva (se puede ajustar por cotización). */
    public const QUOTE_TERMS_KEY = 'erp.quote_terms_default';

    public function updateQuoteTerms(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'quote_terms' => 'nullable|string|max:20000',
        ]);

        // Fila creada la primera vez que se guarda; Setting::set() no fija el grupo.
        Setting::updateOrCreate(['key' => self::QUOTE_TERMS_KEY], [
            'value'              => trim((string) ($validated['quote_terms'] ?? '')),
            'type'               => 'string',
            'group_name'         => 'erp',
            'updated_by_user_id' => auth()->id(),
        ]);

        return redirect()->to(route('admin.erp-settings.index') . '#quote-terms')
            ->with('success', 'Términos y condiciones del cotizador actualizados. Se precargarán en las cotizaciones nuevas.');
    }

    // ── Catálogo de términos y condiciones (opciones que se marcan al cotizar) ──

    private function quoteTermData(Request $request): array
    {
        $v = $request->validate([
            'title'      => 'required|string|max:150',
            'body'       => 'required|string|max:20000',
            'applies_to' => 'required|in:product,service,both',
            'sort_order' => 'nullable|integer|min:0|max:100000',
        ]);
        $v['is_default'] = $request->boolean('is_default');
        $v['is_active'] = $request->boolean('is_active');
        $v['title'] = trim($v['title']);
        $v['body'] = trim($v['body']);

        return $v;
    }

    private function quoteTermsBack(string $msg): RedirectResponse
    {
        return redirect()->to(route('admin.erp-settings.index') . '#quote-terms')->with('success', $msg);
    }

    public function storeQuoteTerm(Request $request): RedirectResponse
    {
        $data = $this->quoteTermData($request);
        $data['sort_order'] = $data['sort_order'] ?? ((int) QuoteTerm::max('sort_order') + 1);
        QuoteTerm::create($data);

        return $this->quoteTermsBack('Término agregado al catálogo.');
    }

    public function updateQuoteTerm(Request $request, QuoteTerm $quoteTerm): RedirectResponse
    {
        $data = $this->quoteTermData($request);
        $data['sort_order'] = $data['sort_order'] ?? $quoteTerm->sort_order;
        $quoteTerm->update($data);

        return $this->quoteTermsBack('Término actualizado. Las cotizaciones ya creadas conservan su texto.');
    }

    /** Sube/baja un término intercambiando posición; renumera para evitar empates. */
    public function moveQuoteTerm(Request $request, QuoteTerm $quoteTerm): RedirectResponse
    {
        $request->validate(['direction' => 'required|in:up,down']);
        $list = QuoteTerm::ordered()->get()->values();
        $i = $list->search(fn ($t) => $t->id === $quoteTerm->id);
        $j = $request->input('direction') === 'up' ? $i - 1 : $i + 1;
        if ($j >= 0 && $j < $list->count()) {
            $moved = $list->splice($i, 1)->first();
            $list->splice($j, 0, [$moved]);
            foreach ($list->values() as $pos => $t) {
                if ((int) $t->sort_order !== $pos) {
                    $t->update(['sort_order' => $pos]);
                }
            }
        }

        return $this->quoteTermsBack('Orden actualizado.');
    }

    public function destroyQuoteTerm(QuoteTerm $quoteTerm): RedirectResponse
    {
        $quoteTerm->delete();

        return $this->quoteTermsBack('Término eliminado. Las cotizaciones ya creadas conservan su texto.');
    }

    public function update(Request $request): RedirectResponse
    {
        // Mismo patrón que SettingController::update(): solo se tocan claves
        // que ya existen en la tabla settings con group_name='erp'.
        $values = $request->input('values', []);

        $existingKeys = Setting::where('group_name', 'erp')
            ->where('key', '!=', self::QUOTE_TERMS_KEY)
            ->whereIn('key', array_keys($values))
            ->pluck('key')->all();

        foreach ($values as $key => $value) {
            if (!in_array($key, $existingKeys, true)) {
                continue;
            }

            Setting::set($key, $value, auth()->id());
        }

        return back()->with('success', 'Configuración ERP actualizada.');
    }

    // ── Tipos de Servicio (Servicios Técnicos) ──────────────────────────────

    public function storeServiceType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:service_types,name',
        ]);

        ServiceType::create(['name' => $validated['name'], 'is_active' => true]);

        return back()->with('success', 'Tipo de servicio agregado.');
    }

    public function updateServiceType(Request $request, ServiceType $serviceType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:service_types,name,' . $serviceType->id,
        ]);

        $serviceType->update([
            'name'      => $validated['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Tipo de servicio actualizado.');
    }

    public function destroyServiceType(ServiceType $serviceType): RedirectResponse
    {
        // services.service_type_id tiene FK ON DELETE RESTRICT hacia
        // service_types (ver 2026_04_19_173326_create_services_table.php) —
        // borrar un tipo en uso lanzaría una QueryException. En vez de dejar
        // que el usuario se tope con un error 500, si hay servicios técnicos
        // usando este tipo simplemente lo desactivamos.
        if ($serviceType->services()->exists()) {
            $serviceType->update(['is_active' => false]);

            return back()->with('success', 'El tipo de servicio está en uso por servicios técnicos existentes; se desactivó en lugar de eliminarse.');
        }

        $serviceType->delete();

        return back()->with('success', 'Tipo de servicio eliminado.');
    }

    // ── Tipos de Reporte (Reportes de Servicio) ─────────────────────────────

    public function storeServiceReportType(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key'           => 'required|string|max:60|alpha_dash|unique:service_report_types,key',
            'label'         => 'required|string|max:100',
            'form_behavior' => 'required|in:measurements,activity,custom',
        ]);

        ServiceReportType::create([
            'key'           => $validated['key'],
            'label'         => $validated['label'],
            'form_behavior' => $validated['form_behavior'],
            'is_active'     => true,
            'sort_order'    => (int) ServiceReportType::max('sort_order') + 1,
        ]);

        return back()->with('success', 'Tipo de reporte agregado.');
    }

    public function updateServiceReportType(Request $request, ServiceReportType $serviceReportType): RedirectResponse
    {
        // form_behavior no es editable desde esta UI en este pase: cambiarlo
        // para un tipo ya en uso podría romper reportes existentes que
        // dependen de qué sub-formulario del wizard se les muestra.
        $validated = $request->validate([
            'label' => 'required|string|max:100',
        ]);

        $serviceReportType->update([
            'label'     => $validated['label'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Tipo de reporte actualizado.');
    }

    public function destroyServiceReportType(ServiceReportType $serviceReportType): RedirectResponse
    {
        // service_reports.service_type es un enum de valores fijos, no una
        // FK hacia service_report_types, así que borrar aquí no puede tumbar
        // una constraint de base de datos. Aun así, si ya hay reportes
        // guardados con esta key, desactivar es más seguro que eliminar el
        // catálogo bajo sus pies.
        $inUse = \App\Models\ServiceReport::where('service_type', $serviceReportType->key)->exists();

        if ($inUse) {
            $serviceReportType->update(['is_active' => false]);

            return back()->with('success', 'El tipo de reporte está en uso por reportes existentes; se desactivó en lugar de eliminarse.');
        }

        $serviceReportType->delete();

        return back()->with('success', 'Tipo de reporte eliminado.');
    }
}
