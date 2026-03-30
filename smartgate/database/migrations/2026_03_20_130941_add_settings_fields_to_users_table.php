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
            $table->string('profile_picture')->nullable()->after('last_name');
            $table->boolean('dark_mode')->default(false)->after('password');
            $table->boolean('two_factor_enabled')->default(false)->after('dark_mode');
            $table->string('language', 10)->default('en')->after('two_factor_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profile_picture', 'dark_mode', 'two_factor_enabled', 'language']);
        });
    }
};
