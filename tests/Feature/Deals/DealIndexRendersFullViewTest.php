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
 * Smoke test de integración para la costura entre DealController::index()
 * (backend, esta Fase 14) y resources/views/admin/deals/index.blade.php
 * (frontend, construido en paralelo): confirma que el contrato de
 * variables documentado en la cabecera de esa vista ($pipelines,
 * $pipeline, $stages, $deals, $owners, $tags) realmente renderiza sin
 * errores con datos reales, incluyendo un Deal con etiquetas adjuntas.
 */
class DealIndexRendersFullViewTest extends TestCase
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

    public function test_the_unified_deals_page_renders_with_a_full_dataset(): void
    {
        $admin = $this->adminUser();

        $pipeline = Pipeline::create(['name' => 'Ventas', 'is_default' => true, 'is_active' => true]);
        $stage = PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Contactado',
            'slug' => 'contactado',
            'order' => 0,
            'probability' => 10,
            'wip_limit' => 5,
        ]);

        $deal = Deal::create([
            'pipeline_id' => $pipeline->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'Negocio de humo',
            'amount' => 1500,
            'currency' => 'MXN',
            'status' => 'open',
            'owner_id' => $admin->id,
        ]);

        $tag = Tag::create(['name' => 'VIP', 'color' => '#ff0000']);
        $deal->tags()->attach($tag->id);

        $response = $this->actingAs($admin)->get('/admin/negocios');

        $response->assertOk();
        $response->assertViewIs('admin.deals.index');
        $response->assertViewHas('pipeline', fn ($p) => $p->id === $pipeline->id);
        $response->assertViewHas('deals', fn ($deals) => $deals->count() === 1);
        $response->assertSee('Negocio de humo');
        $response->assertSee('VIP');
    }

    public function test_the_alias_route_table_serves_the_same_unified_page(): void
    {
        $admin = $this->adminUser();

        Pipeline::create(['name' => 'Ventas', 'is_default' => true, 'is_active' => true]);

        $response = $this->actingAs($admin)->get('/admin/negocios/tabla');

        $response->assertOk();
        $response->assertViewIs('admin.deals.index');
    }
}
