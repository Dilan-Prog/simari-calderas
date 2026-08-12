<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
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
        $settings = Setting::where('group_name', 'erp')->orderBy('key')->get();

        $serviceTypes = ServiceType::orderBy('name')->get();

        // service_report_types lo migra otro agente en paralelo — si esa
        // migración todavía no corrió, no tumbamos toda la pantalla por eso.
        $serviceReportTypes = Schema::hasTable('service_report_types')
            ? ServiceReportType::orderBy('sort_order')->orderBy('label')->get()
            : collect();

        return view('admin.erp-settings.index', compact('settings', 'serviceTypes', 'serviceReportTypes'));
    }

    public function update(Request $request): RedirectResponse
    {
        // Mismo patrón que SettingController::update(): solo se tocan claves
        // que ya existen en la tabla settings con group_name='erp'.
        $values = $request->input('values', []);

        $existingKeys = Setting::where('group_name', 'erp')
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
    //
    // NOTA: estos tres métodos quedan listos pero sin rutas asignadas todavía.
    // El agente de rutas cerró su contrato sin estas 3 adicionales:
    //   POST   /admin/erp/configuracion/tipos-reporte              -> storeServiceReportType
    //   PUT    /admin/erp/configuracion/tipos-reporte/{serviceReportType} -> updateServiceReportType
    //   DELETE /admin/erp/configuracion/tipos-reporte/{serviceReportType} -> destroyServiceReportType
    // con nombres sugeridos admin.erp-settings.report-types.store/update/destroy
    // y middleware permission:erp-settings(,create/,edit/,delete). Falta
    // agregarlas a routes/admin.php.

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
