<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Biblioteca de medios del módulo admin "Galería": registra las
        // imágenes subidas (módulo propio y picker compartido) para que
        // dejen de ser archivos huérfanos en uploads/.
        Schema::create('gallery_images', function (Blueprint $table) {
            $table->id();
            $table->string('path', 255);                    // relativa: uploads/xxx.jpg
            $table->string('original_name', 255)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Permiso del módulo (idempotente). Los admins lo ven de inmediato
        // (bypass isAdmin()); para otros roles se asigna en el módulo Roles.
        DB::table('permissions')->updateOrInsert(
            ['module' => 'gallery', 'action' => 'view'],
            ['name' => 'Ver Galería', 'code' => 'gallery.view', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_images');

        DB::table('permissions')->where('module', 'gallery')->delete();
    }
};
