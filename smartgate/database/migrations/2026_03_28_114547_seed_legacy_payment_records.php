<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\VehicleRegistration;
use App\Models\Payment;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Identify all registrations that HAVE an RFID tag but NO payment record
        // 2. Insert a historical payment record of P125.00 for them
        $registrations = VehicleRegistration::whereNotNull('rfid_tag_id')->get();
        
        foreach ($registrations as $r) {
            if ($r->payments()->count() === 0) {
                Payment::create([
                    'vehicle_registration_id' => $r->id,
                    'amount' => 125.00,
                    'or_number' => 'LEGACY-' . rand(1000, 9999), // Fallback OR
                    'paid_at' => $r->updated_at ?? now()
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
