<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Office\DashboardController as OfficeDashboard;
use App\Http\Controllers\Guard\DashboardController as GuardDashboard;

// Landing & Public Routes
Route::get('/', [App\Http\Controllers\LandingController::class, 'index'])->name('landing');
Route::get('/online-registration', [App\Http\Controllers\LandingController::class, 'showRegistrationForm'])->name('online-registration');
Route::post('/online-registration', [App\Http\Controllers\LandingController::class, 'submitRegistration'])->name('online-registration.submit');
Route::post('/online-registration/validate-document', [App\Http\Controllers\LandingController::class, 'validateDocument'])->name('online-registration.validate');

// Public AJAX: Dependent Dropdown Data
Route::get('/api/brands/{id}', [\App\Http\Controllers\Admin\FleetAssetController::class, 'brandsByCategory'])->name('api.brands');
Route::get('/api/models/{id}', [\App\Http\Controllers\Admin\FleetAssetController::class, 'modelsByBrand'])->name('api.models');

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

// Shared Authenticated Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/bridge/start',  [OfficeDashboard::class, 'startBridge'])->name('bridge.start');
    Route::get('/bridge/status', [OfficeDashboard::class, 'bridgeStatus'])->name('bridge.status');
    Route::post('/bridge/heartbeat', [AdminDashboard::class, 'bridgeHeartbeat']);
    Route::post('/bridge/sync',      [AdminDashboard::class, 'bridgeSync']);
    
    // Admin's system logs view
    Route::get('/admin/logs', [AdminDashboard::class, 'systemLogs'])->name('admin.logs');
    
    // User Profile & Security update
    Route::post('/profile/update', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/login-history', [App\Http\Controllers\ProfileController::class, 'getLoginHistory'])->name('profile.login-history');
    
    // Smart Dashboard Redirect
    Route::get('/dashboard', function () {
        $role = auth()->user()->role;
        return redirect()->route($role . '.dashboard');
    })->name('dashboard');

    // Financial & Issuance (Shared Admin/Office)
    Route::get('/manage/payments/pending', [\App\Http\Controllers\Admin\FinancialController::class, 'pendingPayments'])->name('payments.pending');
    Route::post('/manage/payments/process', [\App\Http\Controllers\Admin\FinancialController::class, 'processPayment'])->name('payments.process');
    Route::get('/manage/payments/ledger', [\App\Http\Controllers\Admin\FinancialController::class, 'financialLedger'])->name('payments.ledger');
});

// Admin Routes
Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminDashboard::class, 'users'])->name('users');
    Route::post('/users', [AdminDashboard::class, 'storeUser'])->name('users.store');
    Route::put('/users/{id}', [AdminDashboard::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{id}', [AdminDashboard::class, 'deleteUser'])->name('users.delete');
    Route::get('/rfid', [AdminDashboard::class, 'rfid'])->name('rfid');
    Route::post('/rfid', [AdminDashboard::class, 'storeRegistration'])->name('rfid.store');
    Route::post('/rfid/{id}/toggle-status', [AdminDashboard::class, 'toggleStatus'])->name('rfid.toggle-status');
    Route::get('/rfid/{id}', [AdminDashboard::class, 'showRegistration'])->name('rfid.show');
    Route::get('/reports', [AdminDashboard::class, 'reports'])->name('reports');
    Route::get('/reports/export', [AdminDashboard::class, 'exportExcel'])->name('reports.export');
    Route::get('/traffic-analytics', [GuardDashboard::class, 'analytics'])->name('traffic-analytics');
    Route::get('/traffic-analytics/data', [GuardDashboard::class, 'fetchAnalyticsData'])->name('traffic-analytics.data');
    Route::get('/check-tag', [OfficeDashboard::class, 'checkTag'])->name('check-tag');
    Route::get('/settings', [AdminDashboard::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminDashboard::class, 'updateSettings'])->name('settings.update');
    Route::post('/lockdown/toggle', [AdminDashboard::class, 'toggleLockdown'])->name('lockdown.toggle');

    // Fleet Assets CRUD
    Route::get('/manage/fleet', [\App\Http\Controllers\Admin\FleetAssetController::class, 'index'])->name('manage.fleet');
    Route::post('/manage/fleet/brand', [\App\Http\Controllers\Admin\FleetAssetController::class, 'storeBrand'])->name('manage.fleet.brand.store');
    Route::put('/manage/fleet/brand/{id}', [\App\Http\Controllers\Admin\FleetAssetController::class, 'updateBrand'])->name('manage.fleet.brand.update');
    Route::delete('/manage/fleet/brand/{id}', [\App\Http\Controllers\Admin\FleetAssetController::class, 'destroyBrand'])->name('manage.fleet.brand.destroy');
    
    Route::post('/manage/fleet/model', [\App\Http\Controllers\Admin\FleetAssetController::class, 'storeModel'])->name('manage.fleet.model.store');
    Route::put('/manage/fleet/model/{id}', [\App\Http\Controllers\Admin\FleetAssetController::class, 'updateModel'])->name('manage.fleet.model.update');
    Route::delete('/manage/fleet/model/{id}', [\App\Http\Controllers\Admin\FleetAssetController::class, 'destroyModel'])->name('manage.fleet.model.destroy');

    Route::post('/manage/fleet/category', [\App\Http\Controllers\Admin\FleetAssetController::class, 'storeCategory'])->name('manage.fleet.category.store');
    Route::post('/manage/fleet/category/{id}/toggle', [\App\Http\Controllers\Admin\FleetAssetController::class, 'toggleCategory'])->name('manage.fleet.category.toggle');

    // AJAX endpoints (also available for admin context)
    Route::get('/manage/fleet/api/brands-by-category/{id}', [\App\Http\Controllers\Admin\FleetAssetController::class, 'brandsByCategory']);
    Route::get('/manage/fleet/api/models-by-brand/{id}',    [\App\Http\Controllers\Admin\FleetAssetController::class, 'modelsByBrand']);

    // Academic Data CRUD
    Route::get('/manage/academic', [\App\Http\Controllers\Admin\AcademicDataController::class, 'index'])->name('manage.academic');
    Route::post('/manage/academic/college', [\App\Http\Controllers\Admin\AcademicDataController::class, 'storeCollege'])->name('manage.academic.college.store');
    Route::put('/manage/academic/college/{id}', [\App\Http\Controllers\Admin\AcademicDataController::class, 'updateCollege'])->name('manage.academic.college.update');
    Route::delete('/manage/academic/college/{id}', [\App\Http\Controllers\Admin\AcademicDataController::class, 'destroyCollege'])->name('manage.academic.college.destroy');

    Route::post('/manage/academic/course', [\App\Http\Controllers\Admin\AcademicDataController::class, 'storeCourse'])->name('manage.academic.course.store');
    Route::put('/manage/academic/course/{id}', [\App\Http\Controllers\Admin\AcademicDataController::class, 'updateCourse'])->name('manage.academic.course.update');
    Route::delete('/manage/academic/course/{id}', [\App\Http\Controllers\Admin\AcademicDataController::class, 'destroyCourse'])->name('manage.academic.course.destroy');

});

// Office Routes
Route::middleware(['role:office'])->prefix('office')->name('office.')->group(function () {
    Route::get('/dashboard', [OfficeDashboard::class, 'index'])->name('dashboard');
    Route::get('/registration', [OfficeDashboard::class, 'registration'])->name('registration');
    Route::post('/registration', [OfficeDashboard::class, 'store'])->name('registration.store');
    Route::get('/registration/{id}', [OfficeDashboard::class, 'show'])->name('registration.show');
    Route::put('/registration/{id}', [OfficeDashboard::class, 'update'])->name('registration.update');
    Route::delete('/registration/{id}', [OfficeDashboard::class, 'destroy'])->name('registration.destroy');
    Route::get('/users', [OfficeDashboard::class, 'users'])->name('users');
    Route::post('/users/{id}/add-vehicle', [OfficeDashboard::class, 'addVehicle'])->name('users.add-vehicle');
    Route::get('/stats', [OfficeDashboard::class, 'stats'])->name('stats');
    Route::get('/stats/demographics', [OfficeDashboard::class, 'demographics'])->name('stats.demographics');
    Route::get('/stats/expiry', [OfficeDashboard::class, 'expiry'])->name('stats.expiry');
    Route::get('/stats/behavior', [OfficeDashboard::class, 'behavior'])->name('stats.behavior');
    Route::get('/stats/behavior/search', [OfficeDashboard::class, 'behaviorSearch'])->name('stats.behavior.search');
    Route::get('/stats/behavior/{id}/analyze', [OfficeDashboard::class, 'analyzeOwner'])->name('stats.behavior.analyze');
    Route::post('/stats/expiry/send-alerts', [OfficeDashboard::class, 'sendExpiryAlerts'])->name('stats.expiry.alerts');
    Route::get('/check-tag', [OfficeDashboard::class, 'checkTag'])->name('registration.checkTag');
    Route::post('/registration/{id}/verify', [OfficeDashboard::class, 'verify'])->name('registration.verify');
    Route::post('/registration/{id}/reject', [OfficeDashboard::class, 'reject'])->name('registration.reject');
    Route::post('/registration/validate-stored/{id}/{type}', [OfficeDashboard::class, 'validateStoredDocument'])->name('registration.validate-stored');
});

// Guard Routes
Route::middleware(['role:guard'])->prefix('guard')->name('guard.')->group(function () {
    Route::get('/dashboard', [GuardDashboard::class, 'index'])->name('dashboard');
    Route::get('/entry', [GuardDashboard::class, 'entry'])->name('entry');
    Route::get('/exit', [GuardDashboard::class, 'exit'])->name('exit');
    
    // Hardware integration routes
    Route::get('/lookup-tag', [GuardDashboard::class, 'lookupTag'])->name('lookup.tag');
    Route::post('/log-vehicle', [GuardDashboard::class, 'logVehicle'])->name('log.vehicle');
    Route::post('/log-vehicle/{id}/toggle', [GuardDashboard::class, 'toggleLogType'])->name('log.toggle');
    Route::get('/analytics', [GuardDashboard::class, 'analytics'])->name('analytics')->middleware('role:guard,admin');
    Route::get('/analytics/data', [GuardDashboard::class, 'fetchAnalyticsData'])->name('analytics.data')->middleware('role:guard,admin');
    Route::get('/lockdown-check', [GuardDashboard::class, 'checkLockdown'])->name('lockdown.check');

    // Visitor manual entry routes
    Route::post('/visitor-entry', [GuardDashboard::class, 'storeVisitor'])->name('visitor.store');
    Route::post('/visitor-exit/{id}', [GuardDashboard::class, 'exitVisitor'])->name('visitor.exit.process');
    Route::get('/visitor/analytics', [GuardDashboard::class, 'visitorAnalytics'])->name('visitor.analytics');
});
