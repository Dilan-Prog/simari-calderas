<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Crear tabla permissions
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->notNull()->unique();
            $table->string('name', 100)->notNull();
            $table->string('module', 50)->notNull();
            $table->timestamp('created_at')->nullable();
        });

        // Crear tabla role_permissions (pivot)
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->notNull();
            $table->unsignedBigInteger('permission_id')->notNull();
            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });

        // Crear tabla contact_emergency
        Schema::create('contact_emergency', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->notNull();
            $table->string('name', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('relationship', 50)->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Agregar FK de role_id en users (ya fue agregada como integer en update_users_table)
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });
        Schema::dropIfExists('contact_emergency');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};
