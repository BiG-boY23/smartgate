<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        if (!Session::has('user')) {
            return redirect()->route('login');
        }

        $userRole = Session::get('role');
        $allowedRoles = explode(',', $roles);

        if (!in_array($userRole, $allowedRoles)) {
            // unauthorized, redirect to their own dashboard
             if ($userRole === 'admin') return redirect()->route('admin.dashboard');
             if ($userRole === 'office') return redirect()->route('office.dashboard');
             if ($userRole === 'guard') return redirect()->route('guard.dashboard');
             
             return redirect()->route('login');
        }

        return $next($request);
    }
}
