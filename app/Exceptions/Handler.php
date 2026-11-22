<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * API móvil: toda respuesta de error (401 no autenticado, 403, 404 de
     * modelo o de ruta inexistente, 422 de validación, 429...) se entrega como
     * JSON aunque el cliente no mande `Accept: application/json`, y nunca como
     * redirect al login web.
     */
    protected function shouldReturnJson($request, Throwable $e)
    {
        return $request->is('api/mobile/*') || parent::shouldReturnJson($request, $e);
    }
}
