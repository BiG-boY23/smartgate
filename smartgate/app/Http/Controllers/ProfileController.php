<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Update the authenticated user's profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'current_password' => ['required_with:new_password', 'nullable'],
            'new_password'     => ['nullable', 'confirmed', Password::min(8)],
            'profile_picture'  => ['nullable', 'image', 'max:2048'], // 2MB max
            'dark_mode'        => ['nullable', 'boolean'],
            'two_factor_enabled' => ['nullable', 'boolean'],
            'language'         => ['required', 'string', 'in:en,tl'],
        ]);

        // 1. Profile Picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $path = $request->file('profile_picture')->store('profiles', 'public');
            $user->profile_picture = $path;
        }

        // 2. Security check for password change
        if ($request->filled('new_password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The current password you provided is incorrect.'
                ], 422);
            }
            $user->password = Hash::make($request->new_password);
        }

        // 3. User Identity updates
        $user->first_name = $request->first_name;
        $user->last_name  = $request->last_name;
        $user->email      = $request->email;
        
        // 4. Preferences & Security settings
        $user->dark_mode          = $request->boolean('dark_mode');
        $user->two_factor_enabled = $request->boolean('two_factor_enabled');
        $user->language           = $request->language;
        
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile Updated! Your changes have been saved.'
        ]);
    }

    /**
     * Get login history for the current user
     */
    public function getLoginHistory()
    {
        $logs = Auth::user()->loginLogs()->orderBy('login_at', 'desc')->take(10)->get();
        return response()->json($logs);
    }
}
