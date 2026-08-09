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
        $this->seedFooterCompany();
    }

    protected function seedHeaderMain(): void
    {
        $menu = Menu::firstOrCreate(['location' => 'header-main'], ['name' => 'Navegación principal', 'is_active' => true]);

        $this->seedFlatItems($menu, [
            'Catálogo' => route('catalog.index', [], false),
        ]);
    }

    protected function seedFooterCompany(): void
    {
        $menu = Menu::firstOrCreate(['location' => 'footer-company'], ['name' => 'Footer - Empresa', 'is_active' => true]);

        $this->seedFlatItems($menu, [
            'Inicio' => route('home', [], false),
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
