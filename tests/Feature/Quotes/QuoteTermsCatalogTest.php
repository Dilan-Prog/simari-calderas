<?php

namespace Tests\Feature\Quotes;

use App\Models\Customer;
use App\Models\Quote;
use App\Models\QuoteTerm;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\QuoteTermsComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteTermsCatalogTest extends TestCase
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

    private function term(array $o = []): QuoteTerm
    {
        return QuoteTerm::create($o + ['title' => 'T' . uniqid(), 'body' => 'Cuerpo', 'applies_to' => 'both', 'sort_order' => 0]);
    }

    private function customer(): Customer
    {
        return Customer::create([
            'first_name' => 'Zulema', 'last_name' => 'Quintero', 'email' => 'z-' . uniqid() . '@example.com', 'phone' => '5511223344',
            'password_hash' => 'x', 'document_type' => 'RFC', 'document_numer' => 'X', 'birth_date' => '1990-01-01',
            'status' => 'active', 'source' => 'web', 'company' => 'Textiles',
        ]);
    }

    private function payload(array $over = []): array
    {
        $items = [['product_id' => null, 'product_name' => 'Linea', 'quantity' => 1, 'unit_price' => 100,
            'discount_percent' => 0, 'tax_percent' => 16, 'line_total' => 100]];

        return $over + ['customer_id' => $this->customer()->id, 'guest_name' => 'Z', 'tax_rate' => 16, 'currency' => 'MXN',
            'exchange_rate' => 18, 'items_json' => json_encode($items)];
    }

    public function test_crud_del_catalogo(): void
    {
        $this->actingAs($this->admin)->post(route('admin.erp-settings.quote-term.store'), [
            'title' => 'Vigencia', 'body' => '30 dias', 'applies_to' => 'product', 'is_default' => 1, 'is_active' => 1,
        ])->assertRedirect()->assertSessionHas('success');
        $t = QuoteTerm::firstOrFail();
        $this->assertTrue($t->is_default);
        $this->assertSame('product', $t->applies_to);

        $this->actingAs($this->admin)->put(route('admin.erp-settings.quote-term.update', $t), [
            'title' => 'Vigencia 2', 'body' => '60 dias', 'applies_to' => 'service',
        ])->assertSessionHas('success');
        $t->refresh();
        $this->assertSame('Vigencia 2', $t->title);
        $this->assertFalse($t->is_default);
        $this->assertFalse($t->is_active);

        $this->actingAs($this->admin)->get(route('admin.erp-settings.index'))->assertOk()->assertSee('Vigencia 2');

        $this->actingAs($this->admin)->post(route('admin.erp-settings.quote-term.store'), ['title' => '', 'body' => 'x', 'applies_to' => 'zzz'])
            ->assertSessionHasErrors(['title', 'applies_to']);

        $this->actingAs($this->admin)->delete(route('admin.erp-settings.quote-term.destroy', $t))->assertSessionHas('success');
        $this->assertSame(0, QuoteTerm::count());
    }

    public function test_mover_cambia_el_orden(): void
    {
        $this->term(['title' => 'A', 'sort_order' => 0]);
        $b = $this->term(['title' => 'B', 'sort_order' => 1]);
        $this->actingAs($this->admin)->post(route('admin.erp-settings.quote-term.move', $b), ['direction' => 'up']);
        $this->assertSame(['B', 'A'], QuoteTerm::ordered()->pluck('title')->all());
    }

    public function test_backfill_migra_el_texto_unico(): void
    {
        Setting::updateOrCreate(['key' => 'erp.quote_terms_default'], ['value' => 'Texto viejo', 'type' => 'string', 'group_name' => 'erp']);
        $m = require database_path('migrations/2026_10_30_100000_create_quote_terms_table.php');
        $m->backfill();
        $m->backfill(); // idempotente

        $this->assertSame(1, QuoteTerm::count());
        $t = QuoteTerm::first();
        $this->assertSame('Términos generales', $t->title);
        $this->assertSame('Texto viejo', $t->body);
        $this->assertTrue($t->is_default);
        $this->assertSame('both', $t->applies_to);
        $this->assertNotNull(Setting::where('key', 'erp.quote_terms_default')->first());
    }

    public function test_composicion_orden_titulo_y_custom(): void
    {
        $b = $this->term(['title' => 'Anticipo', 'body' => '50% al pedir', 'sort_order' => 2]);
        $a = $this->term(['title' => 'Vigencia', 'body' => 'Vigencia 30 dias', 'sort_order' => 1]);

        $c = new QuoteTermsComposer();
        $this->assertSame("• Vigencia 30 dias\n\n• Anticipo: 50% al pedir", $c->compose([$b->id, $a->id], ''));
        $this->assertSame("• Vigencia 30 dias\n\nTexto libre", $c->compose([$a->id], "  Texto libre \n"));
        $this->assertSame('Solo libre', $c->compose([], 'Solo libre'));
        $this->assertSame('', $c->compose([], null));

        $this->actingAs($this->admin)->getJson(route('admin.quotes.terms-preview', ['term_ids' => [$a->id], 'custom' => 'X']))
            ->assertOk()->assertJson(['text' => "• Vigencia 30 dias\n\nX"]);
    }

    public function test_store_y_update_guardan_seleccion_y_texto(): void
    {
        $a = $this->term(['title' => 'Vigencia', 'body' => '30 dias', 'sort_order' => 1]);
        $b = $this->term(['title' => 'Anticipo', 'body' => '50%', 'sort_order' => 2]);

        $this->actingAs($this->admin)->post(route('admin.quotes.store'), $this->payload([
            'terms_present' => 1, 'term_ids' => [$b->id, $a->id], 'terms_custom' => 'Extra',
        ]))->assertRedirect();
        $q = Quote::firstOrFail();
        $this->assertEquals(['term_ids' => [$a->id, $b->id], 'custom' => 'Extra'], $q->terms_selection);
        $this->assertSame("• Vigencia: 30 dias\n\n• Anticipo: 50%\n\nExtra", $q->terms_conditions);

        $this->actingAs($this->admin)->put(route('admin.quotes.update', $q), $this->payload([
            'customer_id' => $q->customer_id, 'terms_present' => 1, 'term_ids' => [$a->id], 'terms_custom' => '',
        ]))->assertRedirect();
        $q->refresh();
        $this->assertSame('• Vigencia: 30 dias', $q->terms_conditions);
        $this->assertEquals(['term_ids' => [$a->id], 'custom' => ''], $q->terms_selection);

        // Clave presente pero sin nada seleccionado: sin terminos.
        $this->actingAs($this->admin)->put(route('admin.quotes.update', $q), $this->payload([
            'customer_id' => $q->customer_id, 'terms_present' => 1,
        ]));
        $this->assertNull($q->fresh()->terms_conditions);
    }

    public function test_compatibilidad_sin_clave_de_seleccion(): void
    {
        $this->actingAs($this->admin)->post(route('admin.quotes.store'), $this->payload(['terms_conditions' => 'Texto manual']))->assertRedirect();
        $q = Quote::firstOrFail();
        $this->assertSame('Texto manual', $q->terms_conditions);
        $this->assertNull($q->terms_selection);
    }

    public function test_store_rechaza_ids_inexistentes(): void
    {
        $this->actingAs($this->admin)->post(route('admin.quotes.store'), $this->payload(['terms_present' => 1, 'term_ids' => [9999]]))
            ->assertSessionHasErrors('term_ids.0');
    }

    public function test_create_y_edit_exponen_variables(): void
    {
        $a = $this->term(['title' => 'Vigencia', 'is_default' => true, 'sort_order' => 1]);
        $b = $this->term(['title' => 'Anticipo', 'sort_order' => 2]);
        $this->term(['title' => 'Inactivo', 'is_active' => false]);

        $res = $this->actingAs($this->admin)->get(route('admin.quotes.create'))->assertOk();
        $this->assertSame(['Vigencia', 'Anticipo'], $res->viewData('quoteTerms')->pluck('title')->all());
        $this->assertSame([$a->id], $res->viewData('selectedTermIds'));
        $this->assertSame('', $res->viewData('customTerms'));

        $this->actingAs($this->admin)->post(route('admin.quotes.store'), $this->payload([
            'terms_present' => 1, 'term_ids' => [$b->id], 'terms_custom' => 'Libre',
        ]));
        $q = Quote::firstOrFail();
        $res = $this->actingAs($this->admin)->get(route('admin.quotes.edit', $q))->assertOk();
        $this->assertSame([$b->id], $res->viewData('selectedTermIds'));
        $this->assertSame('Libre', $res->viewData('customTerms'));

        // Cotizacion antigua: sin seleccion, el texto actual pasa como personalizado.
        $q->update(['terms_selection' => null, 'terms_conditions' => 'Texto antiguo']);
        $res = $this->actingAs($this->admin)->get(route('admin.quotes.edit', $q))->assertOk();
        $this->assertSame([], $res->viewData('selectedTermIds'));
        $this->assertSame('Texto antiguo', $res->viewData('customTerms'));
    }
}
