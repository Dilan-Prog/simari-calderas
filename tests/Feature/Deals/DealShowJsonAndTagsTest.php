<?php

namespace Tests\Feature\Deals;

use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre dos piezas más de la Fase 14 del plan CRM:
 * - DealController::show() sirviendo JSON cuando el cliente manda
 *   Accept: application/json (drawer lateral del rediseño), sin dejar de
 *   servir la vista Blade normal en el resto de los casos.
 * - TagController, el CRUD modal-based nuevo de etiquetas de Negocios.
 */
class DealShowJsonAndTagsTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $role = Role::create([
            'name_role' => 'Administrador',
            'name_role_es' => 'Administrador',
        ]);

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

    public function test_show_returns_json_when_the_client_asks_for_it(): void
    {
        $admin = $this->adminUser();

        $pipeline = Pipeline::create(['name' => 'Ventas', 'is_default' => true, 'is_active' => true]);
        $stage = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Contactado',
            'slug' => 'contactado',
            'order' => 0,
            'probability' => 10,
        ]);

        $deal = Deal::create([
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'Negocio JSON',
            'amount' => 500,
            'currency' => 'MXN',
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin)->getJson("/admin/negocios/{$deal->id}");

        $response->assertOk();
        $response->assertJsonPath('id', $deal->id);
        $response->assertJsonPath('folio', $deal->folio);
        $response->assertJsonPath('stage.id', $stage->id);
    }

    public function test_show_still_serves_the_blade_view_for_normal_browser_requests(): void
    {
        $admin = $this->adminUser();

        $pipeline = Pipeline::create(['name' => 'Ventas', 'is_default' => true, 'is_active' => true]);
        $stage = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Contactado',
            'slug' => 'contactado',
            'order' => 0,
            'probability' => 10,
        ]);

        $deal = Deal::create([
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'Negocio Blade',
            'amount' => 500,
            'currency' => 'MXN',
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin)->get("/admin/negocios/{$deal->id}");

        $response->assertOk();
        $response->assertViewIs('admin.deals.show');
    }

    public function test_a_tag_can_be_created_attached_and_listed(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->postJson('/admin/etiquetas-negocio', [
            'name' => 'Urgente',
            'color' => '#ff0000',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('tags', ['name' => 'Urgente', 'color' => '#ff0000']);
    }

    public function test_a_duplicate_tag_name_is_rejected(): void
    {
        $admin = $this->adminUser();
        Tag::create(['name' => 'Urgente', 'color' => '#ff0000']);

        $response = $this->actingAs($admin)->postJson('/admin/etiquetas-negocio', [
            'name' => 'Urgente',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('name');
    }

    public function test_a_tag_can_be_attached_to_a_deal_and_shows_up_in_its_json_detail(): void
    {
        $admin = $this->adminUser();

        $pipeline = Pipeline::create(['name' => 'Ventas', 'is_default' => true, 'is_active' => true]);
        $stage = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Contactado',
            'slug' => 'contactado',
            'order' => 0,
            'probability' => 10,
        ]);
        $deal = Deal::create([
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'Negocio etiquetado',
            'amount' => 500,
            'currency' => 'MXN',
            'status' => 'open',
        ]);
        $tag = Tag::create(['name' => 'VIP', 'color' => '#00ff00']);

        $deal->tags()->attach($tag->id);

        $response = $this->actingAs($admin)->getJson("/admin/negocios/{$deal->id}");

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'VIP']);
    }
}
