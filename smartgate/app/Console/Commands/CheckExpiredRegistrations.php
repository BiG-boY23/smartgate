<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VehicleRegistration;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckExpiredRegistrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registrations:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks all vehicle registrations for expiry and updates their status.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        $expiredCount = VehicleRegistration::where('validity_to', '<', $today)
            ->where('status', '!=', 'expired')
            ->update(['status' => 'expired']);

        if ($expiredCount > 0) {
            $this->info("Successfully expired {$expiredCount} registrations.");
            Log::info("Automated Expiry System: Updated {$expiredCount} records to 'expired'.");
        } else {
            $this->info("No new expirations found.");
        }
    }
}
