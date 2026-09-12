<?php

namespace Tests\Feature\Marketing;

use App\Mail\MarketingEmailMailable;
use App\Models\EmailSend;
use App\Services\EmailTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tests de EmailTrackingService::wrapLinksForTracking() (función pura de
 * reescritura de links) y sendTracked() (helper compartido de envío
 * manual: crea el EmailSend, envuelve links + agrega pixel de apertura,
 * y envía vía MarketingEmailMailable).
 */
class EmailTrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): EmailTrackingService
    {
        return app(EmailTrackingService::class);
    }

    // --- wrapLinksForTracking -------------------------------------------

    public function test_wrap_links_rewrites_a_normal_link_to_the_click_route_with_url_encoded(): void
    {
        $original = 'https://example.com/page?foo=bar&baz=1';
        $html = '<a href="' . $original . '">Ver más</a>';

        $result = $this->service()->wrapLinksForTracking($html, 'tok-123');

        $expectedPrefix = route('email.click', ['token' => 'tok-123']);
        $this->assertStringStartsWith($expectedPrefix, $this->extractHref($result));

        // "token" va en la ruta (/e/click/{token}), no en el query string --
        // solo "url" es query param. El query param "url" debe decodificar
        // exactamente a la URL original.
        $query = [];
        parse_str(parse_url($this->extractHref($result), PHP_URL_QUERY), $query);
        $this->assertSame($original, urldecode($query['url']));
    }

    public function test_wrap_links_leaves_mailto_links_untouched(): void
    {
        $html = '<a href="mailto:someone@example.com">Escríbenos</a>';

        $result = $this->service()->wrapLinksForTracking($html, 'tok-123');

        $this->assertSame($html, $result);
    }

    public function test_wrap_links_leaves_tel_links_untouched(): void
    {
        $html = '<a href="tel:+525512345678">Llámanos</a>';

        $result = $this->service()->wrapLinksForTracking($html, 'tok-123');

        $this->assertSame($html, $result);
    }

    public function test_wrap_links_leaves_anchor_only_links_untouched(): void
    {
        $html = '<a href="#section">Ir a la sección</a>';

        $result = $this->service()->wrapLinksForTracking($html, 'tok-123');

        $this->assertSame($html, $result);
    }

    public function test_wrap_links_leaves_the_unsubscribe_link_untouched(): void
    {
        $unsubscribeUrl = route('email.unsubscribe', ['token' => 'abc']);
        $html = '<a href="' . $unsubscribeUrl . '">Darse de baja</a>';

        $result = $this->service()->wrapLinksForTracking($html, 'tok-123');

        $this->assertSame($html, $result);
    }

    private function extractHref(string $html): string
    {
        preg_match('/href="([^"]*)"/', $html, $matches);

        return $matches[1] ?? '';
    }

    // --- sendTracked -------------------------------------------------------

    public function test_send_tracked_creates_email_send_and_sends_tracked_mail(): void
    {
        Mail::fake();

        $rendered = [
            'subject' => 'Asunto de prueba',
            'html'    => '<p>Hola</p><a href="https://example.com/promo">Ver promo</a>',
        ];

        $emailSend = $this->service()->sendTracked(
            'destinatario@example.com',
            $rendered,
            [
                'guest_email' => 'destinatario@example.com',
                'guest_name'  => 'Invitado de prueba',
            ]
        );

        $this->assertInstanceOf(EmailSend::class, $emailSend);
        $this->assertNotEmpty($emailSend->tracking_token);
        $this->assertNotNull($emailSend->sent_at);
        $this->assertDatabaseHas('email_sends', [
            'id'          => $emailSend->id,
            'guest_email' => 'destinatario@example.com',
            'guest_name'  => 'Invitado de prueba',
        ]);

        $expectedClickPrefix = route('email.click', ['token' => $emailSend->tracking_token]);
        $expectedOpenUrl = route('email.open', ['token' => $emailSend->tracking_token]);

        Mail::assertSent(MarketingEmailMailable::class, function (MarketingEmailMailable $mail) use ($expectedClickPrefix, $expectedOpenUrl) {
            $html = $mail->rendered['html'];

            return str_contains($html, 'href="' . $expectedClickPrefix)
                && str_contains($html, '<img src="' . $expectedOpenUrl . '"');
        });
    }
}
