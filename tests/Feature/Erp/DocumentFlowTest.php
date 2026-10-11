<?php

namespace Tests\Feature\Erp;

use App\Models\DocumentLink;
use App\Models\Role;
use App\Models\User;
use App\Services\Erp\DocumentFlow;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentFlowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador', 'phone' => '5555555555',
            'email' => 'a-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    public function test_link_es_idempotente_y_flow_recorre_ambas_direcciones(): void
    {
        $uid = $this->admin()->id;
        $quote = Quote::create(['quote_number' => 'COT-TEST-1', 'status' => 'accepted', 'created_by_user_id' => $uid, 'guest_name' => 'Cliente', 'guest_email' => 'c@example.com']);
        $order = SalesOrder::create(['order_number' => 'PED-TEST-1', 'quote_id' => $quote->id, 'status' => 'pendiente', 'created_by_user_id' => $uid]);

        $this->assertTrue(DocumentFlow::link($quote, $order));
        $this->assertTrue(DocumentFlow::link($quote, $order));
        $this->assertSame(1, DocumentLink::count());

        foreach ([$quote, $order] as $doc) {
            $nodes = DocumentFlow::flow($doc);
            $this->assertSame(['COT-TEST-1', 'PED-TEST-1'], array_column($nodes, 'number'));
            $this->assertCount(1, array_filter($nodes, fn ($n) => $n['current']));
        }

        DocumentFlow::unlinkAll($order);
        $this->assertSame(0, DocumentLink::count());
    }

    public function test_modelo_no_registrado_no_enlaza(): void
    {
        $this->assertFalse(DocumentFlow::link(new Customer(), new Customer()));
    }

    public function test_launchpad_y_busqueda_global(): void
    {
        $admin = $this->admin();
        Quote::create(['quote_number' => 'COT-ZZ-77', 'status' => 'draft', 'created_by_user_id' => $admin->id, 'guest_name' => 'Cliente', 'guest_email' => 'c@example.com']);

        $this->actingAs($admin)->get(route('admin.launchpad'))->assertOk()->assertSee('Operaciones', false);

        $this->actingAs($admin)->getJson(route('admin.launchpad.search', ['q' => 'ZZ-77']))
            ->assertOk()
            ->assertJsonPath('documents.0.number', 'COT-ZZ-77');

        $this->actingAs($admin)->getJson(route('admin.launchpad.search', ['q' => 'Cotiz']))
            ->assertOk()->assertJsonStructure(['modules', 'documents']);
    }
}
