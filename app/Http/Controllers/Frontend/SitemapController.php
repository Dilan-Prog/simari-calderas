<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Products;
use Carbon\Carbon;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function index()
    {
        $sitemap = Sitemap::create();

        $hoy = Carbon::parse('2026-03-30');
        $legal = Carbon::parse('2026-01-01');

        // Páginas estáticas
        $sitemap->add(Url::create('/')->setPriority(1.0)->setChangeFrequency('weekly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/nuestra-empresa')->setPriority(0.8)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/contacto')->setPriority(0.8)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/aviso-privacidad')->setPriority(0.3)->setChangeFrequency('yearly')->setLastModificationDate($legal));
        $sitemap->add(Url::create('/terminos-condiciones')->setPriority(0.3)->setChangeFrequency('yearly')->setLastModificationDate($legal));

        // Servicios
        $sitemap->add(Url::create('/servicios/calderas/mantenimiento-calderas')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/servicios/mantenimiento-industrial')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/servicios/ingenieria-hidraulica')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/servicios/calibracion-equipos')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/servicios/tratamiento-agua')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/servicios/automatizacion')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/servicios/mantenimiento-chillers')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/servicios/proyectos-industriales')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/servicios/desincrustacion-calderas')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/servicios/calderas/reparacion-secadoras')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));

        // Productos
        $sitemap->add(Url::create('/productos/calderas-simari')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/productos/calentadores-solares')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/productos/instrumentacion-industrial')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/productos/tratamiento-agua-antiincrustante')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/productos/refacciones-mantenimiento')->setPriority(0.9)->setChangeFrequency('monthly')->setLastModificationDate($hoy));

        // Masstercal Rinnai
        $sitemap->add(Url::create('/masstercal-rinnai/bombas-de-calor-rinnai')->setPriority(0.8)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/masstercal-rinnai/calentadores-agua-rinnai')->setPriority(0.8)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/masstercal-rinnai/calentadores-electricos-rinnai')->setPriority(0.8)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/masstercal-rinnai/calentadores-paso-gas-rinnai')->setPriority(0.8)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/masstercal-rinnai/suavizadores-filtros-rinnai')->setPriority(0.8)->setChangeFrequency('monthly')->setLastModificationDate($hoy));
        $sitemap->add(Url::create('/masstercal-rinnai/tanques-almacenamiento-rinnai')->setPriority(0.8)->setChangeFrequency('monthly')->setLastModificationDate($hoy));

        // ── Rutas dinámicas del e-commerce ──

        // Catálogo (raíz del listado de productos)
        $ultimoProductoPublicado = Products::where('is_active', true)
            ->where('publish_on_website', true)
            ->max('updated_at');
        $sitemap->add(
            Url::create(route('catalog.index'))
                ->setPriority(0.9)
                ->setChangeFrequency('daily')
                ->setLastModificationDate($ultimoProductoPublicado ? Carbon::parse($ultimoProductoPublicado) : now())
        );

        // Colecciones (páginas SEO /coleccion/{slug})
        foreach (Collection::where('is_active', true)->get(['slug', 'updated_at']) as $collection) {
            $sitemap->add(
                Url::create(route('collection.show', $collection->slug))
                    ->setPriority(0.8)
                    ->setChangeFrequency('weekly')
                    ->setLastModificationDate($collection->updated_at ?? now())
            );
        }

        // Categorías del catálogo
        foreach (Category::where('is_active', true)->get(['slug', 'updated_at']) as $category) {
            $sitemap->add(
                Url::create(route('catalog.category', $category->slug))
                    ->setPriority(0.8)
                    ->setChangeFrequency('weekly')
                    ->setLastModificationDate($category->updated_at ?? now())
            );
        }

        // Productos publicados
        foreach (Products::where('is_active', true)->where('publish_on_website', true)->get(['slug', 'updated_at']) as $product) {
            $sitemap->add(
                Url::create(route('product.show', $product->slug))
                    ->setPriority(0.7)
                    ->setChangeFrequency('weekly')
                    ->setLastModificationDate($product->updated_at ?? now())
            );
        }

        return $sitemap->toResponse(request());
    }
}
