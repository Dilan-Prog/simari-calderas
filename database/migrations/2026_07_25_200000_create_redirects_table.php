<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            // Path only (e.g. "/catalogo/masstercal"), not a full URL — host
            // agnostic, and reusable for any prefix (catalogo, coleccion, etc).
            $table->string('old_path', 500);
            $table->string('new_path', 500);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->timestamps();

            $table->unique('old_path');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
