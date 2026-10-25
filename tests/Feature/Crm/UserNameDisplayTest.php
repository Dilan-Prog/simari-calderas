<?php

namespace Tests\Feature\Crm;

use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Role;
use App\Models\User;
use App\Services\DealService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La tabla users no tiene columna `name`: las pantallas de Negocios y Metas que listaban usuarios fallaban
 * en produccion con "Unknown column 'name' in 'ORDER BY'" y mostraban "Sin asignar" aunque hubiera responsable.
 */
class UserNameDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);
        $mk = fn (string $first, string $last) => User::create([
            'first_name' => $first, 'last_name' => $last, 'position' => 'A', 'phone' => '5555555555',
            'email' => strtolower($first) . '-' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)) . random_int(10, 99), 'role_id' => $role->id,
        ]);
        $this->admin = $mk('Admin', 'Test');
        $this->owner = $mk('Clelia Fernanda', 'Oliva Luevano');
    }

    private function deal()
    {
        $pipeline = Pipeline::create(['name' => 'Ventas General', 'channel' => 'deals', 'is_default' => true, 'is_active' => true]);
        PipelineStage::create(['pipeline_id' => $pipeline->id, 'name' => 'Prospecto', 'slug' => 'prospecto', 'order' => 0]);

        return app(DealService::class)->create([
            'pipeline_id' => $pipeline->id, 'name' => 'Negocio de prueba', 'amount' => 0, 'currency' => 'MXN',
            'owner_id' => $this->owner->id, 'status' => 'open',
        ]);
    }

    public function test_el_usuario_expone_su_nombre_completo_como_name(): void
    {
        $this->assertSame('Clelia Fernanda Oliva Luevano', $this->owner->name);
    }

    public function test_crear_y_editar_negocio_cargan_la_lista_de_responsables(): void
    {
        $deal = $this->deal();

        $this->actingAs($this->admin)->get(route('admin.deals.create'))->assertOk()->assertSee('Clelia Fernanda Oliva Luevano');
        $this->actingAs($this->admin)->get(route('admin.deals.edit', $deal))->assertOk()->assertSee('Clelia Fernanda Oliva Luevano');
    }

    public function test_el_negocio_muestra_a_su_responsable_en_vez_de_sin_asignar(): void
    {
        $deal = $this->deal();

        $html = $this->actingAs($this->admin)->get(route('admin.deals.show', $deal))->assertOk()->getContent();

        $this->assertStringContainsString('Clelia Fernanda Oliva Luevano', $html);
    }

    public function test_crear_y_editar_meta_cargan_la_lista_de_usuarios(): void
    {
        $this->actingAs($this->admin)->get(route('admin.goals.create'))->assertOk()->assertSee('Clelia Fernanda Oliva Luevano');
    }
}
