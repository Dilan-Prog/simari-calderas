<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Tablas de documentos que pueden pertenecer a un proyecto (technical_services vive en `services`). */
    private array $tables = ['quotes', 'services', 'service_reports', 'sales_orders'];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            if (Schema::hasTable($t) && !Schema::hasColumn($t, 'project_id')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'project_id')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('project_id');
                });
            }
        }
    }
};
