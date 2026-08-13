<?php

namespace Tests\Feature\Workflows;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Setting;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowEnrollment;
use App\Models\WorkflowVariable;
use App\Services\WorkflowTokenResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de WorkflowTokenResolver (Fase 17 + Fase 24) -- sistema de tokens
 * `{{ }}` del motor. Cubre las 3 rutas de resolución "de datos" descritas
 * en resolveTokens() (path plano vía campo del enrollable, path con punto
 * vía data_get()/relación, variable nombrada) y los prefijos reservados
 * nuevos de Fase 24 (hoy, ahora, empresa.nombre, settings.<key>, _previous.<campo>, actor.nombre/email).
 *
 * Fase 24 cambió la firma de resolveTokens() para recibir el
 * WorkflowEnrollment completo en vez de $enrollable/$workflowId sueltos --
 * este archivo se actualizó junto con ese cambio (ver
 * WorkflowActionExecutor.php, único otro consumidor real).
 */
class WorkflowTokenResolverTest extends TestCase
{
    use RefreshDatabase;

    private function makeCustomer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'first_name'    => 'Juan',
            'last_name'     => 'Perez',
            'email'         => 'juan.' . uniqid() . '@example.com',
            'phone'         => '5555555555',
            'document_type' => 'RFC',
            'status'        => 'active',
            'source'        => 'test',
            'company'       => 'ACME',
        ], $overrides));
    }

    private function makeDeal(array $overrides = []): Deal
    {
        $pipeline = Pipeline::create(['name' => 'Pipeline de prueba', 'is_default' => true, 'is_active' => true]);
        $stage = PipelineStage::create([
            'pipeline_id'     => $pipeline->id,
            'name'            => 'Contactado',
            'slug'            => 'contactado',
            'order'           => 1,
            'probability'     => 10,
            'is_won'          => false,
            'is_lost'         => false,
            'required_fields' => null,
        ]);

        return Deal::create(array_merge([
            'pipeline_id'       => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'name'              => 'Deal de prueba',
            'amount'            => 1000,
            'currency'          => 'MXN',
            'status'            => 'open',
        ], $overrides));
    }

    private function makeWorkflow(array $overrides = []): Workflow
    {
        return Workflow::create(array_merge([
            'name'                 => 'Workflow de prueba',
            'type'                 => 'deal',
            'enrollment_trigger'   => ['event' => 'created'],
            'is_active'            => true,
            'reenrollment_allowed' => false,
        ], $overrides));
    }

    private function makeEnrollment($enrollable, ?Workflow $workflow = null, array $overrides = []): WorkflowEnrollment
    {
        $workflow ??= $this->makeWorkflow();

        return WorkflowEnrollment::create(array_merge([
            'workflow_id'      => $workflow->id,
            'enrollable_type'  => $enrollable->getMorphClass(),
            'enrollable_id'    => $enrollable->getKey(),
            'current_step_id'  => null,
            'status'           => 'active',
            'enrolled_at'      => now(),
        ], $overrides));
    }

    public function test_resolves_flat_path_from_enrollable_field(): void
    {
        $customer = $this->makeCustomer(['first_name' => 'Ana']);
        $enrollment = $this->makeEnrollment($customer);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('Hola {{ first_name }}', $enrollment);

        $this->assertEquals('Hola Ana', $resolved);
    }

    public function test_resolves_dot_notation_path_through_relation(): void
    {
        $customer = $this->makeCustomer(['company' => 'Industrias ACME']);
        $deal = $this->makeDeal(['customer_id' => $customer->id]);
        $enrollment = $this->makeEnrollment($deal);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens(
            'Empresa: {{ customer.company }}',
            $enrollment
        );

        $this->assertEquals('Empresa: Industrias ACME', $resolved);
    }

    public function test_resolves_named_workflow_variable_before_falling_back_to_flat_field(): void
    {
        $workflow = $this->makeWorkflow();

        WorkflowVariable::create([
            'workflow_id' => $workflow->id,
            'name'        => 'saludo',
            'type'        => 'string',
            'value'       => 'Buenos días',
        ]);

        $deal = $this->makeDeal(['name' => 'no debería usarse']);
        $enrollment = $this->makeEnrollment($deal, $workflow);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('{{ saludo }}', $enrollment);

        $this->assertEquals('Buenos días', $resolved);
    }

    public function test_falls_back_to_flat_field_when_no_named_variable_matches(): void
    {
        $deal = $this->makeDeal(['name' => 'Caldera 500L']);
        $enrollment = $this->makeEnrollment($deal);

        // 'name' no es una WorkflowVariable -- debe caer a data_get($deal, 'name').
        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('{{ name }}', $enrollment);

        $this->assertEquals('Caldera 500L', $resolved);
    }

    public function test_unresolved_token_becomes_empty_string(): void
    {
        $deal = $this->makeDeal();
        $enrollment = $this->makeEnrollment($deal);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('X{{ campo_que_no_existe }}Y', $enrollment);

        $this->assertEquals('XY', $resolved);
    }

    public function test_multiple_tokens_in_same_text_all_resolve(): void
    {
        $customer = $this->makeCustomer(['first_name' => 'Ana', 'company' => 'ACME']);
        $deal = $this->makeDeal(['customer_id' => $customer->id, 'name' => 'Caldera']);
        $enrollment = $this->makeEnrollment($deal);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens(
            '{{ name }} para {{ customer.first_name }} de {{ customer.company }}',
            $enrollment
        );

        $this->assertEquals('Caldera para Ana de ACME', $resolved);
    }

    public function test_text_without_tokens_is_returned_unchanged(): void
    {
        $deal = $this->makeDeal();
        $enrollment = $this->makeEnrollment($deal);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('Texto sin tokens', $enrollment);

        $this->assertEquals('Texto sin tokens', $resolved);
    }

    public function test_null_text_returns_null(): void
    {
        $deal = $this->makeDeal();
        $enrollment = $this->makeEnrollment($deal);

        $this->assertNull(app(WorkflowTokenResolver::class)->resolveTokens(null, $enrollment));
    }

    // --- Prefijos reservados (Fase 24) ---------------------------------------

    public function test_hoy_resolves_to_current_date_at_execution_time(): void
    {
        $deal = $this->makeDeal();
        $enrollment = $this->makeEnrollment($deal);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('{{ hoy }}', $enrollment);

        $this->assertEquals(now()->toDateString(), $resolved);
    }

    public function test_ahora_resolves_to_current_datetime_at_execution_time(): void
    {
        $deal = $this->makeDeal();
        $enrollment = $this->makeEnrollment($deal);

        $this->travelTo(now()->addHours(3));

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('{{ ahora }}', $enrollment);

        $this->assertEquals(now()->toDateTimeString(), $resolved);

        $this->travelBack();
    }

    public function test_empresa_nombre_resolves_to_app_name_config(): void
    {
        config(['app.name' => 'Equiterm Industries']);

        $deal = $this->makeDeal();
        $enrollment = $this->makeEnrollment($deal);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('{{ empresa.nombre }}', $enrollment);

        $this->assertEquals('Equiterm Industries', $resolved);
    }

    public function test_settings_prefix_resolves_via_setting_get_with_full_remaining_path(): void
    {
        Setting::set('ecommerce.iva_rate', '16');

        $deal = $this->makeDeal();
        $enrollment = $this->makeEnrollment($deal);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('{{ settings.ecommerce.iva_rate }}', $enrollment);

        $this->assertEquals('16', $resolved);
    }

    public function test_settings_prefix_resolves_to_empty_string_for_unknown_key(): void
    {
        $deal = $this->makeDeal();
        $enrollment = $this->makeEnrollment($deal);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('{{ settings.no_existe }}', $enrollment);

        $this->assertEquals('', $resolved);
    }

    public function test_previous_prefix_resolves_from_trigger_context(): void
    {
        $deal = $this->makeDeal(['status' => 'open']);
        $enrollment = $this->makeEnrollment($deal, null, [
            'trigger_context' => ['previous' => ['status' => 'pending']],
        ]);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('De {{ _previous.status }} a {{ status }}', $enrollment);

        $this->assertEquals('De pending a open', $resolved);
    }

    public function test_previous_prefix_resolves_to_empty_string_when_no_trigger_context(): void
    {
        $deal = $this->makeDeal();
        $enrollment = $this->makeEnrollment($deal);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('{{ _previous.status }}', $enrollment);

        $this->assertEquals('', $resolved);
    }

    public function test_actor_tokens_resolve_to_authenticated_user_captured_in_trigger_context(): void
    {
        $user = User::factory()->create(['first_name' => 'Laura', 'last_name' => 'Gomez', 'email' => 'laura@example.com']);

        $deal = $this->makeDeal();
        $enrollment = $this->makeEnrollment($deal, null, [
            'trigger_context' => ['actor_user_id' => $user->id],
        ]);

        $resolver = app(WorkflowTokenResolver::class);

        $this->assertEquals('Laura Gomez', $resolver->resolveTokens('{{ actor.nombre }}', $enrollment));
        $this->assertEquals('laura@example.com', $resolver->resolveTokens('{{ actor.email }}', $enrollment));
    }

    public function test_actor_tokens_resolve_to_empty_string_without_authenticated_actor(): void
    {
        $deal = $this->makeDeal();
        $enrollment = $this->makeEnrollment($deal, null, [
            'trigger_context' => ['actor_user_id' => null],
        ]);

        $resolver = app(WorkflowTokenResolver::class);

        $this->assertEquals('', $resolver->resolveTokens('{{ actor.nombre }}', $enrollment));
        $this->assertEquals('', $resolver->resolveTokens('{{ actor.email }}', $enrollment));
    }
}
