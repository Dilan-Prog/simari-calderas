<?php

namespace Tests\Feature\Workflows;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Workflow;
use App\Models\WorkflowVariable;
use App\Services\WorkflowTokenResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de WorkflowTokenResolver (Fase 17) -- primer sistema de tokens
 * `{{ }}` real del motor. Cubre las 3 rutas de resolución descritas en
 * resolveTokens(): path plano vía campo del enrollable, path con punto vía
 * data_get()/relación, y variable nombrada (WorkflowVariable) con y sin
 * scope de workflow.
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

    public function test_resolves_flat_path_from_enrollable_field(): void
    {
        $customer = $this->makeCustomer(['first_name' => 'Ana']);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('Hola {{ first_name }}', $customer);

        $this->assertEquals('Hola Ana', $resolved);
    }

    public function test_resolves_dot_notation_path_through_relation(): void
    {
        $customer = $this->makeCustomer(['company' => 'Industrias ACME']);
        $deal = $this->makeDeal(['customer_id' => $customer->id]);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens(
            'Empresa: {{ customer.company }}',
            $deal
        );

        $this->assertEquals('Empresa: Industrias ACME', $resolved);
    }

    public function test_resolves_named_workflow_variable_before_falling_back_to_flat_field(): void
    {
        $workflow = Workflow::create([
            'name'                 => 'Workflow de prueba',
            'type'                 => 'deal',
            'enrollment_trigger'   => ['event' => 'created'],
            'is_active'            => true,
            'reenrollment_allowed' => false,
        ]);

        WorkflowVariable::create([
            'workflow_id' => $workflow->id,
            'name'        => 'saludo',
            'type'        => 'string',
            'value'       => 'Buenos días',
        ]);

        $deal = $this->makeDeal(['name' => 'no debería usarse']);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('{{ saludo }}', $deal, $workflow->id);

        $this->assertEquals('Buenos días', $resolved);
    }

    public function test_falls_back_to_flat_field_when_no_named_variable_matches(): void
    {
        $deal = $this->makeDeal(['name' => 'Caldera 500L']);

        // 'name' no es una WorkflowVariable -- debe caer a data_get($deal, 'name').
        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('{{ name }}', $deal, null);

        $this->assertEquals('Caldera 500L', $resolved);
    }

    public function test_unresolved_token_becomes_empty_string(): void
    {
        $deal = $this->makeDeal();

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('X{{ campo_que_no_existe }}Y', $deal);

        $this->assertEquals('XY', $resolved);
    }

    public function test_multiple_tokens_in_same_text_all_resolve(): void
    {
        $customer = $this->makeCustomer(['first_name' => 'Ana', 'company' => 'ACME']);
        $deal = $this->makeDeal(['customer_id' => $customer->id, 'name' => 'Caldera']);

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens(
            '{{ name }} para {{ customer.first_name }} de {{ customer.company }}',
            $deal
        );

        $this->assertEquals('Caldera para Ana de ACME', $resolved);
    }

    public function test_text_without_tokens_is_returned_unchanged(): void
    {
        $deal = $this->makeDeal();

        $resolved = app(WorkflowTokenResolver::class)->resolveTokens('Texto sin tokens', $deal);

        $this->assertEquals('Texto sin tokens', $resolved);
    }

    public function test_null_text_returns_null(): void
    {
        $deal = $this->makeDeal();

        $this->assertNull(app(WorkflowTokenResolver::class)->resolveTokens(null, $deal));
    }
}
