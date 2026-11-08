<?php

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Delivery;
use App\Models\PaymentMethod;
use App\Models\Products;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\StoreOrder;
use App\Models\StoreOrderEmail;
use App\Models\StoreOrderItem;
use App\Models\User;
use App\Services\StoreOrderMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Correos automáticos de los pedidos de la tienda: cuándo salen, qué llevan, que no se repitan,
 * que un fallo de correo no rompa el pedido y la pantalla de Ecommerce > Correos de pedidos.
 * Nunca se toca un SMTP real (transporte array).
 */
class StoreOrderEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Setting::get memoiza en una propiedad estática que sobrevive entre pruebas.
        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);

        config([
            'mail.default'      => 'array',
            'mail.from.address' => 'administracion@equiterm.test',
            'mail.from.name'    => 'Equiterm Industries',
            // El aviso interno al equipo tiene sus propias pruebas (abajo); aquí se apaga para contar solo lo del cliente.
            'order_emails.staff.enabled' => false,
        ]);
    }

    private function enable(array $extra = []): void
    {
        Setting::updateOrCreate(['key' => 'order_emails.enabled'], ['value' => '1', 'type' => 'boolean', 'group_name' => 'integraciones']);
        foreach ($extra as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => 'string', 'group_name' => 'integraciones']);
        }
        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);
    }

    private function sent(): array
    {
        return app('mail.manager')->mailer('array')->getSymfonyTransport()->messages()->all();
    }

    private function transfer(): PaymentMethod
    {
        return PaymentMethod::create([
            'type' => 'transferencia', 'name' => 'Transferencia bancaria', 'is_active' => true, 'bank_name' => 'BBVA',
            'beneficiary_name' => 'Equiterm Industries', 'clabe' => '012345678901234567', 'sort_order' => 1,
        ]);
    }

    private function mercadoPago(): PaymentMethod
    {
        return PaymentMethod::create([
            'type' => 'pasarela', 'name' => 'Mercado Pago', 'is_active' => true, 'sort_order' => 2,
            'details' => ['processor' => 'mercadopago'],
        ]);
    }

    /** Pedido con una partida, como lo deja el checkout (el observer corre al cerrar la transacción). */
    private function order(PaymentMethod $method, array $over = []): StoreOrder
    {
        $category = Category::create(['name' => 'Calderas', 'slug' => 'calderas-' . uniqid(), 'is_active' => true]);
        $product = Products::create([
            'category_id' => $category->id, 'name' => 'Caldera 10HP', 'slug' => 'caldera-' . uniqid(),
            'sku' => 'CAL-' . uniqid(), 'price' => 1000, 'stock' => 5, 'is_active' => true,
        ]);

        return DB::transaction(function () use ($method, $product, $over) {
            $order = StoreOrder::unguarded(fn () => StoreOrder::create($over + [
                'order_number' => 'PW-2026-' . random_int(1000, 9999), 'contact_name' => 'Carlos Herrera',
                'contact_email' => 'carlos@cliente.com', 'contact_phone' => '5551112222',
                'shipping_address_line1' => 'Calle 5 #10', 'shipping_city' => 'Aguascalientes', 'shipping_state' => 'Aguascalientes',
                'shipping_postal_code' => '20000', 'payment_method_id' => $method->id,
                'subtotal' => 1000, 'shipping_total' => 0, 'tax_total' => 160, 'total' => 1160, 'currency' => 'MXN',
                'status' => 'pendiente_pago',
            ]));
            StoreOrderItem::create([
                'store_order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name,
                'product_sku' => $product->sku, 'quantity' => 1, 'unit_price' => 1000, 'line_total' => 1000,
            ]);

            return $order;
        });
    }

    private function admin(): User
    {
        $role = Role::firstOrCreate(['name_role' => 'Administrador'], ['name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Laura', 'last_name' => 'Mendoza', 'position' => 'Administrador', 'phone' => '5555555555',
            'email' => 'a-' . uniqid() . '@example.com', 'password' => bcrypt('password'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    // ── Cuándo salen ─────────────────────────────────────────────────────

    public function test_no_sale_nada_mientras_este_apagado(): void
    {
        Setting::updateOrCreate(['key' => 'order_emails.enabled'], ['value' => '0', 'type' => 'boolean', 'group_name' => 'integraciones']);
        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);

        $this->order($this->transfer());

        $this->assertCount(0, $this->sent());
        $this->assertSame(0, StoreOrderEmail::count());
    }

    public function test_pedido_con_transferencia_manda_los_datos_para_pagar(): void
    {
        $this->enable();
        $order = $this->order($this->transfer());

        $messages = $this->sent();
        $this->assertCount(1, $messages);

        $email = $messages[0]->getOriginalMessage();
        $this->assertSame('carlos@cliente.com', $email->getTo()[0]->getAddress());
        $this->assertSame('administracion@equiterm.test', $email->getFrom()[0]->getAddress());
        $this->assertSame('Recibimos tu pedido ' . $order->order_number, $email->getSubject());

        $html = $email->getHtmlBody();
        foreach (['BBVA', '012345678901234567', 'Equiterm Industries', 'Caldera 10HP', '$1,160.00 MXN', $order->order_number] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringContainsString('012345678901234567', $email->getTextBody());

        $log = StoreOrderEmail::firstOrFail();
        $this->assertSame(['created_offline', 'sent', $order->id], [$log->event, $log->status, $log->store_order_id]);
    }

    public function test_pedido_de_mercado_pago_no_avisa_al_crearse_sino_al_pagarse(): void
    {
        $this->enable();
        $order = $this->order($this->mercadoPago());
        $this->assertCount(0, $this->sent());

        $order->update(['status' => 'pagado']);

        $messages = $this->sent();
        $this->assertCount(1, $messages);
        $this->assertSame('Pago confirmado de tu pedido ' . $order->order_number, $messages[0]->getOriginalMessage()->getSubject());
        $this->assertSame('paid', StoreOrderEmail::firstOrFail()->event);
    }

    public function test_cada_aviso_sale_una_sola_vez_por_pedido(): void
    {
        $this->enable();
        $order = $this->order($this->mercadoPago());

        $order->update(['status' => 'pagado']);
        $order->update(['status' => 'en_preparacion']);
        $order->update(['status' => 'pagado']); // ida y vuelta de estatus

        $this->assertCount(1, $this->sent());
        $this->assertSame(1, StoreOrderEmail::where('event', 'paid')->count());
    }

    public function test_el_aviso_de_envio_incluye_paqueteria_y_guia(): void
    {
        $this->enable();
        $order = $this->order($this->mercadoPago(), ['status' => 'en_preparacion']);
        // El catálogo de paqueterías ya viene sembrado por migración.
        $carrier = Delivery::where('name', 'Estafeta')->firstOrFail();
        $carrier->update(['tracking_url_template' => 'https://rastreo.test/?guia={guia}']);
        $user = $this->admin();
        Shipment::create([
            'store_order_id' => $order->id, 'carrier_id' => $carrier->id, 'guia' => 'AB 123', 'status' => 'preparando', 'bultos' => 1,
            'authorized_by_user_id' => $user->id, 'created_by_user_id' => $user->id,
        ]);

        $order->update(['status' => 'enviado']);

        $messages = $this->sent();
        $this->assertCount(1, $messages);
        $html = $messages[0]->getOriginalMessage()->getHtmlBody();
        $this->assertStringContainsString('Estafeta', $html);
        $this->assertStringContainsString('AB 123', $html);
        $this->assertStringContainsString('https://rastreo.test/?guia=AB123', $html);
    }

    public function test_un_evento_apagado_no_sale_y_los_textos_editados_si(): void
    {
        $this->enable([
            'order_emails.paid.subject' => 'Ya pagamos {{order_number}} de {{customer_name}}',
            'order_emails.cancelled.enabled' => '0',
        ]);
        Setting::where('key', 'order_emails.cancelled.enabled')->update(['type' => 'boolean']);
        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);

        $order = $this->order($this->mercadoPago());
        $order->update(['status' => 'pagado']);
        $order->update(['status' => 'cancelado']);

        $messages = $this->sent();
        $this->assertCount(1, $messages);
        $this->assertSame('Ya pagamos ' . $order->order_number . ' de Carlos Herrera', $messages[0]->getOriginalMessage()->getSubject());
    }

    public function test_la_copia_oculta_interna_recibe_el_aviso(): void
    {
        $this->enable(['order_emails.copy_to' => 'ventas@equiterm.test']);
        $this->order($this->transfer());

        $email = $this->sent()[0]->getOriginalMessage();
        $this->assertSame('ventas@equiterm.test', $email->getBcc()[0]->getAddress());
    }

    // ── Fallos ───────────────────────────────────────────────────────────

    public function test_un_fallo_del_correo_no_rompe_el_pedido_y_queda_registrado(): void
    {
        $this->enable();
        config(['mail.from.address' => '']); // sin remitente: smtpProblem() lo detecta

        $order = $this->order($this->transfer());

        $this->assertNotNull(StoreOrder::find($order->id));
        $log = StoreOrderEmail::firstOrFail();
        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('remitente', $log->error);
        $this->assertCount(0, $this->sent());
    }

    public function test_pedido_sin_correo_valido_se_omite(): void
    {
        $this->enable();
        $this->order($this->transfer(), ['contact_email' => '']);

        $this->assertSame('skipped', StoreOrderEmail::firstOrFail()->status);
        $this->assertCount(0, $this->sent());
    }

    // ── Pantalla de Ecommerce ────────────────────────────────────────────

    public function test_la_pantalla_guarda_y_valida_la_configuracion(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.order-emails.index'))->assertOk()->assertSee('Correos de pedidos');

        $payload = ['enabled' => '1', 'copy_to' => 'ventas@equiterm.test', 'footer' => 'Horario 9 a 6', 'events' => []];
        foreach (StoreOrderMailService::EVENTS as $event) {
            $payload['events'][$event] = ['enabled' => '1', 'subject' => "Asunto $event", 'heading' => "Título $event", 'intro' => "Mensaje $event"];
        }

        $this->actingAs($admin)->put(route('admin.order-emails.update'), $payload)->assertRedirect(route('admin.order-emails.index'));

        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);
        $this->assertTrue(app(StoreOrderMailService::class)->enabled());
        $this->assertSame('Asunto paid', app(StoreOrderMailService::class)->eventSettings('paid')['subject']);
        $this->assertSame('Horario 9 a 6', app(StoreOrderMailService::class)->footer());
        $this->assertSame('integraciones', Setting::where('key', 'order_emails.enabled')->value('group_name'));

        // Una copia mal escrita no se guarda.
        $bad = $payload;
        $bad['copy_to'] = 'no-es-correo';
        $this->actingAs($admin)->put(route('admin.order-emails.update'), $bad)->assertSessionHasErrors('copy_to');
    }

    public function test_vista_previa_y_prueba_usan_un_pedido_de_ejemplo(): void
    {
        $admin = $this->admin();
        $this->transfer();

        $this->actingAs($admin)->get(route('admin.order-emails.preview', 'created_offline'))
            ->assertOk()->assertSee('PW-EJEMPLO-0001')->assertSee('BBVA');
        $this->actingAs($admin)->get(route('admin.order-emails.preview', 'inexistente'))->assertNotFound();

        $this->actingAs($admin)->post(route('admin.order-emails.test'), ['event' => 'shipped', 'to' => 'yo@equiterm.test'])
            ->assertSessionHasNoErrors();

        $messages = $this->sent();
        $this->assertCount(1, $messages);
        $this->assertStringStartsWith('[PRUEBA] ', $messages[0]->getOriginalMessage()->getSubject());
        $this->assertSame(0, StoreOrderEmail::count()); // la prueba no se registra como correo de un pedido
    }

    public function test_reenviar_manda_de_nuevo_aunque_ya_haya_salido(): void
    {
        $admin = $this->admin();
        $this->enable();
        $order = $this->order($this->transfer());
        $log = StoreOrderEmail::firstOrFail();
        $this->assertCount(1, $this->sent());

        $this->actingAs($admin)->post(route('admin.order-emails.resend', $log->id))->assertSessionHasNoErrors();

        $this->assertCount(2, $this->sent());
        $this->assertSame(2, StoreOrderEmail::where('store_order_id', $order->id)->where('status', 'sent')->count());
    }

    // ── Correo al cliente activo por defecto + aviso interno al equipo ───────────────────────────────────

    private function staffOn(array $settings = []): void
    {
        config(['order_emails.staff.enabled' => true]);
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value, 'type' => str_ends_with($key, 'enabled') ? 'boolean' : 'string', 'group_name' => 'integraciones']);
        }
        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);
    }

    /** @return array<string,\Symfony\Component\Mime\Email> correos enviados indexados por el primer destinatario */
    private function sentByRecipient(): array
    {
        $out = [];
        foreach ($this->sent() as $m) {
            $email = $m->getOriginalMessage();
            $out[$email->getTo()[0]->getAddress()] = $email;
        }

        return $out;
    }

    public function test_sin_configurar_nada_el_cliente_ya_recibe_su_correo(): void
    {
        $this->order($this->transfer()); // sin llamar a enable(): los correos al cliente vienen activos

        $messages = $this->sent();
        $this->assertCount(1, $messages);
        $this->assertSame('carlos@cliente.com', $messages[0]->getOriginalMessage()->getTo()[0]->getAddress());
    }

    public function test_pedido_nuevo_avisa_al_equipo_por_defecto_a_administracion(): void
    {
        $this->staffOn();
        $order = $this->order($this->transfer());

        $by = $this->sentByRecipient();
        $this->assertCount(2, $by);
        $this->assertArrayHasKey('carlos@cliente.com', $by);

        $staff = $by['administracion@equitermindustries.com.mx'];
        $this->assertStringContainsString('Nuevo pedido ' . $order->order_number, $staff->getSubject());
        $this->assertStringContainsString('Carlos Herrera', $staff->getSubject());
        $this->assertSame('carlos@cliente.com', $staff->getReplyTo()[0]->getAddress()); // el equipo responde al cliente
        $this->assertStringContainsString('5551112222', $staff->getTextBody());
        $this->assertStringContainsString('Caldera 10HP', $staff->getHtmlBody());
        $this->assertStringContainsString(route('admin.orders.show', $order), $staff->getHtmlBody());

        $log = StoreOrderEmail::where('event', 'staff_new_order')->firstOrFail();
        $this->assertSame('sent', $log->status);
        $this->assertSame('administracion@equitermindustries.com.mx', $log->to_email);
    }

    public function test_el_aviso_al_equipo_sale_aunque_los_correos_al_cliente_esten_apagados(): void
    {
        $this->staffOn(['order_emails.enabled' => '0']);
        $this->order($this->transfer());

        $by = $this->sentByRecipient();
        $this->assertSame(['administracion@equitermindustries.com.mx'], array_keys($by));
    }

    public function test_el_destino_del_aviso_se_puede_cambiar_y_acepta_varios(): void
    {
        $this->staffOn(['order_emails.staff_to' => 'ventas@equiterm.test, Gerencia@Equiterm.test']);
        $this->order($this->transfer());

        $staffMsg = collect($this->sent())->map->getOriginalMessage()->first(fn ($e) => str_contains($e->getSubject(), 'Nuevo pedido'));
        $to = collect($staffMsg->getTo())->map->getAddress()->all();
        $this->assertSame(['ventas@equiterm.test', 'gerencia@equiterm.test'], $to);
    }

    public function test_el_aviso_al_equipo_se_puede_apagar(): void
    {
        $this->staffOn(['order_emails.staff_enabled' => '0']);
        $this->order($this->transfer());

        $this->assertSame(['carlos@cliente.com'], array_keys($this->sentByRecipient()));
        $this->assertSame(0, StoreOrderEmail::where('event', 'staff_new_order')->count());
    }

    public function test_pedido_de_mercado_pago_avisa_al_equipo_hasta_que_se_paga_y_una_sola_vez(): void
    {
        $this->staffOn();
        $order = $this->order($this->mercadoPago());
        $this->assertCount(0, $this->sent()); // pago abandonado: no avisa

        $order->update(['status' => 'pagado']);
        $order->update(['status' => 'en_preparacion']);
        $order->update(['status' => 'pagado']);

        $by = $this->sentByRecipient();
        $this->assertArrayHasKey('administracion@equitermindustries.com.mx', $by);
        $this->assertStringContainsString('Pagado en línea', $by['administracion@equitermindustries.com.mx']->getTextBody() . $by['administracion@equitermindustries.com.mx']->getHtmlBody());
        $this->assertSame(1, StoreOrderEmail::where('event', 'staff_new_order')->where('status', 'sent')->count());
    }

    public function test_un_fallo_del_aviso_al_equipo_no_rompe_el_pedido_y_queda_registrado(): void
    {
        $this->staffOn(['order_emails.staff_to' => 'esto-no-es-un-correo']);
        $order = $this->order($this->transfer());

        $this->assertNotNull(StoreOrder::find($order->id));
        $log = StoreOrderEmail::where('event', 'staff_new_order')->firstOrFail();
        $this->assertSame('skipped', $log->status);
    }

    public function test_la_pantalla_guarda_el_destino_del_aviso_y_valida_los_correos(): void
    {
        $admin = $this->admin();
        $payload = ['enabled' => '1', 'staff_enabled' => '1', 'staff_to' => 'ventas@equiterm.test', 'events' => []];
        foreach (StoreOrderMailService::EVENTS as $event) {
            $payload['events'][$event] = ['enabled' => '1', 'subject' => "Asunto $event", 'heading' => "Título $event", 'intro' => "Mensaje $event"];
        }

        $this->actingAs($admin)->put(route('admin.order-emails.update'), $payload)->assertSessionHasNoErrors();
        (new ReflectionProperty(Setting::class, 'cache'))->setValue(null, []);
        $this->assertSame('ventas@equiterm.test', app(StoreOrderMailService::class)->staffTo());
        $this->assertTrue(app(StoreOrderMailService::class)->staffEnabled());

        $this->actingAs($admin)->put(route('admin.order-emails.update'), ['staff_to' => 'malo@'] + $payload)->assertSessionHasErrors('staff_to');

        $this->actingAs($admin)->get(route('admin.order-emails.index'))->assertOk()
            ->assertSee('Avisar al equipo cuando entre un pedido nuevo');
    }

    public function test_reenviar_el_aviso_interno_manda_el_correo_del_equipo(): void
    {
        $admin = $this->admin();
        $this->staffOn();
        $this->order($this->transfer());
        $log = StoreOrderEmail::where('event', 'staff_new_order')->firstOrFail();
        $before = count($this->sent());

        $this->actingAs($admin)->post(route('admin.order-emails.resend', $log->id))->assertSessionHasNoErrors();

        $this->assertCount($before + 1, $this->sent());
        $last = collect($this->sent())->last()->getOriginalMessage();
        $this->assertSame('administracion@equitermindustries.com.mx', $last->getTo()[0]->getAddress());
    }
}
