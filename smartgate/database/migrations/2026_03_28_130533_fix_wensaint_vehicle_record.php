<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\VehicleRegistration;
use App\Models\Vehicle;

return new class extends Migration {
    public function up(): void {
        $v = VehicleRegistration::where('full_name', 'Wen Saint Florito')->first();
        if ($v && $v->vehicles->count() === 0) {
            Vehicle::create([
                'user_id' => $v->id,
                'plate_number' => $v->plate_number,
                'vehicle_details' => trim($v->make_brand . ' ' . $v->model_name),
                'vehicle_type' => $v->vehicle_type,
                'rfid_tag' => $v->rfid_tag_id,
                'expiry_date' => $v->validity_to,
            ]);
            $v->update(['status' => 'ACTIVE']);
        }
    }
};
