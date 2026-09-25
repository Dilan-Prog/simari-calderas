<?php

namespace Tests\Feature\Api\Marketing;

use App\Jobs\SendMarketingEmailJob;
use App\Models\ApiClient;
use App\Models\Customer;
use App\Models\EmailCampaign;
use App\Models\EmailList;
use App\Models\EmailSend;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Fase 6 del plan de integración N8N:
 * - POST /api/v1/email-campaigns/{campaign}/send (dispara EmailCampaignService::send(),
 *   el mismo que usa el panel -- se hace Queue::fake() de SendMarketingEmailJob para no
 *   renderizar/enviar correos reales en el test, igual criterio que otros tests de Marketing
 *   evitan pegarle a servicios externos).
 * - GET /api/v1/email-sends (lectura, filtro campaign_id).
 */
class EmailCampaignControllerTest extends TestCase
{
    use RefreshDatabase;

    private function tokenWithAbilities(array $abilities): string
    {
        $client = ApiClient::create([
            'name'      => 'N8N test client',
            'is_active' => true,
        ]);

        return $client->createToken('test-token', $abilities)->plainTextToken;
    }

    private function makeCustomer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'first_name'    => 'Juan',
            'last_name'     => 'Perez',
            'email'         => 'juan.perez.' . uniqid() . '@example.com',
            'phone'         => '5555555555',
            'document_type' => 'RFC',
            'status'        => 'active',
            'source'        => 'test',
            'company'       => 'ACME',
        ], $overrides));
    }

    private function makeCampaign(Customer $customer): EmailCampaign
    {
        $list = EmailList::create([
            'name' => 'Lista de prueba',
            'type' => 'static',
        ]);

        $list->members()->attach($customer->id, ['added_at' => now()]);

        return EmailCampaign::create([
            'name'    => 'Campaña de prueba',
            'list_id' => $list->id,
            'status'  => 'draft',
        ]);
    }

    // --- POST /email-campaigns/{campaign}/send ---------------------------

    public function test_send_without_token_is_rejected(): void
    {
        $campaign = $this->makeCampaign($this->makeCustomer());

        $response = $this->postJson("/api/v1/email-campaigns/{$campaign->id}/send");

        $response->assertStatus(401);
    }

    public function test_send_with_wrong_ability_is_forbidden(): void
    {
        $campaign = $this->makeCampaign($this->makeCustomer());
        $token = $this->tokenWithAbilities(['email-campaigns:read']);

        $response = $this->withToken($token)->postJson("/api/v1/email-campaigns/{$campaign->id}/send");

        $response->assertStatus(403);
    }

    public function test_send_with_correct_ability_dispatches_job_per_recipient_and_marks_campaign_sent(): void
    {
        Queue::fake();

        $customer = $this->makeCustomer();
        $campaign = $this->makeCampaign($customer);
        $token = $this->tokenWithAbilities(['email-campaigns:trigger']);

        $response = $this->withToken($token)->postJson("/api/v1/email-campaigns/{$campaign->id}/send");

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'sent');

        $this->assertDatabaseHas('email_campaigns', [
            'id'     => $campaign->id,
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('email_sends', [
            'email_campaign_id' => $campaign->id,
            'customer_id'       => $customer->id,
        ]);

        Queue::assertPushed(SendMarketingEmailJob::class, 1);
    }

    // --- GET /email-sends --------------------------------------------------

    public function test_sends_index_without_token_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/email-sends');

        $response->assertStatus(401);
    }

    public function test_sends_index_with_wrong_ability_is_forbidden(): void
    {
        $token = $this->tokenWithAbilities(['email-campaigns:trigger']);

        $response = $this->withToken($token)->getJson('/api/v1/email-sends');

        $response->assertStatus(403);
    }

    public function test_sends_index_with_correct_ability_filters_by_campaign(): void
    {
        $customer = $this->makeCustomer();
        $campaign = $this->makeCampaign($customer);
        $otherCampaign = $this->makeCampaign($this->makeCustomer());

        $send = EmailSend::create([
            'email_campaign_id' => $campaign->id,
            'customer_id'       => $customer->id,
        ]);

        EmailSend::create([
            'email_campaign_id' => $otherCampaign->id,
            'customer_id'       => $customer->id,
        ]);

        $token = $this->tokenWithAbilities(['email-campaigns:read']);

        $response = $this->withToken($token)->getJson("/api/v1/email-sends?campaign_id={$campaign->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $send->id);
    }
}
