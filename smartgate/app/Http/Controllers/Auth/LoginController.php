<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        if (Auth::attempt(['username' => $username, 'password' => $password])) {
            $user = Auth::user();
            $fullName = trim($user->first_name . ' ' . $user->middle_name . ' ' . $user->last_name);
            Session::put('user', $fullName);
            Session::put('role', $user->role);
            
            // Record login audit trail
            \App\Models\LoginLog::create([
                'user_id'    => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Redirect based on role
            if ($user->role === 'admin') return redirect()->route('admin.dashboard')->with('success', "Welcome back, $fullName!");
            if ($user->role === 'office') return redirect()->route('office.dashboard')->with('success', "Welcome back, $fullName!");
            if ($user->role === 'guard') return redirect()->route('guard.dashboard')->with('success', "Welcome back, $fullName!");
        }


        return back()->withErrors(['username' => 'Invalid credentials. Try admin/password, office/password, or guard/password.']);
    }

    public function logout()
    {
        Auth::logout();
        Session::flush();
        return redirect()->route('landing')->with('success', 'Logged out successfully. Have a nice day!');
    }
}
