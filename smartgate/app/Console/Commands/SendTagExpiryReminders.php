<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VehicleRegistration;
use App\Mail\TagExpiringReminder;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendTagExpiryReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tags:send-expiry-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders to owners whose RFID tags are about to expire in 7 days.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetDate = Carbon::now()->addDays(7)->toDateString();

        $registrations = VehicleRegistration::whereDate('validity_to', $targetDate)
            ->whereNotNull('email_address')
            ->where('status', 'ACTIVE')
            ->get();

        $this->info("Found " . $registrations->count() . " tags expiring on $targetDate");

        foreach ($registrations as $reg) {
            if (filter_var($reg->email_address, FILTER_VALIDATE_EMAIL)) {
                Mail::to($reg->email_address)->send(new TagExpiringReminder($reg));
                $this->info("Sent reminder to: " . $reg->email_address);
            }
        }

        $this->info('Expiry reminder process completed.');
    }
}
