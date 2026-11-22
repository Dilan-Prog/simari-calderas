<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

/**
 * Autenticación de la API móvil del personal (tokens Sanctum de App\Models\User).
 */
class AuthController extends Controller
{
    /**
     * POST /api/mobile/v1/login — emite un token personal con ability 'mobile'.
     * El nombre del token es el `device_name` que envía la app.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'       => ['required', 'string', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::with('role')->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales incorrectas'], 422);
        }

        // Solo se bloquea a los usuarios SUSPENDIDOS. El login web no revisa
        // users.status y los usuarios nuevos nacen como 'inactive' por defecto,
        // así que exigir 'active' dejaría fuera de la app a gente que hoy sí
        // entra al panel. Solo se revela después de validar la contraseña.
        if ($user->status === 'suspended') {
            return response()->json([
                'message' => 'Tu cuenta está desactivada. Contacta al administrador.',
            ], 403);
        }

        $token = $user->createToken($data['device_name'], ['mobile']);

        return response()->json([
            'token' => $token->plainTextToken,
            'user'  => (new UserResource($user))->resolve($request),
        ]);
    }

    /**
     * POST /api/mobile/v1/logout — revoca solo el token con el que se hizo la petición.
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }

    /**
     * GET /api/mobile/v1/me — usuario autenticado con su rol y permisos.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('role');

        return response()->json([
            'user' => (new UserResource($user))->resolve($request),
        ]);
    }
}
