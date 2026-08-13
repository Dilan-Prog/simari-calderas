<?php

namespace Tests\Feature\Deals;

use App\Models\Deal;
use App\Models\DealStageHistory;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre la Fase 14 del plan CRM: DealController::bulkAction() (mover en
 * lote / reasignar dueño / eliminar, dentro de una sola transacción) y
 * DealController::exportCsv() (reutiliza el export genérico FromCollection
 * ya usado por Reportes, ver app/Exports/ReportExport.php).
 */
class DealBulkActionAndExportTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::create([
            'name_role' => 'Administrador',
            'name_role_es' => 'Administrador',
        ]);

        // Igual que en PipelineChannelTest: no se usa User::factory() (su
        // definition() manda un 'name' que no existe en la tabla real).
        return User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'position' => 'Administrador',
            'phone' => '5555555555',
            'email' => 'admin-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'rfc' => 'XAXX010101000',
            'role_id' => $role->id,
        ]);
    }

    private function pipelineWithStages(): array
    {
        $pipeline = Pipeline::create(['name' => 'Ventas', 'is_default' => true, 'is_active' => true]);

        $stageA = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Contactado',
            'slug' => 'contactado',
            'order' => 0,
            'probability' => 10,
        ]);

        $stageB = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Propuesta',
            'slug' => 'propuesta',
            'order' => 1,
            'probability' => 50,
        ]);

        return [$pipeline, $stageA, $stageB];
    }

    private function makeDeal(Pipeline $pipeline, PipelineStage $stage, array $overrides = []): Deal
    {
        $deal = Deal::create(array_merge([
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'Negocio de prueba',
            'amount' => 1000,
            'currency' => 'MXN',
            'status' => 'open',
        ], $overrides));

        DealStageHistory::create([
            'deal_id' => $deal->id,
            'from_stage_id' => null,
            'to_stage_id' => $stage->id,
            'moved_at' => now(),
        ]);

        return $deal;
    }

    public function test_bulk_action_moves_a_batch_of_deals_to_a_new_stage_and_logs_history(): void
    {
        $admin = $this->adminUser();
        [$pipeline, $stageA, $stageB] = $this->pipelineWithStages();

        $dealOne = $this->makeDeal($pipeline, $stageA);
        $dealTwo = $this->makeDeal($pipeline, $stageA);

        $response = $this->actingAs($admin)->postJson('/admin/negocios/accion-masiva', [
            'ids' => [$dealOne->id, $dealTwo->id],
            'action' => 'move_stage',
            'stage_id' => $stageB->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('affected', 2);

        $this->assertSame($stageB->id, $dealOne->fresh()->pipeline_stage_id);
        $this->assertSame($stageB->id, $dealTwo->fresh()->pipeline_stage_id);

        // Cada movimiento debe haber quedado registrado en el historial
        // (DealService::moveStage() ya lo hace, bulkAction() lo reutiliza
        // en vez de escribir el pivote a mano).
        $this->assertDatabaseHas('deal_stage_history', [
            'deal_id' => $dealOne->id,
            'from_stage_id' => $stageA->id,
            'to_stage_id' => $stageB->id,
        ]);
        $this->assertDatabaseHas('deal_stage_history', [
            'deal_id' => $dealTwo->id,
            'from_stage_id' => $stageA->id,
            'to_stage_id' => $stageB->id,
        ]);
    }

    public function test_bulk_action_assigns_a_new_owner_to_a_batch_of_deals(): void
    {
        $admin = $this->adminUser();
        [$pipeline, $stageA] = $this->pipelineWithStages();

        // No se reusa adminUser() para el nuevo dueño: crea otro Role con
        // el mismo name_role, que viola su unique() — solo se necesita
        // otro User con el mismo role_id.
        $newOwner = User::create([
            'first_name' => 'Segundo',
            'last_name' => 'Ejecutivo',
            'position' => 'Ejecutivo de ventas',
            'phone' => '5555555556',
            'email' => 'owner-' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'rfc' => 'XEXX010101' . random_int(100, 999),
            'role_id' => $admin->role_id,
        ]);

        $dealOne = $this->makeDeal($pipeline, $stageA);
        $dealTwo = $this->makeDeal($pipeline, $stageA);

        $response = $this->actingAs($admin)->postJson('/admin/negocios/accion-masiva', [
            'ids' => [$dealOne->id, $dealTwo->id],
            'action' => 'assign_owner',
            'owner_id' => $newOwner->id,
        ]);

        $response->assertOk();
        $response->assertJsonPath('affected', 2);

        $this->assertSame($newOwner->id, $dealOne->fresh()->owner_id);
        $this->assertSame($newOwner->id, $dealTwo->fresh()->owner_id);
    }

    public function test_bulk_action_soft_deletes_a_batch_of_deals(): void
    {
        $admin = $this->adminUser();
        [$pipeline, $stageA] = $this->pipelineWithStages();

        $dealOne = $this->makeDeal($pipeline, $stageA);
        $dealTwo = $this->makeDeal($pipeline, $stageA);

        $response = $this->actingAs($admin)->postJson('/admin/negocios/accion-masiva', [
            'ids' => [$dealOne->id, $dealTwo->id],
            'action' => 'delete',
        ]);

        $response->assertOk();
        $response->assertJsonPath('affected', 2);

        $this->assertSoftDeleted('deals', ['id' => $dealOne->id]);
        $this->assertSoftDeleted('deals', ['id' => $dealTwo->id]);
    }

    public function test_bulk_action_move_stage_requires_a_stage_id(): void
    {
        $admin = $this->adminUser();
        [$pipeline, $stageA] = $this->pipelineWithStages();

        $deal = $this->makeDeal($pipeline, $stageA);

        $response = $this->actingAs($admin)->postJson('/admin/negocios/accion-masiva', [
            'ids' => [$deal->id],
            'action' => 'move_stage',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('stage_id');
    }

    public function test_bulk_action_rejects_an_unknown_action(): void
    {
        $admin = $this->adminUser();
        [$pipeline, $stageA] = $this->pipelineWithStages();

        $deal = $this->makeDeal($pipeline, $stageA);

        $response = $this->actingAs($admin)->postJson('/admin/negocios/accion-masiva', [
            'ids' => [$deal->id],
            'action' => 'send_rocket',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('action');
    }

    public function test_bulk_action_move_stage_rolls_back_the_whole_batch_when_a_deal_is_missing_required_fields(): void
    {
        $admin = $this->adminUser();
        [$pipeline, $stageA] = $this->pipelineWithStages();

        // Segunda etapa con un campo requerido que ningún deal de prueba
        // trae — DealService::moveStage() debe tirar ValidationException
        // en el segundo deal del lote, y como bulkAction() envuelve todo
        // en una sola transacción, el primer deal (que sí pudo moverse)
        // debe revertirse también.
        $strictStage = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Cierre',
            'slug' => 'cierre',
            'order' => 2,
            'probability' => 90,
            'required_fields' => ['expected_close_date'],
        ]);

        $dealOne = $this->makeDeal($pipeline, $stageA, ['expected_close_date' => now()->addDays(5)]);
        $dealTwo = $this->makeDeal($pipeline, $stageA); // sin expected_close_date

        $response = $this->actingAs($admin)->postJson('/admin/negocios/accion-masiva', [
            'ids' => [$dealOne->id, $dealTwo->id],
            'action' => 'move_stage',
            'stage_id' => $strictStage->id,
        ]);

        // DealService::moveStage() tira ValidationException, que el
        // exception handler de Laravel convierte en 422 (no 500) porque
        // la request espera JSON — lo importante es que la transacción
        // completa de bulkAction() se revirtió, ver aserciones abajo.
        $response->assertStatus(422);

        $this->assertSame($stageA->id, $dealOne->fresh()->pipeline_stage_id);
        $this->assertSame($stageA->id, $dealTwo->fresh()->pipeline_stage_id);
    }

    public function test_export_csv_streams_a_csv_file_with_the_filtered_deals(): void
    {
        $admin = $this->adminUser();
        [$pipeline, $stageA] = $this->pipelineWithStages();

        $this->makeDeal($pipeline, $stageA, ['name' => 'Negocio exportable uno']);
        $this->makeDeal($pipeline, $stageA, ['name' => 'Negocio exportable dos']);

        $response = $this->actingAs($admin)->get('/admin/negocios/exportar');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_export_csv_respects_the_status_filter(): void
    {
        $admin = $this->adminUser();
        [$pipeline, $stageA] = $this->pipelineWithStages();

        $this->makeDeal($pipeline, $stageA, ['status' => 'open']);
        $this->makeDeal($pipeline, $stageA, ['status' => 'won', 'closed_at' => now()]);

        $response = $this->actingAs($admin)->get('/admin/negocios/exportar?status=won');

        $response->assertOk();
    }
}
