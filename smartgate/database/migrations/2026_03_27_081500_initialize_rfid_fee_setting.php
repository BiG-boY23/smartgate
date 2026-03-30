<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add rfid_fee to system_settings table if it doesn't already exist
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'rfid_fee'],
            ['value' => '100', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'rfid_fee')->delete();
    }
};
