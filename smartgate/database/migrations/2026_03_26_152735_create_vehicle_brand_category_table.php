<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create pivot table for Brand-Category relationship
        Schema::create('vehicle_brand_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_category_id')->constrained('vehicle_categories')->cascadeOnDelete();
            $table->foreignId('vehicle_brand_id')->constrained('vehicle_brands')->cascadeOnDelete();
            $table->timestamps();
            
            // Ensure no duplicate links
            $table->unique(['vehicle_category_id', 'vehicle_brand_id'], 'vbc_unique_link');
        });

        // 2. Remove the old single-link column from vehicle_brands if it exists
        if (Schema::hasColumn('vehicle_brands', 'vehicle_category_id')) {
            // Optional: Move existing data to pivot before dropping
            $brands = DB::table('vehicle_brands')->whereNotNull('vehicle_category_id')->get();
            foreach ($brands as $brand) {
                DB::table('vehicle_brand_category')->insert([
                    'vehicle_category_id' => $brand->vehicle_category_id,
                    'vehicle_brand_id' => $brand->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::table('vehicle_brands', function (Blueprint $table) {
                $table->dropForeign(['vehicle_category_id']);
                $table->dropColumn('vehicle_category_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_brand_category');
        
        // Re-add the single-link column for rollback compatibility
        Schema::table('vehicle_brands', function (Blueprint $table) {
            $table->foreignId('vehicle_category_id')->nullable()->constrained('vehicle_categories')->nullOnDelete();
        });
    }
};
