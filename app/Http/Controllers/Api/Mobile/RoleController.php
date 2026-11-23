<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Roles con su número de usuarios y matriz de permisos (solo lectura, permiso
 * roles,view como el panel). Son pocos: se devuelven todos, sin paginar.
 */
class RoleController extends Controller
{
    /** GET /api/mobile/v1/roles */
    public function index(Request $request): JsonResponse
    {
        $roles = Role::query()
            ->withCount('users')
            ->with('permissions:id,module,action')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => RoleResource::collection($roles)->resolve($request),
            'meta' => ['total' => $roles->count()],
        ]);
    }
}
