<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // PASO 1: Limpiar datos existentes
        // ============================================================
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('role_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ============================================================
        // PASO 2: Modificar tabla permissions
        // ============================================================
        Schema::table('permissions', function (Blueprint $table) {
            // Hacer code nullable ya que no lo usaremos
            $table->string('code', 100)->nullable()->change();

            // Agregar columna action después de module
            $table->string('action', 50)->default('view')->after('module');

            // Agregar updated_at
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });

        // ============================================================
        // PASO 3: Insertar todos los permisos del sistema
        // ============================================================
        $now = now();

        $permissions = [
            ['name' => 'Ver Dashboard',            'module' => 'dashboard',          'action' => 'view'],
            ['name' => 'Ver Clientes',             'module' => 'clients',            'action' => 'view'],
            ['name' => 'Ver Proveedores',          'module' => 'suppliers',          'action' => 'view'],
            ['name' => 'Ver Productos',            'module' => 'products',           'action' => 'view'],
            ['name' => 'Ver Categorías',           'module' => 'categories',         'action' => 'view'],
            ['name' => 'Ver Marcas',               'module' => 'brands',             'action' => 'view'],
            ['name' => 'Ver Cotizaciones',         'module' => 'quotes',             'action' => 'view'],
            ['name' => 'Ver Órdenes de Compra',    'module' => 'purchase-orders',    'action' => 'view'],
            ['name' => 'Ver Órdenes',              'module' => 'orders',             'action' => 'view'],
            ['name' => 'Ver Servicios Técnicos',   'module' => 'technical-services', 'action' => 'view'],
            ['name' => 'Ver Reportes de Servicio', 'module' => 'service-reports',    'action' => 'view'],
            ['name' => 'Ver Inventario',           'module' => 'inventory',          'action' => 'view'],
            ['name' => 'Ver Envíos',               'module' => 'shipments',          'action' => 'view'],
            ['name' => 'Ver Paqueterías',          'module' => 'carriers',           'action' => 'view'],
            ['name' => 'Ver Métodos de Pago',      'module' => 'payment-methods',    'action' => 'view'],
            ['name' => 'Ver Google Ads',           'module' => 'google-ads',         'action' => 'view'],
            ['name' => 'Ver Email Marketing',      'module' => 'email-marketing',    'action' => 'view'],
            ['name' => 'Ver Analíticas',           'module' => 'analytics',          'action' => 'view'],
            ['name' => 'Ver Auditoría Sistema',    'module' => 'audit',              'action' => 'view'],
            ['name' => 'Ver Blog',                 'module' => 'blog',               'action' => 'view'],
            ['name' => 'Ver Menú',                 'module' => 'menu',               'action' => 'view'],
            ['name' => 'Ver SEO Global',           'module' => 'seo',                'action' => 'view'],
            ['name' => 'Ver WhatsApp',             'module' => 'whatsapp',           'action' => 'view'],
            ['name' => 'Ver Configuración',        'module' => 'settings',           'action' => 'view'],
            ['name' => 'Ver Usuarios',             'module' => 'users',              'action' => 'view'],
            ['name' => 'Ver Roles',                'module' => 'roles',              'action' => 'view'],
        ];

        foreach ($permissions as &$permission) {
            $permission['created_at'] = $now;
            $permission['updated_at'] = $now;
        }

        DB::table('permissions')->insert($permissions);
    }

    public function down(): void
    {
        // Limpiar permisos insertados
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('role_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Revertir cambios en la tabla
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('action');
            $table->dropColumn('updated_at');
            $table->string('code', 100)->nullable(false)->change();
        });
    }
};
