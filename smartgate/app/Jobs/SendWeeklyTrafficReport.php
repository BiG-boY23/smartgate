<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\VehicleLog;
use App\Models\User;
use App\Mail\WeeklyReportMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SendWeeklyTrafficReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() { }

    public function handle()
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        // 1. Gather Metrics
        $totalEntries = VehicleLog::where('type', 'entry')->whereBetween('timestamp', [$startOfWeek, $endOfWeek])->count();
        $totalExits = VehicleLog::where('type', 'exit')->whereBetween('timestamp', [$startOfWeek, $endOfWeek])->count();
        
        $peakHourData = VehicleLog::select(DB::raw('strftime("%H", timestamp) as hour, count(*) as count'))
            ->whereBetween('timestamp', [$startOfWeek, $endOfWeek])
            ->groupBy('hour')
            ->orderByDesc('count')
            ->first();
        $peakHour = $peakHourData ? Carbon::createFromTime($peakHourData->hour)->format('h A') : 'N/A';

        $topTagData = VehicleLog::select('rfid_tag_id', DB::raw('count(*) as count'))
            ->whereBetween('timestamp', [$startOfWeek, $endOfWeek])
            ->groupBy('rfid_tag_id')
            ->orderByDesc('count')
            ->first();
        $topTag = $topTagData ? $topTagData->rfid_tag_id : 'N/A';

        $reportData = [
            'total_entries' => $totalEntries,
            'total_exits' => $totalExits,
            'peak_hour' => $peakHour,
            'top_tag' => $topTag
        ];

        // 2. Dispatch Email to Admins
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            if ($admin->email) {
                Mail::to($admin->email)->send(new WeeklyReportMail($reportData));
            }
        }
    }
}
