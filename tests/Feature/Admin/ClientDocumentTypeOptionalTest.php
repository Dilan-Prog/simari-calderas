<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Clientes (admin): el tipo de documento es opcional al crear y al editar. */
class ClientDocumentTypeOptionalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);
        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'A', 'phone' => '5555555555',
            'email' => 'u-' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => 'RFC' . strtoupper(substr(uniqid(), -8)) . random_int(10, 99), 'role_id' => $role->id,
        ]);
    }

    public function test_se_puede_crear_y_editar_un_cliente_sin_tipo_de_documento(): void
    {
        $payload = [
            'full_name' => 'Cliente Sin Documento', 'company' => 'Empresa Uno', 'email' => 'sin-doc@example.com', 'phone' => '4491234567',
            'source' => 'admin', 'status' => 'active', 'document_type' => '',
        ];

        $this->actingAs($this->admin)->postJson(route('admin.clients.store'), $payload)->assertRedirect();

        $customer = Customer::where('email', 'sin-doc@example.com')->firstOrFail();
        $this->assertSame('', $customer->document_type);

        $this->actingAs($this->admin)->putJson(route('admin.clients.update', $customer->id), $payload + ['notes' => 'editado'])->assertSuccessful();
        $this->assertSame('', $customer->fresh()->document_type);
    }

    public function test_un_tipo_de_documento_invalido_sigue_rechazandose(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.clients.store'), [
            'full_name' => 'Cliente Raro', 'company' => 'Empresa', 'phone' => '4491234567', 'source' => 'admin', 'status' => 'active', 'document_type' => 'licencia',
        ])->assertStatus(422)->assertJsonValidationErrors('document_type');
    }
}
