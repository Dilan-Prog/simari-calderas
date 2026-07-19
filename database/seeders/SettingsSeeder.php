<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'footer.description' => 'Líderes en soluciones térmicas industriales y residenciales. Innovación, eficiencia y confianza técnica desde 1995.',
            'footer.address'     => 'Ciudad de México, CDMX',
            'footer.phone'       => '+52 449 434 8018',
            'footer.phone_link'  => '+524494348018',
            'footer.email'       => 'administracion@equitermindustries.com.mx',
        ];

        foreach ($settings as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'type' => 'string', 'group_name' => 'footer', 'is_public' => true]
            );
        }
    }
}
