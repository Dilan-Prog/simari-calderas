<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenusSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedHeaderMain();
        $this->seedHeaderServicios();
        $this->seedFooterServices();
        $this->seedFooterProducts();
        $this->seedFooterCompany();
    }

    protected function seedHeaderMain(): void
    {
        $menu = Menu::firstOrCreate(['location' => 'header-main'], ['name' => 'Navegación principal', 'is_active' => true]);

        $this->seedFlatItems($menu, [
            'Catálogo' => route('catalog.index', [], false),
            'Nosotros' => route('company', [], false),
            'Contacto' => route('contact', [], false),
        ]);
    }

    protected function seedHeaderServicios(): void
    {
        $menu = Menu::firstOrCreate(['location' => 'header-servicios'], ['name' => 'Mega menú de Servicios', 'is_active' => true]);

        $tree = [
            'Calderas' => [
                'Servicios de Calderas' => route('boiler-services', [], false),
                'Mantenimiento de Calderas' => route('boiler-maintenance', [], false),
                'Desincrustación de Calderas' => route('boiler-descaling', [], false),
                'Reparación de Calderas' => route('boiler-repair', [], false),
            ],
            'Tratamiento de Agua' => [
                'Tratamiento de Agua' => route('water-treatment', [], false),
                'Tratamiento Anti-incrustante' => route('water-treatment-Anti', [], false),
            ],
            'Mantenimiento Industrial' => [
                'Mantenimiento Industrial' => route('industrial-maintenance', [], false),
                'Mantenimiento de Chillers' => route('chiller-maintenance', [], false),
                'Reparación de Secadoras' => route('hair-repair', [], false),
            ],
            'Ingeniería y Proyectos' => [
                'Ingeniería Hidráulica' => route('hydraulic-engineering', [], false),
                'Proyecto Industrial' => route('industrial-project', [], false),
                'Automatización' => route('automation', [], false),
                'Calibración de Equipos' => route('equipement-calibration', [], false),
            ],
        ];

        $sort = 0;
        foreach ($tree as $rootTitle => $children) {
            $root = MenuItem::firstOrCreate(
                ['menu_id' => $menu->id, 'parent_id' => null, 'title' => $rootTitle],
                ['sort_order' => $sort++, 'is_active' => true]
            );

            $childSort = 0;
            foreach ($children as $childTitle => $url) {
                MenuItem::firstOrCreate(
                    ['menu_id' => $menu->id, 'parent_id' => $root->id, 'title' => $childTitle],
                    ['url' => $url, 'sort_order' => $childSort++, 'is_active' => true]
                );
            }
        }
    }

    protected function seedFooterServices(): void
    {
        $menu = Menu::firstOrCreate(['location' => 'footer-services'], ['name' => 'Footer - Servicios', 'is_active' => true]);

        $this->seedFlatItems($menu, [
            'Mantenimiento industrial' => route('industrial-maintenance', [], false),
            'Ingeniería Hidráulica' => route('hydraulic-engineering', [], false),
            'Calibración de equipos' => route('equipement-calibration', [], false),
            'Tratamiento de Agua' => route('water-treatment', [], false),
            'Automatización' => route('automation', [], false),
            'Mantenimiento de Chillers' => route('chiller-maintenance', [], false),
            'Desincrustación de Calderas' => route('descale-boilers', [], false),
            'Proyecto Industrial' => route('industrial-project', [], false),
            'Reparación de Secadoras' => route('hair-repair', [], false),
        ]);
    }

    protected function seedFooterProducts(): void
    {
        $menu = Menu::firstOrCreate(['location' => 'footer-products'], ['name' => 'Footer - Productos', 'is_active' => true]);

        $this->seedFlatItems($menu, [
            'Calderas SIMARI' => route('simari-boilers', [], false),
            'Calentadores Solares' => route('solar-heaters', [], false),
            'Instrumentación Industrial' => route('industrial-instrumentation', [], false),
            'Tratamiento Anti-incrustante' => route('water-treatment-Anti', [], false),
            'Refacciones y Mantenimiento' => route('spare-parts', [], false),
        ]);
    }

    protected function seedFooterCompany(): void
    {
        $menu = Menu::firstOrCreate(['location' => 'footer-company'], ['name' => 'Footer - Empresa', 'is_active' => true]);

        $this->seedFlatItems($menu, [
            'Inicio' => route('home', [], false),
            'Nosotros' => route('company', [], false),
            'Contacto' => route('contact', [], false),
        ]);
    }

    protected function seedFlatItems(Menu $menu, array $items): void
    {
        $sort = 0;
        foreach ($items as $title => $url) {
            MenuItem::firstOrCreate(
                ['menu_id' => $menu->id, 'parent_id' => null, 'title' => $title],
                ['url' => $url, 'sort_order' => $sort++, 'is_active' => true]
            );
        }
    }
}
