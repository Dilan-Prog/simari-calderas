<?php

namespace Database\Seeders;

use App\Models\HomeSection;
use App\Models\HomeSectionSlide;
use Illuminate\Database\Seeder;

class HomeSectionsSeeder extends Seeder
{
    public function run(): void
    {
        $hero = HomeSection::firstOrCreate(
            ['type' => 'hero_slider'],
            ['title' => null, 'sort_order' => 0, 'is_active' => true]
        );

        $slides = [
            [
                'image_url'       => 'images/products/calderas-industriales-simari/caldera-industrial-simari.jpg',
                'badge_text'      => 'INGENIERÍA TÉRMICA AVANZADA',
                'title'           => 'Soluciones en Calderas, Calentamiento y Tratamiento de Agua para tu Industria',
                'title_highlight' => 'Calderas',
                'description'     => 'Diseño, instalación, mantenimiento y refacciones para plantas térmicas industriales. Más de 20 años entregando proyectos llave en mano en toda la República Mexicana.',
                'link_url'        => route('catalog.index', [], false),
            ],
            [
                'image_url'       => 'images/masstercal-rinnai/productos-rinnai/bombas-calor/bomba-de-calor-para-piscina-bcp-50.jpg',
                'badge_text'      => 'CALENTAMIENTO EFICIENTE',
                'title'           => 'Calentadores y Bombas de Calor Rinnai para tu Operación',
                'title_highlight' => 'Rinnai',
                'description'     => 'Distribuidor autorizado Masstercal Rinnai: calentadores residenciales, comerciales y bombas de calor de alta eficiencia, con instalación certificada.',
                'link_url'        => route('catalog.index', [], false),
            ],
            [
                'image_url'       => 'images/services/tratamiento-agua-calderas/servicio-de-tratamiento-de-agua-para-calderas-industriales.jpg',
                'badge_text'      => 'AGUA PARA PROCESOS INDUSTRIALES',
                'title'           => 'Tratamiento de Agua a la Medida de tu Planta',
                'title_highlight' => 'Agua',
                'description'     => 'Suavizadores, filtros y sistemas anti-incrustantes para proteger tus equipos y optimizar el consumo de agua de tu planta.',
                'link_url'        => route('catalog.index', [], false),
            ],
        ];

        foreach ($slides as $i => $slide) {
            HomeSectionSlide::firstOrCreate(
                ['home_section_id' => $hero->id, 'title' => $slide['title']],
                array_merge($slide, ['home_section_id' => $hero->id, 'sort_order' => $i, 'is_active' => true])
            );
        }

        $sections = [
            ['type' => 'brand_carousel', 'title' => null, 'config' => []],
            ['type' => 'product_carousel', 'title' => 'Productos destacados', 'config' => ['source' => 'featured', 'limit' => 12]],
            ['type' => 'category_grid', 'title' => 'Descubre nuestras categorías', 'config' => []],
            ['type' => 'product_carousel', 'title' => 'Novedades', 'config' => ['source' => 'new', 'limit' => 12]],
            ['type' => 'product_carousel', 'title' => 'Recomendados para ti', 'config' => ['source' => 'recommended', 'limit' => 12]],
        ];

        foreach ($sections as $i => $section) {
            HomeSection::firstOrCreate(
                ['type' => $section['type'], 'title' => $section['title']],
                array_merge($section, ['sort_order' => $i + 1, 'is_active' => true])
            );
        }
    }
}
