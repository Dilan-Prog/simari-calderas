<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function index()
    {
        $sitemap = Sitemap::create();

        // Páginas estáticas
        $sitemap->add(Url::create('/')->setPriority(1.0)->setChangeFrequency('weekly'));
        $sitemap->add(Url::create('/nuestra-empresa')->setPriority(0.8)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/contacto')->setPriority(0.8)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/aviso-privacidad')->setPriority(0.3)->setChangeFrequency('yearly'));
        $sitemap->add(Url::create('/terminos-condiciones')->setPriority(0.3)->setChangeFrequency('yearly'));

        // Servicios
        $sitemap->add(Url::create('/servicios/mantenimiento-industrial')->setPriority(0.9)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/servicios/ingenieria-hidraulica')->setPriority(0.9)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/servicios/calibracion-equipos')->setPriority(0.9)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/servicios/tratamiento-agua')->setPriority(0.9)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/servicios/automatizacion')->setPriority(0.9)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/servicios/mantenimiento-chillers')->setPriority(0.9)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/servicios/proyectos-industriales')->setPriority(0.9)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/servicios/desincrustacion-calderas')->setPriority(0.9)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/servicios/reparacion-secadoras')->setPriority(0.9)->setChangeFrequency('monthly'));

        // Productos
        $sitemap->add(Url::create('/productos/calderas-simari')->setPriority(0.9)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/productos/calentadores-solares')->setPriority(0.9)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/productos/instrumentacion-industrial')->setPriority(0.9)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/productos/tratamiento-agua-antiincrustante')->setPriority(0.9)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/productos/refacciones-mantenimiento')->setPriority(0.9)->setChangeFrequency('monthly'));

        // Masstercal Rinnai
        $sitemap->add(Url::create('/masstercal-rinnai/bombas-de-calor-rinnai')->setPriority(0.8)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/masstercal-rinnai/calentadores-agua-rinnai')->setPriority(0.8)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/masstercal-rinnai/calentadores-electricos-rinnai')->setPriority(0.8)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/masstercal-rinnai/calentadores-paso-gas-rinnai')->setPriority(0.8)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/masstercal-rinnai/suavizadores-filtros-rinnai')->setPriority(0.8)->setChangeFrequency('monthly'));
        $sitemap->add(Url::create('/masstercal-rinnai/tanques-almacenamiento-rinnai')->setPriority(0.8)->setChangeFrequency('monthly'));

        // Rutas dinámicas futuras — agregar aquí:
        // foreach (Producto::all() as $producto) {
        //     $sitemap->add(Url::create("/productos/{$producto->slug}")->setPriority(0.8));
        // }

        return $sitemap->toResponse(request());
    }
}
