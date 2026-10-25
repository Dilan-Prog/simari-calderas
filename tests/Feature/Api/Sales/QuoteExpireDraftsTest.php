<?php

namespace Tests\Feature\Api\Sales;

use App\Models\ApiClient;
use App\Models\Quote;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** POST /api/v1/quotes/expire-drafts: pasa a "Vencida" los borradores viejos (flujo de limpieza de N8N). */
class QuoteExpireDraftsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);
        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'A', 'phone' => '5555555555',
            'email' => 'admin-' . uniqid() . '@example.com', 'password' => bcrypt('x'), 'status' => 'active',
            'rfc' => 'XAXX010101000', 'role_id' => $role->id,
        ]);
    }

    private function token(array $abilities): void
    {
        $client = ApiClient::create(['name' => 'N8N Test', 'is_active' => true, 'created_by' => $this->admin->id]);
        $this->withToken($client->createToken('t', $abilities)->plainTextToken);
    }

    private function quote(string $number, string $status, int $daysOld): Quote
    {
        $q = Quote::create([
            'quote_number' => $number, 'status' => $status, 'created_by_user_id' => $this->admin->id,
            'guest_name' => 'Cliente ' . $number, 'total' => 100, 'currency' => 'MXN',
        ]);
        Quote::whereKey($q->id)->update(['created_at' => now()->subDays($daysOld)]);

        return $q->fresh();
    }

    public function test_requiere_token_y_la_ability_de_escritura(): void
    {
        $this->postJson('/api/v1/quotes/expire-drafts')->assertStatus(401);

        $this->token(['quotes:read']);
        $this->postJson('/api/v1/quotes/expire-drafts')->assertStatus(403);
    }

    public function test_expira_solo_los_borradores_con_mas_de_n_dias(): void
    {
        $viejo = $this->quote('COT-1', 'draft', 45);
        $limite = $this->quote('COT-2', 'draft', 31);
        $reciente = $this->quote('COT-3', 'draft', 10);
        $enviada = $this->quote('COT-4', 'sent', 90);
        $aceptada = $this->quote('COT-5', 'accepted', 90);
        $this->token(['quotes:write']);

        $json = $this->postJson('/api/v1/quotes/expire-drafts', ['days' => 30])->assertOk()->json('data');

        $this->assertSame(2, $json['processed']);
        $this->assertSame(0, $json['remaining']);
        $this->assertEqualsCanonicalizing([$viejo->id, $limite->id], array_column($json['quotes'], 'id'));
        $this->assertSame('expired', $viejo->fresh()->status);
        $this->assertSame('expired', $limite->fresh()->status);
        $this->assertSame('draft', $reciente->fresh()->status);
        $this->assertSame('sent', $enviada->fresh()->status);
        $this->assertSame('accepted', $aceptada->fresh()->status);
    }

    public function test_usa_30_dias_por_defecto_y_es_idempotente(): void
    {
        $this->quote('COT-1', 'draft', 40);
        $this->token(['quotes:write']);

        $this->postJson('/api/v1/quotes/expire-drafts')->assertOk()->assertJsonPath('data.days', 30)->assertJsonPath('data.processed', 1);
        $this->postJson('/api/v1/quotes/expire-drafts')->assertOk()->assertJsonPath('data.processed', 0)->assertJsonPath('data.matched', 0);
    }

    public function test_dry_run_solo_lista_sin_cambiar_nada(): void
    {
        $q = $this->quote('COT-1', 'draft', 40);
        $this->token(['quotes:write']);

        $this->postJson('/api/v1/quotes/expire-drafts', ['days' => 30, 'dry_run' => true])
            ->assertOk()->assertJsonPath('data.dry_run', true)->assertJsonPath('data.matched', 1)->assertJsonPath('data.quotes.0.status', 'draft');

        $this->assertSame('draft', $q->fresh()->status);
    }

    public function test_limit_acota_cuantas_procesa_y_reporta_las_restantes(): void
    {
        foreach (range(1, 3) as $i) {
            $this->quote('COT-' . $i, 'draft', 50);
        }
        $this->token(['quotes:write']);

        $this->postJson('/api/v1/quotes/expire-drafts', ['days' => 30, 'limit' => 2])
            ->assertOk()->assertJsonPath('data.processed', 2)->assertJsonPath('data.remaining', 1);

        $this->assertSame(2, Quote::where('status', 'expired')->count());
    }

    public function test_valida_los_parametros(): void
    {
        $this->token(['quotes:write']);

        $this->postJson('/api/v1/quotes/expire-drafts', ['days' => 0])->assertStatus(422)->assertJsonValidationErrors('days');
        $this->postJson('/api/v1/quotes/expire-drafts', ['days' => 'abc'])->assertStatus(422);
        $this->postJson('/api/v1/quotes/expire-drafts', ['limit' => 9999])->assertStatus(422)->assertJsonValidationErrors('limit');
    }
}
