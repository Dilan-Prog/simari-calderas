<?php

use App\Models\Products;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('canonical_product_id')->nullable()->after('canonical_url')
                ->constrained('products')->nullOnDelete();
        });

        // FIX (URL Canónica): backfill de datos, intencional y de una sola
        // corrida — antes de esta migración, `canonical_url` era texto libre
        // escrito a mano por el admin (la URL de otro producto). Ahora se
        // reemplaza por un selector que guarda el producto elegido
        // (`canonical_product_id`); para no perder lo ya capturado, se
        // intenta emparejar cada `canonical_url` existente contra la URL
        // pública real de algún otro producto del catálogo
        // (`route('product.show', $slug)`, la misma que arma
        // `routes/web.php` para `/producto/{slug}`). Si hay coincidencia
        // exacta de string, se preselecciona ese producto; si no coincide con
        // ninguno, se deja `canonical_product_id` en null y NO se toca
        // `canonical_url` — se conserva tal cual como "URL personalizada"
        // (nunca se pierde un dato ya capturado). Un `migrate` normal corre
        // esto una sola vez (las migraciones no se repiten), así que no hace
        // falta una guarda de idempotencia adicional; se documenta aquí para
        // que quede claro por qué un backfill de datos vive dentro de una
        // migración de esquema.
        //
        // Se arma un mapa [url_publica_real => product_id] de TODO el
        // catálogo una sola vez (en vez de una consulta por producto dentro
        // del chunk) para que el emparejamiento sea O(n) y no dispare miles
        // de queries en catálogos grandes.
        $urlToProductId = [];
        Products::query()->select('id', 'slug')->chunk(200, function ($products) use (&$urlToProductId) {
            foreach ($products as $product) {
                if ($product->slug) {
                    $urlToProductId[route('product.show', $product->slug)] = $product->id;
                }
            }
        });

        Products::query()
            ->whereNotNull('canonical_url')
            ->select('id', 'canonical_url')
            ->chunk(200, function ($products) use ($urlToProductId) {
                foreach ($products as $product) {
                    $matchedId = $urlToProductId[$product->canonical_url] ?? null;

                    // Nunca emparejar un producto consigo mismo (no debería
                    // ocurrir en datos reales, pero es una guarda barata).
                    if ($matchedId !== null && $matchedId !== $product->id) {
                        $product->newQuery()->where('id', $product->id)->update([
                            'canonical_product_id' => $matchedId,
                        ]);
                    }
                    // Si no hay match, no se toca nada: canonical_url queda
                    // como "URL personalizada" y canonical_product_id null.
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Solo se revierte el esquema — el backfill de arriba no se
        // deshace (no hay nada que "deshacer": canonical_url nunca se tocó,
        // solo se llenó canonical_product_id, y esa columna desaparece con
        // el drop de todos modos).
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('canonical_product_id');
        });
    }
};
