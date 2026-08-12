<?php

namespace Tests\Feature\Workflows;

use App\Console\Commands\DebugWorkflowNextSteps;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Console\OutputStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/**
 * Fase 10, punto 2 [ALTA]: "Divergencia silenciosa entre el motor PHP y el
 * canvas React".
 *
 * app/Services/WorkflowEngineService.php (método privado nextSiblingOf())
 * decide en tiempo de ejecución cuál es el "siguiente step" de una
 * inscripción real. resources/js/admin/canvas/graph/toFlow.js
 * (buildPredecessorMap()) reproduce manualmente la misma semántica en JS,
 * en sentido inverso, sólo para dibujar las conexiones del canvas — sin
 * ningún test que verifique que ambas implementaciones coinciden. Ya hubo
 * 3 bugs reales de bifurcación en este mecanismo durante la Fase 5.
 *
 * Este test no ejecuta JS: fija en PHP, contra 3 árboles de WorkflowStep
 * representativos, la secuencia exacta que nextSiblingOf() produce (vía el
 * comando de depuración `workflows:debug-next-steps`, wrapper de solo
 * lectura definido en WorkflowEngineService::nextStepSequence()). Si algún
 * día se cambia la lógica de nextSiblingOf() sin actualizar toFlow.js (o
 * viceversa), este test es la red de seguridad que debe fallar primero:
 * cualquier cambio de comportamiento del motor que no esté reflejado aquí
 * (con las aserciones actualizadas a propósito, como en
 * WorkflowEngineTest.php) es la señal de que toFlow.js también necesita
 * revisión manual.
 */
class CanvasEngineParityTest extends TestCase
{
    use RefreshDatabase;

    private function makeWorkflow(array $overrides = []): Workflow
    {
        return Workflow::create(array_merge([
            'name'                 => 'Workflow de paridad canvas/motor',
            'type'                 => 'customer',
            'enrollment_trigger'   => ['type' => 'manual'],
            'is_active'            => true,
            'reenrollment_allowed' => false,
        ], $overrides));
    }

    private function makeStep(Workflow $workflow, array $overrides = []): WorkflowStep
    {
        return WorkflowStep::create(array_merge([
            'workflow_id'    => $workflow->id,
            'parent_step_id' => null,
            'order'          => 0,
            'step_type'      => 'action',
            'action_type'    => 'notify_rep',
            'action_config'  => [],
        ], $overrides));
    }

    /**
     * Corre el comando de depuración directamente con input/output
     * explícitos y decodifica su salida JSON como step_id => next_step_id.
     *
     * No se usa Artisan::call()/Artisan::output(): RefreshDatabase corre su
     * migración inicial vía el helper artisan() de PHPUnit
     * (Illuminate\Foundation\Testing\Concerns\InteractsWithConsole), que deja
     * ligada en el contenedor una instancia MOCK de OutputStyle (comportamiento
     * estándar de testing de Laravel para no ensuciar la salida de la consola
     * durante los tests) — cualquier comando posterior resuelto por el
     * contenedor terminaría escribiendo silenciosamente a ese mock en vez de
     * a un buffer real. Para evitarlo, se construye un OutputStyle real
     * envolviendo un BufferedOutput y se pasa explícitamente a
     * Command::run(): como ya es instancia de OutputStyle, Laravel no vuelve
     * a resolverlo del contenedor (ver Illuminate\Console\Command::run()).
     */
    private function nextStepSequenceFor(Workflow $workflow): array
    {
        $command = app(DebugWorkflowNextSteps::class);
        $command->setLaravel($this->app);

        $input = new ArrayInput(['workflow' => (string) $workflow->id]);
        $buffer = new BufferedOutput();
        $output = new OutputStyle($input, $buffer);

        $exitCode = $command->run($input, $output);

        $this->assertSame(0, $exitCode, 'El comando de depuración debe salir con código 0');

        $raw = trim($buffer->fetch());
        $rows = json_decode($raw, true);
        $this->assertIsArray($rows, 'La salida del comando debe ser JSON válido: ' . $raw);

        $map = [];
        foreach ($rows as $row) {
            $map[$row['step_id']] = $row['next_step_id'];
        }

        return $map;
    }

    /**
     * Árbol 1: cadena lineal simple, 3 steps raíz sin ramas.
     */
    public function test_linear_tree_next_step_sequence(): void
    {
        $workflow = $this->makeWorkflow();

        $step1 = $this->makeStep($workflow, ['order' => 0]);
        $step2 = $this->makeStep($workflow, ['order' => 1]);
        $step3 = $this->makeStep($workflow, ['order' => 2]);

        $sequence = $this->nextStepSequenceFor($workflow);

        $this->assertSame($step2->id, $sequence[$step1->id]);
        $this->assertSame($step3->id, $sequence[$step2->id]);
        $this->assertNull($sequence[$step3->id]);
    }

    /**
     * Árbol 2: condición con dos ramas, un step por rama. Verifica que
     * cada rama queda aislada de la otra por branch_key (ninguna de las
     * dos "ve" a la otra como su siguiente step).
     */
    public function test_condition_two_branches_next_step_sequence(): void
    {
        $workflow = $this->makeWorkflow();

        $condition = $this->makeStep($workflow, [
            'order'            => 0,
            'step_type'        => 'condition',
            'action_type'      => null,
            'branch_condition' => ['field' => 'status', 'operator' => 'equals', 'value' => 'active'],
        ]);

        $yesChild = $this->makeStep($workflow, [
            'parent_step_id' => $condition->id,
            'order'          => 0,
            'branch_key'     => 'yes',
        ]);

        $noChild = $this->makeStep($workflow, [
            'parent_step_id' => $condition->id,
            'order'          => 0,
            'branch_key'     => 'no',
        ]);

        $sequence = $this->nextStepSequenceFor($workflow);

        // La condición misma no tiene más steps raíz después de ella.
        $this->assertNull($sequence[$condition->id]);
        // Cada rama de un único step no tiene siguiente dentro de su propia rama.
        $this->assertNull($sequence[$yesChild->id]);
        $this->assertNull($sequence[$noChild->id]);
    }

    /**
     * Árbol 3: ramas de longitud desigual — el caso exacto del bug de
     * "sangrado entre ramas" corregido en Fase 5 (nextSiblingOf() filtraba
     * sólo por parent_step_id+order, sin branch_key, así que la primera
     * rama en terminar caía en la cabeza de la otra). La rama 'yes' tiene
     * 2 steps, la rama 'no' tiene 1. yesChild1 debe encadenar a yesChild2
     * (NO a noChild1, aunque comparta parent_step_id y tenga order mayor),
     * y noChild1 no debe encadenar a nada.
     */
    public function test_unequal_length_branches_do_not_bleed_into_each_other(): void
    {
        $workflow = $this->makeWorkflow();

        $condition = $this->makeStep($workflow, [
            'order'            => 0,
            'step_type'        => 'condition',
            'action_type'      => null,
            'branch_condition' => ['field' => 'status', 'operator' => 'equals', 'value' => 'active'],
        ]);

        $yesChild1 = $this->makeStep($workflow, [
            'parent_step_id' => $condition->id,
            'order'          => 0,
            'branch_key'     => 'yes',
        ]);

        $yesChild2 = $this->makeStep($workflow, [
            'parent_step_id' => $condition->id,
            'order'          => 1,
            'branch_key'     => 'yes',
        ]);

        $noChild1 = $this->makeStep($workflow, [
            'parent_step_id' => $condition->id,
            'order'          => 0,
            'branch_key'     => 'no',
        ]);

        $sequence = $this->nextStepSequenceFor($workflow);

        $this->assertSame($yesChild2->id, $sequence[$yesChild1->id]);
        $this->assertNull($sequence[$yesChild2->id]);
        $this->assertNull($sequence[$noChild1->id]);
    }
}
