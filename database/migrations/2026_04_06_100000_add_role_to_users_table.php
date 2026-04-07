<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->string('name', 100);
            $table->string('module', 50);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });

        Schema::create('contact_emergency', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('relationship', 50)->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->change();
        });

        DB::table('users')->update(['role_id' => null]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('contact_emergency');
        Schema::dropIfExists('permissions');
    }
};
