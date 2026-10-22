<?php

namespace Tests\Feature\Quotes;

use App\Models\Customer;
use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\PipelineStage;
use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteEmbedTest extends TestCase
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

    private function customer(): Customer
    {
        return Customer::create([
            'first_name' => 'Zulema', 'last_name' => 'Quintero', 'email' => 'zulema-' . uniqid() . '@example.com', 'phone' => '5511223344',
            'password_hash' => 'x', 'document_type' => 'RFC', 'document_numer' => 'X', 'birth_date' => '1990-01-01',
            'status' => 'active', 'source' => 'web', 'company' => 'Textiles Quintero',
        ]);
    }

    private function deal(): Deal
    {
        $pipeline = Pipeline::create(['name' => 'Ventas', 'is_active' => true]);
        $stage = PipelineStage::create(['pipeline_id' => $pipeline->id, 'name' => 'Nuevo', 'slug' => 'nuevo', 'order' => 1]);

        return Deal::create(['pipeline_id' => $pipeline->id, 'pipeline_stage_id' => $stage->id, 'name' => 'Negocio']);
    }

    private function payload(Customer $customer, array $over = []): array
    {
        $items = [[
            'product_id' => null, 'product_name' => 'Linea libre', 'quantity' => 1, 'unit_price' => 100,
            'discount_percent' => 0, 'tax_percent' => 16, 'line_total' => 100,
        ]];

        return $over + [
            'customer_id' => $customer->id, 'guest_name' => 'Zulema Quintero', 'tax_rate' => 16, 'currency' => 'MXN',
            'exchange_rate' => 18, 'items_json' => json_encode($items),
        ];
    }

    public function test_create_prellena_el_cliente(): void
    {
        $c = $this->customer();

        $res = $this->actingAs($this->admin)->get(route('admin.quotes.create', ['customer_id' => $c->id]))->assertOk();
        $res->assertSee('id="customerIdInput" value="' . $c->id . '"', false);
        $res->assertSee('value="Zulema Quintero"', false);
        $res->assertSee('value="' . $c->email . '"', false);
    }

    public function test_create_embed_no_incluye_sidebar_y_reenvia_campos(): void
    {
        $c = $this->customer();
        $deal = $this->deal();

        $res = $this->actingAs($this->admin)->get(route('admin.quotes.create', ['customer_id' => $c->id, 'deal_id' => $deal->id, 'embed' => 1]))->assertOk();
        $res->assertDontSee('admin-sidebar')
            ->assertSee('name="embed" value="1"', false)
            ->assertSee('name="deal_id" value="' . $deal->id . '"', false);

        $this->actingAs($this->admin)->get(route('admin.quotes.create'))->assertOk()->assertSee('admin-sidebar');
    }

    public function test_store_embed_liga_al_negocio_y_redirige(): void
    {
        $c = $this->customer();
        $deal = $this->deal();

        $res = $this->actingAs($this->admin)->post(route('admin.quotes.store'), $this->payload($c, ['embed' => 1, 'deal_id' => $deal->id]));

        $quote = Quote::firstOrFail();
        $res->assertRedirect(route('admin.quotes.embedded-saved', $quote));
        $this->assertTrue($deal->fresh()->quotes->contains($quote));

        $this->actingAs($this->admin)->get(route('admin.quotes.embedded-saved', $quote))
            ->assertOk()->assertSee('equiterm:quote-saved', false)->assertSee($quote->quote_number);
    }

    public function test_store_sin_embed_redirige_a_show(): void
    {
        $c = $this->customer();

        $res = $this->actingAs($this->admin)->post(route('admin.quotes.store'), $this->payload($c));

        $res->assertRedirect(route('admin.quotes.show', Quote::firstOrFail()));
    }
}
