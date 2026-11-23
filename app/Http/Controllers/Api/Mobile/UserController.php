<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\StaffUserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lista de usuarios del personal para la app (solo lectura, permiso users,view
 * como el panel). Campos en lista blanca: ver StaffUserResource.
 */
class UserController extends Controller
{
    /** GET /api/mobile/v1/usuarios?search=&status=&role_id=&page=&per_page= */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'search'   => ['nullable', 'string', 'max:100'],
            'status'   => ['nullable', 'in:active,inactive,suspended'],
            'role_id'  => ['nullable', 'integer'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = User::query()
            ->with('role:id,name_role,name_role_es')
            ->withMax('tokens', 'last_used_at')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('id');

        $search = trim((string) ($data['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function (Builder $q) use ($like) {
                $q->where('first_name', 'like', $like)
                  ->orWhere('last_name', 'like', $like)
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])
                  ->orWhere('email', 'like', $like)
                  ->orWhere('position', 'like', $like);
            });
        }
        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (! empty($data['role_id'])) {
            $query->where('role_id', $data['role_id']);
        }

        $paginator = $query->paginate((int) ($data['per_page'] ?? 50));

        return response()->json([
            'data' => StaffUserResource::collection($paginator->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }
}
