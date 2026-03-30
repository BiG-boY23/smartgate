<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleRegistration;
use App\Models\Vehicle;
use App\Models\RegistrationReview;
use App\Models\User;
use App\Models\AuditLog;
use App\Models\VehicleLog;
use App\Models\Visitor;
use App\Models\SystemLog;
use App\Models\LockdownRecord;
use App\Models\SystemSetting;
use App\Exports\TrafficLogExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Get real statistics from database
        $stats = [
            'total_rfid' => VehicleRegistration::count(),
            'active_rfid' => Vehicle::count(),
            'blacklisted_rfid' => VehicleRegistration::where('status', 'rejected')->count(),
            'pending_registrations' => VehicleRegistration::where('status', 'pending')->count(),
            'entries_today' => VehicleLog::where('type', 'entry')->whereDate('timestamp', Carbon::today())->count(),
            'exits_today' => VehicleLog::where('type', 'exit')->whereDate('timestamp', Carbon::today())->count(),
            'current_occupancy' => VehicleLog::dailyOccupancy(),
            'total_capacity' => (int)SystemSetting::get('total_parking_slots', 200),
        ];

        // Get recent registrations for activity logs (load latest review + admin)
        $recentRegistrations = VehicleRegistration::with(['latestReview.admin'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $lockdownHistory = LockdownRecord::with('admin')->orderBy('started_at', 'desc')->limit(10)->get();

        $logs = $recentRegistrations->map(function ($registration) {
            return [
                'time' => $registration->created_at->format('h:i A'),
                'user' => 'Office Staff',
                'action' => 'Registration ' . ucfirst($registration->status),
                'details' => $registration->full_name . ' - ' . $registration->plate_number,
            ];
        })->toArray();

        return view('admin.dashboard', compact('stats', 'logs', 'lockdownHistory'));
    }

    public function users()
    {
        $users = User::all();
        return view('admin.users', compact('users'));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'username' => 'required|string|unique:users',
            'email' => 'nullable|email|unique:users',
            'role' => 'required|in:admin,office,guard',
            'password' => [
                'required',
                'confirmed',
                'string',
                'min:8',             // must be at least 8 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
            ],
        ]);

        User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'middle_name' => $request->middle_name,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
        ]);

        $this->recordActivity('USER_CREATED', "Created user account for {$request->first_name} {$request->last_name} ({$request->role})");

        return redirect()->back()->with('success', 'User added successfully.');
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'username' => 'required|string|unique:users,username,'.$id,
            'email' => 'nullable|email|unique:users,email,'.$id,
            'role' => 'required|in:admin,office,guard',
        ]);

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'middle_name' => $request->middle_name,
            'username' => $request->username,
            'email' => $request->email,
            'role' => $request->role,
        ];

        if ($request->filled('password')) {
            $request->validate([
                'password' => [
                    'confirmed',
                    'string',
                    'min:8',
                    'regex:/[a-z]/',
                    'regex:/[A-Z]/',
                    'regex:/[0-9]/',
                    'regex:/[@$!%*#?&]/',
                ],
            ]);
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $this->recordActivity('USER_UPDATED', "Updated user account for {$user->username}");

        return redirect()->back()->with('success', 'User updated successfully.');
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'You cannot delete yourself.');
        }
        $username = $user->username;
        $user->delete();
        $this->recordActivity('USER_DELETED', "Deleted user account: {$username}");
        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    public function rfid(Request $request)
    {
        $query = VehicleRegistration::with(['officeUser']);

        // Quick Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%$search%")
                  ->orWhere('plate_number', 'like', "%$search%")
                  ->orWhere('rfid_tag_id', 'like', "%$search%");
            });
        }

        // Status Filter
        if ($request->has('status') && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        $registrations = $query->orderBy('created_at', 'desc')->paginate(15);
        
        $stats = [
            'total' => VehicleRegistration::count(),
            'active' => Vehicle::count(),
            'blacklisted' => VehicleRegistration::where('status', 'rejected')->count(),
        ];

        $categories = \App\Models\VehicleCategory::where('is_active', true)->orderBy('name')->get();

        return view('admin.rfid', compact('registrations', 'stats', 'categories'));
    }

    public function storeRegistration(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'role' => 'required|in:student,faculty,staff',
            'fullName' => 'required|string|max:255',
            'contactNumber' => 'required|string|max:20',
            'emailAddress' => 'nullable|email|max:255',
            'vehicleType' => 'required|string|max:100',
            'makeBrand' => 'required|string|max:255',
            'modelName' => 'nullable|string|max:255',
            'plateNumber' => 'required|string|max:20',
            'rfidTagId' => 'required|string|unique:vehicle_registrations,rfid_tag_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = [
            'role' => $request->role,
            'full_name' => $request->fullName,
            'university_id' => $request->universityId ?? 'N/A',
            'college_dept' => $request->collegeDept ?? 'N/A',
            'contact_number' => $request->contactNumber,
            'email_address' => $request->emailAddress ?? 'N/A',
            'vehicle_type' => $request->vehicleType,
            'make_brand' => $request->makeBrand,
            'model_name' => $request->modelName,
            'plate_number' => $request->plateNumber,
            'rfid_tag_id' => $request->rfidTagId,
            'status' => 'approved',
            'office_user_id' => Auth::id(),
            'validity_from' => now(),
            'validity_to' => now()->addYear(),
        ];

        VehicleRegistration::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tag Registered successfully and is now active.'
        ]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $registration = VehicleRegistration::findOrFail($id);
        $newStatus = $registration->status === 'approved' ? 'rejected' : 'approved';
        
        $registration->update([
            'status' => $newStatus
        ]);

        $this->recordActivity('TAG_STATUS_CHANGE', ($newStatus === 'approved' ? 'Activated' : 'Blacklisted') . " tag ID: {$registration->rfid_tag_id} for {$registration->full_name}");

        return response()->json([
            'success' => true, 
            'message' => 'Tag status updated to ' . ($newStatus === 'approved' ? 'Active' : 'Blacklisted'),
            'new_status' => $newStatus
        ]);
    }

    public function showRegistration($id)
    {
        $registration = VehicleRegistration::with(['officeUser'])->findOrFail($id);
        return response()->json($registration);
    }

    public function reports(Request $request)
    {
        // 1. Audit Logs (Historical)
        $auditLogs = AuditLog::with('user')->orderByDesc('created_at')->paginate(20, ['*'], 'audit_page');

        // 2. Gate Traffic (Filtered)
        $query = VehicleLog::with(['vehicleRegistration', 'vehicle']);

        // Apply Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->whereHas('vehicleRegistration', function($qr) use ($search) {
                    $qr->where('full_name', 'like', "%$search%");
                })->orWhereHas('vehicle', function($qv) use ($search) {
                    $qv->where('plate_number', 'like', "%$search%");
                });
            });
        }

        // Apply Status
        if ($request->filled('status') && $request->get('status') !== 'all') {
            $query->where('type', $request->get('status'));
        }

        // Apply Date Range
        $from = $request->filled('from') ? Carbon::parse($request->get('from'))->startOfDay() : Carbon::today()->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->get('to'))->endOfDay() : Carbon::today()->endOfDay();
        $query->whereBetween('timestamp', [$from, $to]);

        $logs = $query->orderByDesc('timestamp')->get();

        // 3. Trend Analytics (Daily Entries vs Exits)
        $trendData = ['entry' => [], 'exit' => []];
        $days = [];
        $tempFrom = clone $from;
        
        // Ensure we have at least 7 days for a good trend if range is small
        $diff = $from->diffInDays($to);
        if($diff < 7 && !request()->filled('from')) {
            $tempFrom = clone $to;
            $tempFrom->subDays(7);
        }

        for ($d = clone $tempFrom; $d <= $to; $d->addDay()) {
            $dateStr = $d->format('Y-m-d');
            $days[] = $d->format('M d');
            $trendData['entry'][$dateStr] = 0;
            $trendData['exit'][$dateStr] = 0;
        }

        // We need a separate query for accurate trends that isn't affected by Search/Status filters 
        // unless the user implicitly wants to trend their search (complex).
        // Let's trend ALL movement for the selected period.
        $trendLogs = VehicleLog::whereBetween('timestamp', [$tempFrom, $to])->get();
        foreach ($trendLogs as $tlog) {
            $dateStr = $tlog->timestamp->format('Y-m-d');
            if (isset($trendData[$tlog->type][$dateStr])) {
                $trendData[$tlog->type][$dateStr]++;
            }
        }

        $chartData = [
            'labels' => $days,
            'entries' => array_values($trendData['entry']),
            'exits' => array_values($trendData['exit']),
        ];

        return view('admin.reports', compact('auditLogs', 'logs', 'chartData'));
    }

    /**
     * Export Traffic Logs to Excel (.xlsx)
     */
    public function exportExcel(Request $request)
    {
        $filters = $request->only(['from', 'to', 'search', 'status']);
        return Excel::download(new TrafficLogExport($filters), 'EVSU_SmartGate_Report_' . date('Y-m-d') . '.xlsx');
    }

    private function recordActivity($action, $details)
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'details' => $details,
            'ip_address' => request()->ip()
        ]);
    }

    public function settings()
    {
        $settings = [
            'cooldown_interval' => SystemSetting::get('cooldown_interval', 3),
            'tag_logic' => SystemSetting::get('tag_logic', 'flexible'),
            'total_parking_slots' => SystemSetting::get('total_parking_slots', 200),
            'occupancy_warning_threshold' => SystemSetting::get('occupancy_warning_threshold', 90),
            'blacklist_alarm' => SystemSetting::get('blacklist_alarm', 'on'),
            'expiry_alert_lead_time' => SystemSetting::get('expiry_alert_lead_time', 30),
            'guard_ticker' => SystemSetting::get('guard_ticker', 'Welcome to EVSU. Please check your RFID tags before entry.'),
            'bridge_heartbeat_freq' => SystemSetting::get('bridge_heartbeat_freq', 30),
            'validity_period' => SystemSetting::get('validity_period', 1),
            'evsu_logo' => SystemSetting::get('evsu_logo'),
            'chocobol_logo' => SystemSetting::get('chocobol_logo'),
            'rfid_fee' => SystemSetting::get('rfid_fee', 100),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        // Define list of possible settings to process
        $settingKeys = [
            'cooldown_interval', 'tag_logic', 'total_parking_slots', 
            'occupancy_warning_threshold', 'blacklist_alarm', 
            'expiry_alert_lead_time', 'guard_ticker', 
            'bridge_heartbeat_freq', 'validity_period', 'rfid_fee'
        ];

        foreach ($settingKeys as $key) {
            if ($request->has($key)) {
                SystemSetting::set($key, $request->input($key));
            }
        }

        // Handle File Uploads (Only update if a new file is provided)
        $fileSettings = ['evsu_logo', 'chocobol_logo'];
        foreach ($fileSettings as $key) {
            if ($request->hasFile($key)) {
                $file = $request->file($key);
                $filename = $key . '_' . time() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('settings', $filename, 'public');
                SystemSetting::set($key, $path);
            }
        }

        // Add to audit log
        $this->recordActivity('SETTINGS_UPDATED', "Administrator updated global system settings.");

        return redirect()->back()->with('success', 'System Command Center settings updated successfully.');
    }

    /**
     * Heartbeat from bridge_service.py
     */
    public function bridgeHeartbeat(Request $request)
    {
        $port = $request->input('port', 'Unknown');
        
        Cache::put('bridge_last_heartbeat', now(), 120); // 2 min expiry
        Cache::put('bridge_com_port', $port, 120);

        return response()->json(['status' => 'success']);
    }

    /**
     * Sync buffered scans from bridge_service.py
     */
    public function bridgeSync(Request $request)
    {
        $scans = $request->input('scans', []);
        $count = 0;

        foreach ($scans as $scan) {
            $tagId = $scan['tagId'];
            $timestamp = Carbon::parse($scan['timestamp']);

            $vehicle = Vehicle::where('rfid_tag', $tagId)->first();
            $registration = $vehicle ? $vehicle->owner : null;

            VehicleLog::create([
                'vehicle_registration_id' => $registration ? $registration->id : null,
                'vehicle_id' => $vehicle ? $vehicle->id : null,
                'rfid_tag_id' => $tagId,
                'type' => 'entry', 
                'timestamp' => $timestamp
            ]);

            $count++;
        }

        if ($count > 0) {
            SystemLog::create([
                'type' => 'sync',
                'source' => 'bridge',
                'message' => "Successfully synced {$count} offline scans.",
                'details' => ['scans' => $count]
            ]);
        }

        return response()->json(['success' => true, 'synced' => $count]);
    }

    /**
     * System Logs View
     */
    public function systemLogs()
    {
        $logs = SystemLog::orderByDesc('created_at')->limit(100)->get();
        return view('admin.system-logs', compact('logs'));
    }

    public function bridgeStatus()
    {
        $connection = @fsockopen('127.0.0.1', 8080, $errno, $errstr, 1);
        if ($connection) {
            fclose($connection);
            return response()->json(['online' => true]);
        }
        return response()->json(['online' => false]);
    }

    /**
     * Toggles global system lockdown.
     */
    public function toggleLockdown(Request $request)
    {
        $current = Cache::get('system_lockdown', ['active' => false, 'reason' => '']);
        $newActive = !$current['active'];
        $reason = $request->input('reason', 'N/A');

        if ($newActive) {
            // Started
            LockdownRecord::create([
                'started_at' => now(),
                'admin_id' => Auth::id(),
                'reason' => $reason
            ]);
            Cache::forever('system_lockdown', ['active' => true, 'reason' => $reason]);
        } else {
            // Ended
            $last = LockdownRecord::where('ended_at', null)->orderBy('started_at', 'desc')->first();
            if ($last) {
                $last->update(['ended_at' => now()]);
            }
            Cache::forever('system_lockdown', ['active' => false, 'reason' => '']);
        }

        $message = $newActive ? "EMERGENCY LOCKDOWN ACTIVATED: $reason" : "System Lockdown Deactivated";
        
        SystemLog::create([
            'type' => 'status',
            'source' => 'admin',
            'message' => $message,
            'details' => ['admin_id' => Auth::id()]
        ]);

        $this->recordActivity('LOCKDOWN_TOGGLE', $message);

        return response()->json([
            'success' => true,
            'lockdown' => $newActive,
            'message' => $message,
            'reason' => $reason
        ]);
    }
}
