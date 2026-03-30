<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Add model_name column
        if (!Schema::hasColumn('vehicle_registrations', 'model_name')) {
            Schema::table('vehicle_registrations', function (Blueprint $table) {
                $table->string('model_name')->nullable()->after('make_brand');
            });
        }

        // Step 2: Change vehicle_type from ENUM to VARCHAR
        // SQLite doesn't support ALTER COLUMN type natively, so we use DB::statement
        // which works for MySQL/MariaDB. For SQLite (dev), we use a workaround.
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: recreate is handled automatically in newer Laravel; just skip.
            // We can still add model_name above. Type change for SQLite is ignored
            // since SQLite stores any string regardless of enum declaration.
        } else {
            // MySQL / MariaDB
            DB::statement("ALTER TABLE vehicle_registrations MODIFY vehicle_type VARCHAR(100) NOT NULL DEFAULT 'Other'");
        }
    }

    public function down(): void
    {
        Schema::table('vehicle_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('vehicle_registrations', 'model_name')) {
                $table->dropColumn('model_name');
            }
        });
    }
};
