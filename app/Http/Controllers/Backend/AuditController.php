<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = SystemLog::with('performedBy')->orderByDesc('performed_at');

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('performed_by_user_id')) {
            $query->where('performed_by_user_id', $request->input('performed_by_user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('performed_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('performed_at', '<=', $request->input('date_to'));
        }

        $logs = $query->paginate(30)->withQueryString();

        // Listas para los <select> de filtro — distinct() sobre la tabla
        // real, no hardcodeadas, para que se mantengan al día solas según
        // qué modelos realmente escriben logs.
        $entityTypes = SystemLog::query()->distinct()->orderBy('entity_type')->pluck('entity_type');
        $actions = SystemLog::query()->distinct()->orderBy('action')->pluck('action');
        $users = User::orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('admin.audit.index', compact('logs', 'entityTypes', 'actions', 'users'));
    }
}
