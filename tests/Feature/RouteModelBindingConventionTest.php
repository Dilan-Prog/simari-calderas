<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Red de seguridad para la convención de rutas de Automatizaciones
 * (Workflows) que anidan 2+ segmentos de modelo, p.ej.
 * `automatizaciones/{workflow}/pasos/{step}`.
 *
 * Laravel resuelve el route-model-binding implícito emparejando el
 * nombre del segmento de la URI (`{step}`) con el nombre del parámetro
 * del método del controller (`WorkflowStep $step`). Si alguien agrega
 * un nuevo segmento a una de estas rutas, o renombra un parámetro en
 * el controller sin actualizar la ruta (o viceversa), Laravel no avisa
 * en tiempo de compilación: el binding simplemente no se resuelve (o
 * resuelve el modelo equivocado) y el bug solo aparece en producción.
 *
 * Este test recorre, por reflexión, todas las rutas registradas bajo
 * el prefijo `automatizaciones/` con 2 o más parámetros de ruta y
 * verifica que la firma del método del controller declare un
 * parámetro con el mismo nombre para cada segmento de la URI.
 *
 * Deliberadamente acotado a las rutas de Automatizaciones (no a todas
 * las rutas del proyecto): una versión genérica que recorriera *todas*
 * las rutas produce falsos positivos con controllers de framework que
 * consumen los parámetros de ruta indirectamente (p.ej.
 * VerifyEmailController::__invoke(EmailVerificationRequest $request),
 * donde {id}/{hash} se leen dentro del FormRequest y nunca aparecen
 * como parámetros del método).
 *
 * Ver también: `app/Services/WorkflowEngineService.php` (comentario en
 * `nextSiblingOf()`) y `resources/js/admin/canvas/graph/toFlow.js`
 * (comentario en `buildPredecessorMap()`) para la otra red de
 * seguridad relacionada con este módulo (paridad motor PHP / canvas
 * React), cubierta por `tests/Feature/Workflows/CanvasEngineParityTest.php`.
 */
class RouteModelBindingConventionTest extends TestCase
{
    /**
     * Prefijo de URI (sin el prefijo global `admin/`) bajo el que
     * viven todas las rutas del módulo de Automatizaciones.
     */
    private const ROUTE_URI_PREFIX = 'automatizaciones/';

    public function test_workflow_routes_with_multiple_model_segments_declare_all_of_them_in_the_controller_signature(): void
    {
        $checked = [];

        foreach (Route::getRoutes() as $route) {
            $uri = ltrim($route->uri(), '/');
            $uri = preg_replace('#^admin/#', '', $uri);

            if (! str_starts_with($uri, self::ROUTE_URI_PREFIX)) {
                continue;
            }

            $paramNames = $this->routeParameterNames($route->uri());

            if (count($paramNames) < 2) {
                continue;
            }

            $action = $route->getActionName();

            // Las rutas de este módulo solo usan controllers, nunca
            // closures; si alguna vez se agrega una closure aquí, que
            // el test lo señale explícitamente en vez de fallar de
            // forma críptica en la reflexión.
            $this->assertStringContainsString(
                '@',
                $action,
                "La ruta [{$route->methods()[0]} {$uri}] no usa un controller de clase (¿closure?). "
                    ."Este test asume convención controller@method para las rutas de Automatizaciones."
            );

            [$controllerClass, $method] = explode('@', $action);

            $this->assertTrue(
                method_exists($controllerClass, $method),
                "El método [{$method}] declarado en la ruta [{$uri}] no existe en [{$controllerClass}]."
            );

            $reflection = new ReflectionMethod($controllerClass, $method);
            $methodParamNames = array_map(
                fn ($param) => $param->getName(),
                $reflection->getParameters()
            );

            foreach ($paramNames as $paramName) {
                $this->assertContains(
                    $paramName,
                    $methodParamNames,
                    "La ruta [{$uri}] declara el segmento de modelo {{$paramName}}, pero "
                        ."[{$controllerClass}@{$method}] no tiene un parámetro llamado \${$paramName} "
                        ."en su firma. Esto rompe el route-model-binding implícito de Laravel: "
                        .'revisa que el nombre del segmento de la URI coincida exactamente con el '
                        .'nombre del parámetro del método.'
                );
            }

            $checked[] = $uri;
        }

        // Si no se encontró ninguna ruta que cumpliera el criterio, el
        // test estaría pasando de forma vacía (falso positivo si el
        // prefijo de las rutas de Automatizaciones cambia algún día).
        $this->assertNotEmpty(
            $checked,
            'No se encontró ninguna ruta bajo "'.self::ROUTE_URI_PREFIX.'" con 2+ segmentos de modelo. '
                .'¿Cambió el prefijo de las rutas de Automatizaciones (routes/admin.php)? '
                .'Actualiza ROUTE_URI_PREFIX en este test.'
        );

        // Ancla explícita a las 3 rutas anidadas conocidas del módulo
        // (pasos/{step}, variables/{variable}, notas/{note}), cada una
        // con verbos PUT y DELETE, para que un cambio de convención se
        // note también en el número de rutas cubiertas.
        $this->assertGreaterThanOrEqual(6, count($checked));
    }

    /**
     * @return array<int, string>
     */
    private function routeParameterNames(string $uri): array
    {
        preg_match_all('/\{([^}]+)\}/', $uri, $matches);

        return array_map(
            fn ($name) => rtrim($name, '?'),
            $matches[1]
        );
    }
}
