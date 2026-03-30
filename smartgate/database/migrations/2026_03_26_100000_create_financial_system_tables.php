<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create Payments Table - Defensive check
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vehicle_registration_id')->constrained('vehicle_registrations')->onDelete('cascade');
                $table->string('or_number')->unique(); 
                $table->decimal('amount', 10, 2);
                $table->timestamp('paid_at')->useCurrent();
                $table->timestamps();
            });
        }

        // 2. Update Vehicle Registrations table statuses
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE vehicle_registrations MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'verified', 'ACTIVE') DEFAULT 'pending'");
        }

        // 3. Make rfid_tag_id nullable
        Schema::table('vehicle_registrations', function (Blueprint $table) {
            $table->string('rfid_tag_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_registrations', function (Blueprint $table) {
            $table->string('rfid_tag_id')->nullable(false)->change();
        });
        
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE vehicle_registrations MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
        }
        
        Schema::dropIfExists('payments');
    }
};
