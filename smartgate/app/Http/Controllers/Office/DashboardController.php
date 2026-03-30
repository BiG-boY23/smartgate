<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Models\VehicleRegistration;
use App\Models\Vehicle;
use App\Models\VehicleLog;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = VehicleRegistration::count();
        $registeredToday = VehicleRegistration::whereDate('created_at', Carbon::today())->count();
        $activeVehicles = Vehicle::count();
        $pendingRegistrations = VehicleRegistration::where('status', 'pending')->count();
        
        $totalCapacity = (int)\App\Models\SystemSetting::get('total_parking_slots', 200);
        $currentOccupancy = VehicleLog::dailyOccupancy();

        // Quick summary by role (student, faculty, staff)
        $roleCounts = VehicleRegistration::selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $summary = [
            'student' => [
                'label' => 'Students',
                'count' => $roleCounts['student'] ?? 0,
            ],
            'faculty' => [
                'label' => 'Faculty',
                'count' => $roleCounts['faculty'] ?? 0,
            ],
            'staff' => [
                'label' => 'Non-Teaching',
                'count' => $roleCounts['staff'] ?? 0,
            ],
        ];

        // Compute percentages safely
        foreach ($summary as $key => $item) {
            $summary[$key]['percent'] = $totalUsers > 0
                ? round(($item['count'] / $totalUsers) * 100)
                : 0;
        }

        return view('office.dashboard', compact('totalUsers', 'registeredToday', 'activeVehicles', 'summary', 'pendingRegistrations', 'totalCapacity', 'currentOccupancy'));
    }

    public function registration(Request $request)
    {
        $registration = null;
        if ($request->has('id')) {
            $registration = VehicleRegistration::findOrFail($request->id);
        }
        $brands = \App\Models\VehicleBrand::with('models')->orderBy('name')->get();
        $categories = \App\Models\VehicleCategory::where('is_active', true)->orderBy('name')->get();
        $colleges = \App\Models\College::with('courses')->orderBy('name')->get();
        
        return view('office.registration', compact('registration', 'brands', 'categories', 'colleges'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:student,faculty,staff',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'contact_number' => 'required|string|max:20',
            'email_address' => 'nullable|email|max:255',
            'vehicle_type' => 'required|string|max:100', 
            'make_brand' => 'required|string|max:255',
            'model_name' => 'required|string|max:255',
            'plate_number' => 'required|string|max:20',
            'validity_from' => 'required|date',
            'validity_to' => 'required|date',
            'rfid_tag_id' => 'required|string|unique:vehicle_registrations,rfid_tag_id',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax() || $request->hasHeader('X-Requested-With')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please check the required fields and tag uniqueness.',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $fullName = trim($request->first_name . ' ' . ($request->middle_name ? $request->middle_name . ' ' : '') . $request->last_name);
        
        $data = [
            'role'              => $request->role,
            'first_name'        => $request->first_name,
            'last_name'         => $request->last_name,
            'middle_name'       => $request->middle_name,
            'full_name'         => $fullName,
            'contact_number'    => $request->contact_number,
            'email_address'     => $request->email_address ?? 'N/A',
            'vehicle_type'      => $request->vehicle_type,
            'make_brand'        => $request->make_brand,
            'model_name'        => $request->model_name,
            'model_year'        => $request->model_year ?? 'N/A',
            'color'             => $request->color ?? 'N/A',
            'plate_number'      => $request->plate_number,
            'registered_owner'  => $request->registered_owner ?? $fullName,
            'validity_from'     => $request->validity_from,
            'validity_to'       => $request->validity_to,
            'rfid_tag_id'       => $request->rfid_tag_id,
            'status'            => 'approved',
            'office_user_id'    => Auth::id(),
            'sticker_classification' => [$request->role],
        ];

        // Role-based specific mappings
        if ($request->role === 'student') {
            $data['university_id'] = $request->university_id;
            $data['course'] = $request->course;
            $data['college_dept'] = $request->college_dept;
            $data['year_level'] = $request->year_level;
        } elseif ($request->role === 'faculty') {
            $data['university_id'] = $request->university_id;
            $data['college_dept'] = $request->college_dept;
            $data['office'] = $request->college_dept; // Map dept to office for faculty
        } elseif ($request->role === 'staff') {
            $data['business_stall_name'] = $request->business_stall_name;
            $data['vendor_address'] = $request->vendor_address;
            $data['university_id'] = 'N/A';
        }

        $registration = VehicleRegistration::create($data);

        // Record payment and create Vehicle entry for automatic tag assignment
        if ($registration->rfid_tag_id) {
            // 1. Create Payment record
            $rfid_fee = (float)\App\Models\SystemSetting::get('rfid_fee', 100);
            \App\Models\Payment::create([
                'vehicle_registration_id' => $registration->id,
                'amount' => $rfid_fee,
                'or_number' => 'REG-' . strtoupper(bin2hex(random_bytes(4))), // Auto-gen unique OR for office direct reg
                'paid_at' => now()
            ]);

            // 2. Create Vehicle record (This is what displays in the Registered Accounts list)
            \App\Models\Vehicle::create([
                'user_id'         => $registration->id,
                'plate_number'    => $registration->plate_number,
                'vehicle_details' => trim(($registration->make_brand ?? '') . ' ' . ($registration->model_name ?? '')),
                'vehicle_type'    => $registration->vehicle_type,
                'rfid_tag'        => $registration->rfid_tag_id,
                'expiry_date'     => $registration->validity_to,
            ]);

            // 3. Mark as ACTIVE
            $registration->update(['status' => 'ACTIVE']);
        }

        if ($request->expectsJson() || $request->ajax() || $request->hasHeader('X-Requested-With')) {
            return response()->json([
                'success' => true,
                'message' => 'Registration completed successfully and is now active.'
            ]);
        }

        return redirect()->route('office.registration')
            ->with('success', 'Registration completed successfully and is now active.');
    }

    public function users()
    {
        $registrations = VehicleRegistration::with(['vehicles', 'payments'])->orderByDesc('created_at')->get();
        
        $totalUsers = $registrations->count();
        $activeTags = $registrations->whereNotNull('rfid_tag_id')->count();
        $pendingReg = $registrations->where('status', 'pending')->count();
        $verifiedReg = $registrations->where('status', 'verified')->count();

        $roleCounts = $registrations->groupBy('role')->map->count();
        $summary = [
            'student' => ['label' => 'Students', 'count' => $roleCounts['student'] ?? 0],
            'faculty' => ['label' => 'Faculty', 'count' => $roleCounts['faculty'] ?? 0],
            'staff' => ['label' => 'Non-Teaching', 'count' => $roleCounts['staff'] ?? 0],
        ];
        foreach ($summary as $key => $item) {
            $summary[$key]['percent'] = $totalUsers > 0 ? round(($item['count'] / $totalUsers) * 100) : 0;
        }

        $brands = \App\Models\VehicleBrand::with('models')->orderBy('name')->get();
        $categories = \App\Models\VehicleCategory::where('is_active', true)->orderBy('name')->get();

        return view('office.users', compact('registrations', 'totalUsers', 'activeTags', 'pendingReg', 'verifiedReg', 'summary', 'brands', 'categories'));
    }

    /**
     * Show a single registration (JSON).
     */
    public function show($id)
    {
        $registration = VehicleRegistration::findOrFail($id);
        return response()->json(['success' => true, 'data' => $registration]);
    }

    /**
     * Update a registration using the same fields as create.
     */
    public function update(Request $request, $id)
    {
        $registration = VehicleRegistration::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:student,faculty,staff',
            'firstName' => 'nullable|string|max:100',
            'lastName' => 'nullable|string|max:100',
            'middleName' => 'nullable|string|max:100',
            'fullName' => 'nullable|string|max:255',
            'universityId' => 'nullable|string|max:255',
            'collegeDept' => 'nullable|string|max:255',
            'contactNumber' => 'required|string|max:20',
            'emailAddress' => 'nullable|email|max:255',
            'vehicleType' => 'required|string|max:100', // dynamic category
            'makeBrand' => 'required|string|max:255',
            'modelName' => 'nullable|string|max:255',
            'plateNumber' => 'required|string|max:20',
            'validityFrom' => 'required|date',
            'validityTo' => 'required|date|after:validityFrom',
            'rfidTagId' => 'required|string|unique:vehicle_registrations,rfid_tag_id,' . $registration->id,
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $fullName = $request->fullName;
        if ($request->has('firstName') && $request->has('lastName')) {
            $fullName = trim($request->firstName . ' ' . ($request->middleName ?? '') . ' ' . $request->lastName);
            $fullName = preg_replace('/\s+/', ' ', $fullName);
        }

        $data = [
            'role' => $request->role,
            'first_name' => $request->firstName,
            'last_name' => $request->lastName,
            'middle_name' => $request->middleName,
            'full_name' => $fullName ?? $registration->full_name,
            'university_id' => $request->universityId ?? 'N/A',
            'college_dept' => $request->collegeDept ?? 'N/A',
            'contact_number' => $request->contactNumber,
            'email_address' => $request->emailAddress ?? 'N/A',
            'course' => $request->course,
            'year_level' => $request->yearLevel,
            'rank' => $request->rank,
            'office' => $request->office,
            'business_stall_name' => $request->businessStallName,
            'vendor_address' => $request->vendorAddress,
            'vehicle_type' => $request->vehicleType,
            'registered_owner' => $request->registeredOwner ?? 'N/A',
            'make_brand' => $request->makeBrand,
            'model_name' => $request->modelName,
            'model_year' => $request->modelYear ?? 'N/A',
            'color' => $request->color ?? 'N/A',
            'plate_number' => $request->plateNumber,
            'engine_number' => $request->engineNumber ?? 'N/A',
            'sticker_classification' => $request->stickerClassification ?? [],
            'requirements' => $request->requirements ?? [],
            'validity_from' => $request->validityFrom,
            'validity_to' => $request->validityTo,
            'rfid_tag_id' => $request->rfidTagId,
            'status' => ($registration->status === 'expired' && Carbon::parse($request->validityTo)->isFuture()) ? 'approved' : $registration->status,
            'office_user_id' => Auth::id(),
        ];

        $wasTagged = !empty($registration->rfid_tag_id);
        $registration->update($data);
        $isTagged = !empty($registration->rfid_tag_id);

        // Record payment if newly tagged
        if (!$wasTagged && $isTagged) {
            $rfid_fee = (float)\App\Models\SystemSetting::get('rfid_fee', 100);
            \App\Models\Payment::create([
                'vehicle_registration_id' => $registration->id,
                'amount' => $rfid_fee,
                'or_number' => 'RENEW-' . strtoupper(bin2hex(random_bytes(4))),
                'paid_at' => now()
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Registration updated successfully.',
                'data' => $registration,
            ]);
        }

        return redirect()->route('office.registration', ['id' => $registration->id])
            ->with('success', 'Registration updated successfully.');
    }

    /**
     * Delete a registration.
     */
    public function destroy($id)
    {
        $registration = VehicleRegistration::findOrFail($id);
        $registration->delete();

        return response()->json([
            'success' => true,
            'message' => 'Registration deleted.',
        ]);
    }

    public function stats()
    {
        // 1. Total Entries and Exits
        $totalEntries = VehicleLog::where('type', 'entry')->count();
        $totalExits = VehicleLog::where('type', 'exit')->count();

        // 2. Peak Hour
        $driver = \DB::connection()->getDriverName();
        $hourExpr = $driver === 'sqlite' ? "strftime('%H', timestamp)" : "HOUR(timestamp)";

        $peakMatch = VehicleLog::selectRaw("$hourExpr as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->orderByDesc('count')
            ->first();

        $peakHour = 'N/A';
        if ($peakMatch) {
            $peakHour = Carbon::createFromTime($peakMatch->hour, 0)->format('h:i A');
        }

        // 3. Monthly Registration Trends (Last 6 Months)
        $months = [];
        $counts = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M');
            $count = VehicleRegistration::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            
            $months[] = $monthName;
            $counts[] = $count;
        }

        return view('office.stats', compact('totalEntries', 'totalExits', 'peakHour', 'months', 'counts'));
    }

    public function checkTag(Request $request)
    {
        $tagId = $request->query('tagId');
        $excludeId = $request->query('excludeId');
        
        $query = VehicleRegistration::where('rfid_tag_id', $tagId);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $registration = $query->first();

        if ($registration) {
            return response()->json([
                'exists' => true,
                'message' => 'This tag is already assigned to ' . $registration->full_name . '.',
                'owner' => $registration->full_name,
                'registration_id' => $registration->id
            ]);
        }

        return response()->json(['exists' => false]);
    }
    public function verify($id)
    {
        $registration = VehicleRegistration::findOrFail($id);
        
        if ($registration->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This registration is already ' . $registration->status . '.'
            ], 400);
        }

        $registration->update(['status' => 'verified']);

        // Send Email
        if ($registration->email_address && filter_var($registration->email_address, FILTER_VALIDATE_EMAIL)) {
            \Illuminate\Support\Facades\Mail::to($registration->email_address)
                ->send(new \App\Mail\RegistrationVerified($registration));
        }

        return response()->json([
            'success' => true,
            'message' => 'Registration verified! An email has been sent to the applicant.'
        ]);
    }

    public function reject(Request $request, $id)
    {
        $registration = VehicleRegistration::findOrFail($id);
        
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $registration->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason
        ]);

        // Send Email
        if ($registration->email_address && filter_var($registration->email_address, FILTER_VALIDATE_EMAIL)) {
            \Illuminate\Support\Facades\Mail::to($registration->email_address)
                ->send(new \App\Mail\RegistrationRejected($registration));
        }

        return response()->json([
            'success' => true,
            'message' => 'Registration rejected and applicant notified.'
        ]);
    }

    public function validateStoredDocument($id, $type)
    {
        $registration = VehicleRegistration::findOrFail($id);
        
        $pathMap = [
            'cr_file' => 'cr_path',
            'or_file' => 'or_path',
            'license_file' => 'license_path',
            'cor_file' => 'cor_path',
            'student_id_file' => 'student_id_path',
            'employee_id_file' => 'employee_id_path',
            'extra' => ['cor_path', 'employee_id_path', 'student_id_path']
        ];

        $path = null;
        if ($type === 'extra') {
            foreach ($pathMap['extra'] as $f) {
                if ($registration->$f) {
                    $path = $registration->$f;
                    $type = str_replace('_path', '', $f);
                    break;
                }
            }
        } else {
            $column = $pathMap[$type] ?? null;
            $path = $column ? $registration->$column : null;
        }

        if (!$path || !\Storage::exists($path)) {
            return response()->json(['success' => false, 'message' => 'File not found.']);
        }

        $validator = new \App\Services\DocumentValidationService();
        $fullPath = storage_path('app/' . $path);
        
        $result = $validator->validate($fullPath, $type);
        return response()->json($result);
    }

    // ─────────────────────────────────────────────────────
    // BRIDGE AUTO-LAUNCH
    // ─────────────────────────────────────────────────────

    /**
     * Check if bridge_service.py is already listening on port 8080.
     * If not, launch it in the background automatically.
     *
     * Called by both Office and Guard frontends before opening WebSocket.
     */
    public function startBridge()
    {
        $port       = 8080;
        $scriptPath = realpath(base_path('../bridge_service.py'));

        // 1. Check if the port is already open (bridge already running)
        $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
        if ($connection) {
            fclose($connection);
            return response()->json([
                'status'  => 'already_running',
                'message' => 'Bridge is already active on port ' . $port . '.'
            ]);
        }

        // 2. Validate that the script exists
        if (!$scriptPath || !file_exists($scriptPath)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'bridge_service.py not found at: ' . $scriptPath
            ], 404);
        }

        // 3. Launch in background (Windows: start /B keeps it detached)
        $cmd = 'start /B python "' . $scriptPath . '" > NUL 2>&1';
        pclose(popen($cmd, 'r'));

        // 4. Give the process up to 3 s to bind the port
        $started = false;
        for ($i = 0; $i < 6; $i++) {
            usleep(500000);   // 0.5 s
            $check = @fsockopen('127.0.0.1', $port, $e, $s, 1);
            if ($check) {
                fclose($check);
                $started = true;
                break;
            }
        }

        if ($started) {
            return response()->json([
                'status'  => 'started',
                'message' => 'Bridge launched successfully.'
            ]);
        }

        return response()->json([
            'status'  => 'timeout',
            'message' => 'Bridge was launched but did not respond within 3 s. Try clicking Connect again.'
        ], 202);
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

    public function demographics()
    {
        $roles = ['student', 'faculty', 'staff'];
        $stats = [];
        $pieData = [
            'outer' => [], // Roles
            'inner' => []  // Vehicle Types
        ];

        foreach ($roles as $role) {
            $owners = VehicleRegistration::where('role', $role)->get();
            $ownerCount = $owners->count();
            
            $vehicles = \App\Models\Vehicle::whereIn('user_id', $owners->pluck('id'))->get();
            $vehicleCount = $vehicles->count();
            
            $vTypes = $vehicles->groupBy('vehicle_type')->map->count();
            
            $stats[$role] = [
                'owners' => $ownerCount,
                'vehicles' => $vehicleCount,
                'ratio' => $ownerCount > 0 ? round($vehicleCount / $ownerCount, 1) : 0,
                'breakdown' => $vTypes,
                'top_multi' => VehicleRegistration::withCount('vehicles')
                    ->where('role', $role)
                    ->orderByDesc('vehicles_count')
                    ->take(3)
                    ->get()
            ];

            $pieData['outer'][] = ['label' => ucfirst($role), 'value' => $ownerCount];
            foreach($vTypes as $type => $count) {
                $pieData['inner'][] = ['label' => ucfirst($role) . ' ' . ucfirst($type), 'value' => $count];
            }
        }

        // Summary Stats (Fleet)
        $totalOwners = VehicleRegistration::count();
        $totalVehicles = \App\Models\Vehicle::count();

        $popularCategoryRaw = VehicleRegistration::select('vehicle_type', \DB::raw('count(*) as total'))
            ->groupBy('vehicle_type')
            ->orderByDesc('total')
            ->first();
        $popularCategory = $popularCategoryRaw 
            ? $popularCategoryRaw->vehicle_type . ' (' . round(($popularCategoryRaw->total / max($totalOwners, 1)) * 100) . '%)' 
            : 'N/A';

        $popularBrandRaw = VehicleRegistration::select('make_brand', \DB::raw('count(*) as total'))
            ->groupBy('make_brand')
            ->orderByDesc('total')
            ->first();
        $popularBrand = $popularBrandRaw ? $popularBrandRaw->make_brand : 'N/A';

        $summary = [
            'popularCategory' => $popularCategory,
            'popularBrand' => $popularBrand,
            'totalVehicles' => $totalVehicles
        ];

        // Real-Time Occupancy Analysis
        $today = now()->toDateString();
        $latestLogsIds = VehicleLog::whereDate('timestamp', $today)
            ->select(\DB::raw('MAX(id) as id'))
            ->groupBy('vehicle_id')
            ->pluck('id');

        $insideLogs = VehicleLog::with(['vehicle', 'vehicleRegistration'])
            ->whereIn('id', $latestLogsIds)
            ->where('type', 'entry')
            ->get();

        $occupancyBreakdown = [];
        foreach($roles as $r) {
            $roleLogs = $insideLogs->filter(function($l) use ($r) {
                return $l->vehicleRegistration && $l->vehicleRegistration->role === $r;
            });
            $occupancyBreakdown[$r] = [
                'total' => $roleLogs->count(),
                'types' => $roleLogs->groupBy(fn($l) => $l->vehicle->vehicle_type ?? 'Other')->map->count()
            ];
        }

        return view('office.stats.demographics', compact('stats', 'pieData', 'occupancyBreakdown', 'totalOwners', 'totalVehicles', 'summary'));
    }

    public function expiry()
    {
        $now = now();
        $expired = VehicleRegistration::where('validity_to', '<', $now->toDateString())->count();
        $critical = VehicleRegistration::where('validity_to', '>', $now->toDateString())
            ->where('validity_to', '<=', now()->addDays(15)->toDateString())->count();
        
        $activeRegistrations = VehicleRegistration::whereNotNull('rfid_tag_id')
            ->orderBy('validity_to', 'asc')
            ->get();

        $healthy = $activeRegistrations->where('validity_to', '>', now()->addDays(15)->toDateString())->count();
            
        $total = $activeRegistrations->count();
        $expiredPerc = $total > 0 ? round(($expired / $total) * 100) : 0;
        $criticalPerc = $total > 0 ? round(($critical / $total) * 100) : 0;
        $healthyPerc = $total > 0 ? round(($healthy / $total) * 100) : 0;

        return view('office.stats.expiry', compact('expired', 'critical', 'healthy', 'total', 'expiredPerc', 'criticalPerc', 'healthyPerc', 'activeRegistrations'));
    }

    public function sendExpiryAlerts()
    {
        $now = now();
        $target = now()->addDays(15);
        
        $registrations = \App\Models\VehicleRegistration::whereNotNull('email_address')
            ->where('status', 'ACTIVE')
            ->where('validity_to', '>', $now->toDateString())
            ->where('validity_to', '<=', $target->toDateString())
            ->get();

        $count = 0;
        foreach ($registrations as $reg) {
            if (filter_var($reg->email_address, FILTER_VALIDATE_EMAIL)) {
                \Illuminate\Support\Facades\Mail::to($reg->email_address)
                    ->send(new \App\Mail\TagExpiringReminder($reg));
                $count++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Renewal notifications successfully dispatched to $count vehicle owners."
        ]);
    }

    public function behavior(Request $request)
    {
        $start = $request->query('start', now()->subDays(6)->toDateString());
        $end = $request->query('end', now()->toDateString());

        $logsQuery = VehicleLog::whereBetween('timestamp', [$start . ' 00:00:00', $end . ' 23:59:59']);

        // 1. Total Active Users (Filtered)
        $activeUserIds = (clone $logsQuery)->whereNotNull('vehicle_registration_id')
            ->distinct('vehicle_registration_id')
            ->pluck('vehicle_registration_id');
        $totalActiveUsers = $activeUserIds->count();

        // 2. Peak Activity Day
        $peakDayRaw = (clone $logsQuery)->select(\DB::raw('DATE(timestamp) as date, count(*) as total'))
            ->groupBy('date')
            ->orderByDesc('total')
            ->first();
        $peakActivityDay = $peakDayRaw ? Carbon::parse($peakDayRaw->date)->format('M d, Y') : 'N/A';

        // 3. Most Frequent Role
        $mostFreqRoleRaw = (clone $logsQuery)->join('vehicle_registrations', 'vehicle_logs.vehicle_registration_id', '=', 'vehicle_registrations.id')
            ->select(\DB::raw('vehicle_registrations.role, count(*) as total'))
            ->groupBy('vehicle_registrations.role')
            ->orderByDesc('total')
            ->first();
        $mostFrequentRole = $mostFreqRoleRaw ? ucfirst($mostFreqRoleRaw->role) : 'N/A';

        // 4. Average Scans per Day
        $diffDays = Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1;
        $totalScans = (clone $logsQuery)->count();
        $avgScansPerDay = $diffDays > 0 ? round($totalScans / $diffDays, 1) : $totalScans;

        // Frequent Explorers: Top 10
        $frequentFlyersRaw = VehicleLog::select(\DB::raw('vehicle_registration_id, count(*) as total'))
            ->whereNotNull('vehicle_registration_id')
            ->whereBetween('timestamp', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->groupBy('vehicle_registration_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
            
        $frequentFlyers = $frequentFlyersRaw->map(function($f) {
            $reg = VehicleRegistration::find($f->vehicle_registration_id);
            return (object)[
                'name' => $reg->full_name ?? 'Unknown Owner',
                'role' => ucfirst($reg->role ?? 'N/A'),
                'count' => $f->total
            ];
        });

        // Multi-vehicle owners
        $multiOwners = VehicleRegistration::withCount('vehicles')
            ->get()
            ->filter(function($reg) { return $reg->vehicles_count > 1; })
            ->sortByDesc('vehicles_count')
            ->take(5);

        $period = $this->calculateTrendData($start, $end);

        if ($request->ajax()) {
            return response()->json([
                'frequentFlyers' => $frequentFlyers,
                'labels' => $period['labels'],
                'activityCounts' => $period['counts'],
                'summary' => [
                    'activeUsers' => $totalActiveUsers,
                    'peakDay' => $peakActivityDay,
                    'frequentRole' => $mostFrequentRole,
                    'avgScans' => $avgScansPerDay
                ]
            ]);
        }

        return view('office.stats.behavior', [
            'frequentFlyers' => $frequentFlyers,
            'multiOwners' => $multiOwners,
            'labels' => $period['labels'],
            'activityCounts' => $period['counts'],
            'startDate' => $start,
            'endDate' => $end,
            'summary' => [
                'activeUsers' => $totalActiveUsers,
                'peakDay' => $peakActivityDay,
                'frequentRole' => $mostFrequentRole,
                'avgScans' => $avgScansPerDay
            ]
        ]);
    }

    private function calculateTrendData($start, $end)
    {
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);
        $diffDays = $startDate->diffInDays($endDate);

        $labels = [];
        $counts = [];

        if ($diffDays <= 1) { // Today/Single Day - Hourly view
            for ($i = 0; $i < 24; $i++) {
                $labels[] = Carbon::createFromTime($i, 0)->format('ga');
                $counts[] = VehicleLog::whereBetween('timestamp', [
                    $startDate->copy()->startOfDay()->addHours($i),
                    $startDate->copy()->startOfDay()->addHours($i)->addMinutes(59)->addSeconds(59)
                ])->count();
            }
        } elseif ($diffDays <= 31) { // Up to a month - Daily view
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $labels[] = $current->format('M d');
                $counts[] = VehicleLog::whereDate('timestamp', $current->toDateString())->count();
                $current->addDay();
            }
        } elseif ($diffDays <= 365) { // Up to a year - Weekly/Monthly view
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $monthLabel = $current->format('M Y');
                $labels[] = $monthLabel;
                $counts[] = VehicleLog::whereMonth('timestamp', $current->month)
                    ->whereYear('timestamp', $current->year)
                    ->count();
                $current->addMonth();
            }
        } else { // Over a year
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $labels[] = $current->format('Y');
                $counts[] = VehicleLog::whereYear('timestamp', $current->year)->count();
                $current->addYear();
            }
        }

        return ['labels' => $labels, 'counts' => $counts];
    }

    /**
     * AJAX Search for Behavior Analysis Table
     */
    public function behaviorSearch(Request $request)
    {
        $q = $request->query('q');
        $role = $request->query('role');
        $start = $request->query('start', now()->subDays(6)->toDateString());
        $end = $request->query('end', now()->toDateString());

        $owners = VehicleRegistration::withCount('vehicles')
            ->when($q, function($query) use ($q) {
                $query->where(function($sub) use ($q) {
                    $sub->where('full_name', 'like', "%{$q}%")
                        ->orWhere('university_id', 'like', "%{$q}%");
                });
            })
            ->when($role && $role !== 'all', function($query) use ($role) {
                $query->where('role', $role);
            })
            ->get();
            
        $results = $owners->map(function($owner) use ($start, $end) {
            $entries = VehicleLog::where('vehicle_registration_id', $owner->id)
                ->where('type', 'entry')
                ->whereBetween('timestamp', [$start . ' 00:00:00', $end . ' 23:59:59'])
                ->count();
            
            $exits = VehicleLog::where('vehicle_registration_id', $owner->id)
                ->where('type', 'exit')
                ->whereBetween('timestamp', [$start . ' 00:00:00', $end . ' 23:59:59'])
                ->count();

            $total = $entries + $exits;

            return [
                'id' => $owner->id,
                'name' => $owner->full_name,
                'role' => ucfirst($owner->role),
                'vehicles' => $owner->vehicles_count,
                'activity' => $total,
                'entries' => $entries,
                'exits' => $exits
            ];
        })->sortByDesc('activity')->values();

        return response()->json($results);
    }

    /**
     * Deep Audit for a specific owner
     */
    public function analyzeOwner($id)
    {
        $owner = VehicleRegistration::with('vehicles')->findOrFail($id);
        
        // 30-Day Trend (Daily Entries vs Exits)
        $labels = [];
        $entries = [];
        $exits = [];
        
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('M d');
            
            $entries[] = VehicleLog::where('vehicle_registration_id', $id)
                ->where('type', 'entry')
                ->whereDate('timestamp', $date)
                ->count();
                
            $exits[] = VehicleLog::where('vehicle_registration_id', $id)
                ->where('type', 'exit')
                ->whereDate('timestamp', $date)
                ->count();
        }

        // Peak Hours Calculation (0-23)
        $freq = array_fill(0, 24, 0);
        $logs = VehicleLog::where('vehicle_registration_id', $id)->get();
        foreach($logs as $log) {
            $h = (int)$log->timestamp->format('H');
            $freq[$h]++;
        }
        
        arsort($freq);
        $peakHour = array_key_first($freq);
        $peakLabel = \Carbon\Carbon::createFromTime($peakHour, 0)->format('g:i A');

        // Most Used Vehicle
        $mostUsedRaw = VehicleLog::select(\DB::raw('vehicle_id, count(*) as total'))
            ->where('vehicle_registration_id', $id)
            ->groupBy('vehicle_id')
            ->orderByDesc('total')
            ->first();
            
        $mostUsedPlate = 'N/A';
        if ($mostUsedRaw) {
            $v = \App\Models\Vehicle::find($mostUsedRaw->vehicle_id);
            if ($v) $mostUsedPlate = $v->plate_number;
        }

        // Latest Logs (Latest 10)
        $latestLogs = VehicleLog::with('vehicle')
            ->where('vehicle_registration_id', $id)
            ->latest('timestamp')
            ->limit(10)
            ->get()
            ->map(function($log) {
                return [
                    'timestamp' => $log->timestamp->format('M d, Y h:i A'),
                    'type' => strtoupper($log->type),
                    'plate' => $log->vehicle->plate_number ?? 'N/A'
                ];
            });

        return response()->json([
            'success' => true,
            'owner' => [
                'name' => $owner->full_name,
                'role' => ucfirst($owner->role),
                'joined' => $owner->created_at->format('M Y'),
                'vehicles_count' => $owner->vehicles->count()
            ],
            'stats' => [
                'labels' => $labels,
                'entries' => $entries,
                'exits' => $exits,
                'peak_hour' => $peakLabel,
                'most_used' => $mostUsedPlate,
                'total_activity' => $logs->count(),
                'latest_logs' => $latestLogs
            ]
        ]);
    }

    /**
     * Add a new vehicle to an existing registration.
     */
    public function addVehicle(Request $request, $id)
    {
        $registration = VehicleRegistration::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'plate_number'    => 'required|string|unique:vehicles,plate_number',
            'rfid_tag'        => 'required|string|unique:vehicles,rfid_tag',
            'vehicle_details' => 'nullable|string|max:255',
            'vehicle_type'    => 'required|string|max:100', // dynamic category name
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $vehicle = new Vehicle();
        $vehicle->user_id         = $registration->id;
        $vehicle->plate_number    = $request->plate_number;
        // Construct detailed string from dynamic components
        $details = trim(($registration->make_brand ?? '') . ' ' . ($registration->model_name ?? '') . ' ' . ($registration->model_year ?? ''));
        $vehicle->vehicle_details = $request->vehicle_details ?: $details; 
        $vehicle->vehicle_type    = $request->vehicle_type ?: $registration->vehicle_type;
        $vehicle->rfid_tag        = $request->rfid_tag;
        $vehicle->expiry_date     = $registration->validity_to; 
        $vehicle->save();

        // Update registration: set RFID tag and activate
        $registration->update([
            'rfid_tag_id'  => $request->rfid_tag,
            'status'       => 'ACTIVE',
            'validity_from' => now()->toDateString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vehicle linked! Account for ' . $registration->full_name . ' is now ACTIVE.'
        ]);
    }
}
