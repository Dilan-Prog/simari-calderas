<?php

namespace Tests\Feature\Api;

use App\Models\AdEvent;
use App\Models\AdVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdTrackingElementTest extends TestCase
{
    use RefreshDatabase;

    private function makeVisit(): AdVisit
    {
        return AdVisit::create([
            'visitor_uuid' => (string) Str::uuid(),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'expires_at' => now()->addDays(90),
        ]);
    }

    private function sendEvent(AdVisit $visit, array $extra = [])
    {
        RateLimiter::clear('ad-event:' . $visit->visitor_uuid);

        return $this->postJson('/api/v1/ad-tracking/event', array_merge([
            'visitor_uuid' => $visit->visitor_uuid,
            'event_type' => 'whatsapp_click',
            'url' => 'https://example.test/',
        ], $extra));
    }

    public function test_guarda_el_elemento_valido(): void
    {
        $visit = $this->makeVisit();

        $this->sendEvent($visit, ['element' => 'flotante'])->assertOk();

        $this->assertSame('flotante', AdEvent::where('ad_visit_id', $visit->id)->first()->element);
    }

    public function test_un_elemento_invalido_se_guarda_como_null(): void
    {
        $visit = $this->makeVisit();

        $this->sendEvent($visit, ['element' => 'inventado'])->assertOk();

        $event = AdEvent::where('ad_visit_id', $visit->id)->first();
        $this->assertNotNull($event);
        $this->assertNull($event->element);
    }

    public function test_sin_elemento_sigue_funcionando(): void
    {
        $visit = $this->makeVisit();

        $this->sendEvent($visit)->assertOk();

        $event = AdEvent::where('ad_visit_id', $visit->id)->first();
        $this->assertNotNull($event);
        $this->assertSame('whatsapp_click', $event->event_type);
        $this->assertNull($event->element);
    }
}
