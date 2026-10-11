<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ServiceLog;
use App\Models\ServiceType;
use App\Models\TechnicalService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** ERP Fase 5 — Calendario de servicios técnicos (mes / semana / día) con reprogramación. */
class ServiceCalendarController extends Controller
{
    public function index(Request $request)
    {
        $techIds = DB::table('service_technicians')->distinct()->pluck('user_id');
        $technicians = User::whereIn('id', $techIds)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $projects = class_exists(Project::class) && Schema::hasTable('projects')
            ? Project::orderByDesc('id')->limit(300)->get(['id', 'project_number', 'name'])
            : collect();
        $types = ServiceType::orderBy('name')->get(['id', 'name']);

        $user = auth()->user();
        $canEdit = $user->isAdmin() || $user->hasPermission('service-calendar', 'edit');
        $canCreate = $user->isAdmin() || $user->hasPermission('technical-services', 'create');

        return view('admin.service-calendar.index', [
            'technicians' => $technicians,
            'projects'    => $projects,
            'types'       => $types,
            'statuses'    => $this->statuses(),
            'canEdit'     => $canEdit,
            'canCreate'   => $canCreate,
            'filters'     => [
                'technician_id'   => (string) $request->get('technician_id', ''),
                'project_id'      => (string) $request->get('project_id', ''),
                'service_type_id' => (string) $request->get('service_type_id', ''),
                'status'          => (string) $request->get('status', ''),
            ],
        ]);
    }

    /** Eventos entre `start` y `end` (YYYY-MM-DD, inclusive). */
    public function events(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start'           => 'required|date',
            'end'             => 'required|date|after_or_equal:start',
            'technician_id'   => 'nullable|integer',
            'project_id'      => 'nullable|integer',
            'service_type_id' => 'nullable|integer',
            'status'          => 'nullable|string|max:30',
        ]);

        $start = Carbon::parse($data['start'])->startOfDay();
        $end = Carbon::parse($data['end'])->startOfDay();
        if ($start->diffInDays($end) > 92) {
            return response()->json(['message' => 'Rango demasiado amplio.'], 422);
        }

        $services = TechnicalService::query()
            ->with(['customer:id,first_name,last_name,company', 'serviceType:id,name', 'assignedTechnicians:id,first_name,last_name'])
            ->whereNotNull('service_date')
            ->whereDate('service_date', '>=', $start->toDateString())
            ->whereDate('service_date', '<=', $end->toDateString())
            ->when(!empty($data['technician_id']), fn ($q) => $q->byTechnician((int) $data['technician_id']))
            ->when(!empty($data['project_id']), fn ($q) => $q->where('project_id', (int) $data['project_id']))
            ->when(!empty($data['service_type_id']), fn ($q) => $q->where('service_type_id', (int) $data['service_type_id']))
            ->when(!empty($data['status']), fn ($q) => $q->where('status', $data['status']))
            ->orderBy('service_date')->orderBy('service_time')
            ->limit(1000)
            ->get();

        $projectNumbers = $services->pluck('project_id')->filter()->unique()->values();
        $projectMap = ($projectNumbers->isNotEmpty() && Schema::hasTable('projects'))
            ? Project::whereIn('id', $projectNumbers)->pluck('project_number', 'id')
            : collect();

        $events = $services->map(function (TechnicalService $s) use ($projectMap) {
            $time = $s->service_time ? substr((string) $s->service_time, 0, 5) : null;
            $duration = (float) ($s->estimated_duration ?: 1);
            $endTime = null;
            if ($time) {
                $endTime = Carbon::createFromFormat('H:i', $time)->addMinutes((int) round($duration * 60))->format('H:i');
            }
            $customer = $s->customer ? ($s->customer->company ?: trim($s->customer->first_name . ' ' . $s->customer->last_name)) : null;

            return [
                'id'          => $s->id,
                'number'      => $s->service_number ?: $s->service_code,
                'title'       => ($s->service_number ?: $s->service_code) . ($customer ? ' · ' . $customer : ''),
                'customer'    => $customer,
                'date'        => $s->service_date->format('Y-m-d'),
                'time'        => $time,
                'end_time'    => $endTime,
                'duration'    => $duration,
                'status'      => $s->status,
                'status_label' => $s->status_label,
                'priority'    => $s->priority ?: 'normal',
                'priority_label' => $s->priority_label,
                'type'        => $s->serviceType?->name,
                'project'     => $s->project_id ? ($projectMap[$s->project_id] ?? '#' . $s->project_id) : null,
                'technicians' => $s->assignedTechnicians->map(fn ($u) => trim($u->first_name . ' ' . $u->last_name))->values(),
                'url'         => route('admin.technical-services.show', $s->id),
                'editable'    => $s->isEditable() || $s->status === 'in_progress',
            ];
        })->values();

        return response()->json(['events' => $events]);
    }

    /** Reprograma un servicio (arrastrar y soltar). */
    public function reschedule(Request $request, TechnicalService $service): JsonResponse
    {
        $data = $request->validate([
            'service_date' => 'required|date',
            'service_time' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/'],
        ]);

        if (in_array($service->status, ['completed', 'cancelled'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede reprogramar un servicio en estado ' . $service->status_label . '.',
            ], 422);
        }

        $oldDate = $service->service_date?->format('Y-m-d');
        $oldTime = $service->service_time ? substr((string) $service->service_time, 0, 5) : null;
        $newDate = Carbon::parse($data['service_date'])->format('Y-m-d');
        $newTime = array_key_exists('service_time', $data) && $data['service_time']
            ? substr($data['service_time'], 0, 5)
            : $oldTime;

        if ($newDate === $oldDate && $newTime === $oldTime) {
            return response()->json(['success' => true, 'changed' => false, 'service_date' => $newDate, 'service_time' => $newTime]);
        }

        DB::transaction(function () use ($service, $newDate, $newTime, $oldDate, $oldTime) {
            $update = ['service_date' => $newDate, 'service_time' => $newTime];
            if ($service->week_number) {
                $update['week_number'] = Carbon::parse($newDate)->isoWeek;
            }
            $service->update($update);

            ServiceLog::create([
                'service_id'           => $service->id,
                'action'               => 'rescheduled',
                'comment'              => 'Reprogramado desde el calendario: ' . ($oldDate ?? '—') . ' ' . ($oldTime ?? '') . ' → ' . $newDate . ' ' . ($newTime ?? ''),
                'performed_by_user_id' => auth()->id(),
            ]);
        });

        return response()->json([
            'success'      => true,
            'changed'      => true,
            'message'      => 'Servicio reprogramado.',
            'service_date' => $newDate,
            'service_time' => $newTime,
        ]);
    }

    private function statuses(): array
    {
        return [
            'draft' => 'Borrador', 'scheduled' => 'Programado', 'in_progress' => 'En proceso',
            'completed' => 'Completado', 'cancelled' => 'Cancelado',
        ];
    }
}
