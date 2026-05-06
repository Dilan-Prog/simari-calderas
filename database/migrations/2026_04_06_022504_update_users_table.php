<?php

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
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->after('id');
            $table->string('last_name', 100)->after('first_name');
            $table->string('position', 150)->after('last_name');
            $table->string('avatar_url', 255)->after('password')->nullable();
            $table->string('phone', 30)->after('email');
            $table->enum('status', ['active', 'inactive', 'suspended'])->after('avatar_url')->default('inactive');
            $table->string('rfc', 15)->after('status')->unique();
            $table->string('curp', 18)->after('rfc')->nullable()->unique();
            $table->string('social_segurity_number', 20)->after('curp')->nullable()->unique();
            $table->date('birthdate')->after('social_segurity_number')->nullable();
            $table->integer('id_contact_emergency')->after('birthdate')->nullable();
            $table->unsignedBigInteger('role_id')->after('id_contact_emergency');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name',
                'last_name',
                'position',
                'avatar_url',
                'phone',
                'status',
                'rfc',
                'curp',
                'social_segurity_number',
                'birthdate',
                'id_contact_emergency'
            ]);
        });
    }
};
