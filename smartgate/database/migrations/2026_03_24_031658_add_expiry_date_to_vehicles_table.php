<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicles', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('rfid_tag');
            }
        });

        // Sync existing vehicles to match their owner's expiration date initially
        $vehicles = DB::table('vehicles')->get();
        foreach ($vehicles as $v) {
            $owner = DB::table('vehicle_registrations')->where('id', $v->user_id)->first();
            if ($owner && $owner->validity_to) {
                DB::table('vehicles')->where('id', $v->id)->update(['expiry_date' => $owner->validity_to]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('vehicles', 'expiry_date')) {
                $table->dropColumn('expiry_date');
            }
        });
    }
};
