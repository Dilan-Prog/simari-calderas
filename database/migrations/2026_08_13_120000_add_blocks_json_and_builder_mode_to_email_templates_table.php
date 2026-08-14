<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->json('blocks_json')->nullable()->after('html_body');
            $table->string('builder_mode', 20)->default('code')->after('blocks_json');
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn(['blocks_json', 'builder_mode']);
        });
    }
};
