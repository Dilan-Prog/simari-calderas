<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Usuarios y roles del portal de clientes (/customer): cada cuenta de cliente
 * define sus propios roles y da de alta usuarios adicionales de su equipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_portal_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['customer_id', 'name']);
        });

        Schema::create('customer_portal_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('customer_portal_roles')->cascadeOnDelete();
            $table->string('module', 64);
            $table->string('action', 16);

            $table->unique(['role_id', 'module', 'action'], 'cprp_role_module_action_unique');
        });

        Schema::create('customer_portal_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            // Sin rol = sin acceso (si se borra el rol, el usuario queda bloqueado).
            $table->foreignId('role_id')->nullable()->constrained('customer_portal_roles')->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->default('');
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->string('password');
            $table->string('status', 16)->default('active'); // active | inactive
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_portal_users');
        Schema::dropIfExists('customer_portal_role_permissions');
        Schema::dropIfExists('customer_portal_roles');
    }
};
