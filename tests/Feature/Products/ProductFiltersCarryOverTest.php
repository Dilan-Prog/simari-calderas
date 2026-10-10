<?php

namespace Tests\Feature\Products;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesProductBlockFixtures;
use Tests\TestCase;

/**
 * "Editar en lote" y "Volver a Productos" conservan los filtros/búsqueda
 * actuales (sin la página): el editor por lotes usa la misma consulta
 * filtrada (ProductFilters), así que abre el mismo conjunto de productos.
 */
class ProductFiltersCarryOverTest extends TestCase
{
    use RefreshDatabase;
    use CreatesProductBlockFixtures;

    private function adminUser(): User
    {
        $role = Role::create(['name_role' => 'Administrador', 'name_role_es' => 'Administrador']);

        return User::create([
            'first_name' => 'Admin',
            'last_name'  => 'Test',
            'position'   => 'Administrador',
            'phone'      => '5555555555',
            'email'      => 'admin-' . uniqid() . '@example.com',
            'password'   => bcrypt('password'),
            'status'     => 'active',
            'rfc'        => 'XAXX010101000',
            'role_id'    => $role->id,
        ]);
    }

    /** Query string de la primera ocurrencia de href que contiene $needle. */
    private function hrefQuery(string $html, string $needle): array
    {
        preg_match_all('/href="([^"]*' . preg_quote($needle, '/') . '[^"]*)"/', $html, $m);
        $this->assertNotEmpty($m[1], "No hay enlace a {$needle}");

        parse_str((string) parse_url(html_entity_decode($m[1][0]), PHP_URL_QUERY), $query);

        return $query;
    }

    public function test_editar_en_lote_lleva_los_filtros_actuales_sin_la_pagina(): void
    {
        $this->makeProduct();

        $html = $this->actingAs($this->adminUser())
            ->get(route('admin.products.index', ['search' => 'filtro', 'brand_id' => [4], 'price_min' => '100', 'page' => 2]))
            ->assertOk()
            ->getContent();

        $query = $this->hrefQuery($html, 'edicion-masiva');

        $this->assertSame('filtro', $query['search'] ?? null);
        $this->assertEquals(['4'], $query['brand_id'] ?? null);
        $this->assertSame('100', $query['price_min'] ?? null);
        $this->assertArrayNotHasKey('page', $query);
    }

    public function test_volver_a_productos_conserva_los_filtros_del_editor_por_lotes(): void
    {
        $this->makeProduct();

        $html = $this->actingAs($this->adminUser())
            ->get(route('admin.products.bulk-edit', ['search' => 'filtro', 'brand_id' => [4], 'price_min' => '100']))
            ->assertOk()
            ->getContent();

        // El enlace "Volver a Productos" apunta al listado (no a edicion-masiva).
        preg_match_all('/href="([^"]*\/admin\/productos(?:\?[^"]*)?)"[^>]*>\s*← Volver a Productos/u', $html, $m);
        $this->assertNotEmpty($m[1], 'No se encontró el enlace "Volver a Productos"');

        parse_str((string) parse_url(html_entity_decode($m[1][0]), PHP_URL_QUERY), $query);

        $this->assertSame('filtro', $query['search'] ?? null);
        $this->assertEquals(['4'], $query['brand_id'] ?? null);
        $this->assertSame('100', $query['price_min'] ?? null);
    }

    public function test_sin_filtros_los_enlaces_siguen_limpios(): void
    {
        $this->makeProduct();
        $admin = $this->adminUser();

        $html = $this->actingAs($admin)->get(route('admin.products.index'))->assertOk()->getContent();
        $this->assertSame([], $this->hrefQuery($html, 'edicion-masiva'));
    }
}
