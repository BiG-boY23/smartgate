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
        if (!Schema::hasTable('vehicles')) {
            Schema::create('vehicles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id'); // Link to vehicle_registrations.id (the "Owner")
                $table->string('plate_number')->unique();
                $table->string('vehicle_details')->nullable();
                $table->string('rfid_tag')->unique()->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('vehicle_registrations')->onDelete('cascade');
            });
        }

        // Migrate existing data from vehicle_registrations to vehicles (only if table was empty)
        if (DB::table('vehicles')->count() === 0) {
            $registrations = DB::table('vehicle_registrations')->whereNotNull('plate_number')->get();
            foreach ($registrations as $reg) {
                DB::table('vehicles')->insert([
                    'user_id' => $reg->id,
                    'plate_number' => $reg->plate_number,
                    'vehicle_details' => trim(($reg->make_brand ?? '') . ' ' . ($reg->model_year ?? '')),
                    'rfid_tag' => $reg->rfid_tag_id,
                    'created_at' => $reg->created_at ?? now(),
                    'updated_at' => $reg->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
