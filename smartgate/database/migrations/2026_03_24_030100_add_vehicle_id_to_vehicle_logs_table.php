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
        Schema::table('vehicle_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_id')->nullable()->after('vehicle_registration_id');
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('set null');
        });

        // Link existing logs to vehicles based on RFID tag
        $logs = DB::table('vehicle_logs')->get();
        foreach ($logs as $log) {
            if ($log->rfid_tag_id) {
                $vehicle = DB::table('vehicles')->where('rfid_tag', $log->rfid_tag_id)->first();
                if ($vehicle) {
                    DB::table('vehicle_logs')->where('id', $log->id)->update(['vehicle_id' => $vehicle->id]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_logs', function (Blueprint $table) {
            $table->dropForeign(['vehicle_id']);
            $table->dropColumn('vehicle_id');
        });
    }
};
