<?php

namespace Tests\Feature\ServiceRatingsPublish;

use App\Models\Role;
use App\Models\ServicePage;
use App\Models\ServicePageReview;
use App\Models\ServiceRating;
use App\Models\ServiceRatingAnswer;
use App\Models\ServiceSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Publicación de opiniones de clientes (ServiceRating) como reseñas de la
 * página de servicio + bloque rating_reviews (modo selected / desglose).
 */
class ServiceRatingsPublishTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private int $reportSeq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->admin = $this->adminUser();
    }

    private function adminUser(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'position' => 'Administrador',
            'phone' => '5555555555', 'email' => 'admin-' . uniqid() . '@example.com',
            'password' => bcrypt('password'), 'status' => 'active', 'rfc' => 'XAXX010101000',
            'role_id' => $role->id,
        ]);
    }

    private function page(string $slug = 'limpieza'): ServicePage
    {
        return ServicePage::create([
            'name' => 'Servicio ' . $slug, 'slug' => $slug, 'page_type' => ServicePage::TYPE_SERVICE, 'is_active' => true,
        ]);
    }

    private function rating(ServicePage $page, array $over = [], array $answers = []): ServiceRating
    {
        $this->reportSeq++;
        $reportId = DB::table('service_reports')->insertGetId([
            'report_number' => 'SR-T' . $this->reportSeq . uniqid(),
            'created_by_user_id' => $this->admin->id,
            'assigned_user_id' => $this->admin->id,
            'customer_name' => 'Cliente ' . $this->reportSeq,
            'service_type' => 'cleaning',
            'service_date' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $rating = ServiceRating::create(array_merge([
            'service_report_id' => $reportId,
            'service_page_id' => $page->id,
            'customer_name' => 'Cliente ' . $this->reportSeq,
            'rating' => 5,
            'card_label' => 'Excelente',
            'comment' => 'Muy buen servicio ' . $this->reportSeq,
            'status' => ServiceRating::STATUS_SUBMITTED,
            'submitted_at' => now()->subDays(10 - $this->reportSeq),
        ], $over));

        foreach ($answers as $label => $score) {
            ServiceRatingAnswer::create(['service_rating_id' => $rating->id, 'question_label' => $label, 'score' => $score]);
        }

        return $rating;
    }

    private function publish(ServicePage $page, ServiceRating $r)
    {
        return $this->actingAs($this->admin)->postJson(route('admin.service-pages.client-ratings.publish', [$page, $r]));
    }

    private function hide(ServicePage $page, ServiceRating $r)
    {
        return $this->actingAs($this->admin)->postJson(route('admin.service-pages.client-ratings.hide', [$page, $r]));
    }

    private function blockHtml(ServicePage $page, array $config): string
    {
        $section = new ServiceSection(['type' => 'rating_reviews', 'title' => null, 'config' => $config, 'is_active' => true]);
        $section->id = 1;

        return view('frontend.shop.home.sections.rating-reviews', [
            'section' => $section, 'servicePage' => $page->fresh(), 'previewMode' => false,
        ])->render();
    }

    public function test_lista_solo_opiniones_enviadas_de_esa_pagina(): void
    {
        $a = $this->page('a');
        $b = $this->page('b');
        $mine = $this->rating($a, [], ['Limpieza' => 5]);
        $this->rating($b);
        $this->rating($a, ['status' => ServiceRating::STATUS_PENDING]);

        $res = $this->actingAs($this->admin)->getJson(route('admin.service-pages.client-ratings.index', $a))->assertOk();
        $this->assertCount(1, $res->json('ratings'));
        $this->assertSame($mine->id, $res->json('ratings.0.id'));
        $this->assertSame([['label' => 'Limpieza', 'score' => 5]], $res->json('ratings.0.answers'));
        $this->assertFalse($res->json('ratings.0.is_published'));
    }

    public function test_publicar_crea_resena_enlazada_visible_y_afecta_json_ld(): void
    {
        $page = $this->page();
        ServiceSection::create(['service_page_id' => $page->id, 'type' => 'rating_reviews', 'config' => [], 'sort_order' => 0, 'is_active' => true]);
        $r = $this->rating($page, ['rating' => 4, 'customer_name' => 'Juan Pérez']);

        $this->publish($page, $r)->assertOk()->assertJson(['success' => true]);

        $review = ServicePageReview::where('service_rating_id', $r->id)->firstOrFail();
        $this->assertSame('client', $review->source);
        $this->assertTrue($review->is_visible);
        $this->assertTrue($review->is_verified);
        $this->assertSame('Juan Pérez', $review->customer_name);
        $this->assertSame(4, $review->rating);
        $this->assertSame($r->comment, $review->comment);
        $this->assertSame($review->id, $r->fresh()->service_page_review_id);

        $html = $this->get($page->publicPath())->assertOk()->getContent();
        $this->assertStringContainsString('"reviewCount": 1', preg_replace('/"reviewCount":\s*/', '"reviewCount": ', $html));
    }

    public function test_ocultar_no_borra_y_republicar_reactiva_la_misma(): void
    {
        $page = $this->page();
        $r = $this->rating($page);
        $this->publish($page, $r)->assertOk();
        $reviewId = ServicePageReview::first()->id;

        $this->hide($page, $r)->assertOk();
        $this->assertSame(1, ServicePageReview::count());
        $this->assertFalse(ServicePageReview::find($reviewId)->is_visible);

        $this->publish($page, $r)->assertOk();
        $this->assertSame(1, ServicePageReview::count());
        $this->assertTrue(ServicePageReview::find($reviewId)->is_visible);
    }

    public function test_no_publica_opinion_de_otra_pagina_ni_pendiente(): void
    {
        $a = $this->page('a');
        $b = $this->page('b');
        $other = $this->rating($b);
        $pending = $this->rating($a, ['status' => ServiceRating::STATUS_PENDING]);

        $this->publish($a, $other)->assertNotFound();
        $this->publish($a, $pending)->assertNotFound();
        $this->assertSame(0, ServicePageReview::count());
    }

    public function test_reordenar_opiniones_mostradas(): void
    {
        $page = $this->page();
        $r1 = $this->rating($page);
        $r2 = $this->rating($page);
        $r3 = $this->rating($page);
        foreach ([$r1, $r2, $r3] as $r) {
            $this->publish($page, $r)->assertOk();
        }
        $ids = [$r1, $r2, $r3] + [];
        $rev = fn ($r) => ServicePageReview::where('service_rating_id', $r->id)->first();

        $this->actingAs($this->admin)->postJson(route('admin.service-pages.client-ratings.reorder', $page), [
            'order' => [$rev($r3)->id, $rev($r1)->id, $rev($r2)->id],
        ])->assertOk();

        $this->assertSame(
            [$r3->id, $r1->id, $r2->id],
            $page->reviews()->get()->pluck('service_rating_id')->all()
        );

        // ids ajenos o manuales -> 422
        $manual = ServicePageReview::create(['service_page_id' => $page->id, 'customer_name' => 'M', 'rating' => 5, 'comment' => 'x', 'sort_order' => 9]);
        $this->actingAs($this->admin)->postJson(route('admin.service-pages.client-ratings.reorder', $page), [
            'order' => [$manual->id],
        ])->assertStatus(422);
    }

    public function test_bloque_modo_selected_respeta_elegidas_orden_e_ignora_ocultas(): void
    {
        $page = $this->page();
        $mk = fn ($name, $vis = true) => ServicePageReview::create([
            'service_page_id' => $page->id, 'customer_name' => $name, 'rating' => 5,
            'comment' => 'Comentario de ' . $name, 'is_visible' => $vis, 'sort_order' => 0,
        ]);
        $uno = $mk('Alfa Uno');
        $dos = $mk('Beta Dos');
        $tres = $mk('Gamma Tres');
        $oculta = $mk('Delta Oculta', false);

        $html = $this->blockHtml($page, ['mode' => 'selected', 'review_ids' => [$tres->id, $oculta->id, 9999, $uno->id]]);

        $this->assertStringContainsString('Gamma Tres', $html);
        $this->assertStringContainsString('Alfa Uno', $html);
        $this->assertStringNotContainsString('Beta Dos', $html);
        $this->assertStringNotContainsString('Delta Oculta', $html);
        $this->assertLessThan(strpos($html, 'Alfa Uno'), strpos($html, 'Gamma Tres'));

        // auto (regresión): todas las visibles
        $auto = $this->blockHtml($page, []);
        $this->assertStringContainsString('Beta Dos', $auto);
        $this->assertStringNotContainsString('Delta Oculta', $auto);
    }

    public function test_show_breakdown_calcula_medias(): void
    {
        $page = $this->page();
        $r1 = $this->rating($page, [], ['Limpieza' => 5, 'Ordenado' => 4]);
        $r2 = $this->rating($page, [], ['Limpieza' => 4, 'Ordenado' => 4]);
        $r3 = $this->rating($page, [], ['Limpieza' => 1]); // no publicada
        $this->publish($page, $r1);
        $this->publish($page, $r2);

        $breakdown = ServicePageReview::breakdownFor($page->visibleReviews()->get());
        $this->assertSame(['Limpieza' => 4.5, 'Ordenado' => 4.0], $breakdown->pluck('average', 'label')->all());

        $on = $this->blockHtml($page, ['show_breakdown' => true]);
        $this->assertStringContainsString('Promedio por aspecto', $on);
        $this->assertStringContainsString('4.5', $on);

        $off = $this->blockHtml($page, ['show_breakdown' => false]);
        $this->assertStringNotContainsString('Promedio por aspecto', $off);
    }

    public function test_resena_de_cliente_no_permite_editar_nombre_ni_comentario(): void
    {
        $page = $this->page();
        $r = $this->rating($page, ['customer_name' => 'Nombre Real', 'comment' => 'Original']);
        $this->publish($page, $r)->assertOk();
        $review = ServicePageReview::first();

        $this->actingAs($this->admin)->putJson(route('admin.service-pages.reviews.update', [$page, $review]), [
            'customer_name' => 'Otro', 'comment' => 'Cambiado', 'rating' => 1,
            'business_response' => 'Gracias', 'is_visible' => true,
        ])->assertOk();

        $review->refresh();
        $this->assertSame('Nombre Real', $review->customer_name);
        $this->assertSame('Original', $review->comment);
        $this->assertSame(5, $review->rating);
        $this->assertSame('Gracias', $review->business_response);
        $this->assertTrue($review->is_verified);
    }

    public function test_borrar_la_resena_libera_el_enlace(): void
    {
        $page = $this->page();
        $r = $this->rating($page);
        $this->publish($page, $r)->assertOk();
        $review = ServicePageReview::first();

        $this->actingAs($this->admin)->deleteJson(route('admin.service-pages.reviews.destroy', [$page, $review]))->assertOk();
        $this->assertNull($r->fresh()->service_page_review_id);

        $this->publish($page, $r)->assertOk();
        $this->assertSame(1, ServicePageReview::count());
    }

    public function test_requiere_autenticacion_y_permiso(): void
    {
        $page = $this->page();
        $r = $this->rating($page);

        $this->postJson(route('admin.service-pages.client-ratings.publish', [$page, $r]))->assertUnauthorized();

        $role = Role::create(['name_role' => 'Vendedor', 'name_role_es' => 'Vendedor']);
        $user = User::create([
            'first_name' => 'Sin', 'last_name' => 'Permiso', 'position' => 'x', 'phone' => '5555555556',
            'email' => 'np-' . uniqid() . '@example.com', 'password' => bcrypt('password'),
            'status' => 'active', 'rfc' => 'XAXX010101001', 'role_id' => $role->id,
        ]);
        $this->actingAs($user)->postJson(route('admin.service-pages.client-ratings.publish', [$page, $r]))
            ->assertStatus(403);
        $this->assertSame(0, ServicePageReview::count());
    }
}
