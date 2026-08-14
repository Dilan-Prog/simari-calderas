<?php

namespace Tests\Feature\Workflows;

use App\Models\Deal;
use App\Models\WhatsappConversation;
use App\Services\AutomatableModuleRegistry;
use Tests\TestCase;

/**
 * Tests de AutomatableModuleRegistry (Fase 16) -- confirma que el registro
 * central resuelve correctamente las 2 entradas CRM migradas desde los
 * observers bespoke (DealObserver/WhatsappConversationObserver). No prueba
 * todavía entradas de Ecommerce/Servicios/ERP: esas se agregan en Fase 20.
 */
class AutomatableModuleRegistryTest extends TestCase
{
    public function test_type_for_model_resolves_deal(): void
    {
        $registry = app(AutomatableModuleRegistry::class);

        $this->assertSame('deal', $registry->typeForModel(new Deal()));
    }

    public function test_type_for_model_resolves_whatsapp_conversation(): void
    {
        $registry = app(AutomatableModuleRegistry::class);

        $this->assertSame('whatsapp_conversation', $registry->typeForModel(new WhatsappConversation()));
    }

    public function test_registry_has_the_two_crm_entries(): void
    {
        $registry = app(AutomatableModuleRegistry::class);

        $all = $registry->all();

        $this->assertArrayHasKey('deal', $all);
        $this->assertArrayHasKey('whatsapp_conversation', $all);

        $this->assertSame(Deal::class, $all['deal']['model']);
        $this->assertSame(WhatsappConversation::class, $all['whatsapp_conversation']['model']);

        $this->assertSame('CRM', $all['deal']['group']);
        $this->assertSame('CRM', $all['whatsapp_conversation']['group']);

        $this->assertTrue($all['deal']['supports_stale']);
        $this->assertSame('updated_at', $all['deal']['stale_field']);
        $this->assertTrue($all['whatsapp_conversation']['supports_stale']);
        $this->assertSame('last_message_at', $all['whatsapp_conversation']['stale_field']);
    }

    public function test_fields_for_derives_from_model_fillable(): void
    {
        $registry = app(AutomatableModuleRegistry::class);

        $dealFields = $registry->fieldsFor('deal');

        $this->assertContains('folio', $dealFields);
        $this->assertContains('customer_id', $dealFields);
    }

    public function test_label_and_group_helpers(): void
    {
        $registry = app(AutomatableModuleRegistry::class);

        $this->assertSame('Negocio', $registry->label('deal'));
        $this->assertSame('CRM', $registry->group('whatsapp_conversation'));
        $this->assertNull($registry->label('nonexistent_type'));
    }
}
