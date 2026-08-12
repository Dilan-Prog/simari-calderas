<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Services\ReportBuilderService;
use Illuminate\Http\Request;
use Log;

class AdminController extends Controller
{
    /**
     * admin.dashboard es el destino del redirect post-login para el rol
     * 'admin' (ver AuthenticatedSessionController/RoleMiddleware) y no
     * lleva middleware permission: — lo ve cualquier usuario autenticado.
     * Por eso NO redirige a admin.dashboards.show (esa ruta y las demás
     * de DashboardController están gateadas por permission:crm-reports;
     * un usuario sin ese permiso quedaría con un 403 justo al iniciar
     * sesión). En su lugar se mantiene la vista/ruta actual, pero con el
     * gráfico alimentado por datos reales en vez de arrays hardcodeados:
     * se construye un Report no persistido (deals agrupados por mes) y
     * se pasa por ReportBuilderService, igual que hace ReportController::preview().
     */
    public function dashboard()
    {
        $report = new Report([
            'data_source' => 'deals',
            'config' => [
                'metric' => 'count',
                'group_by' => 'month',
            ],
        ]);

        $dealsByMonth = app(ReportBuilderService::class)->build($report);

        return view('admin.dashboard', compact('dealsByMonth'));
    }

    public function login() {
        return view('admin.auth.login');
    }
}
