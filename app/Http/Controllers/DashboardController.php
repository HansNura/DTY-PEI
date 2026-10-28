<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->hasRole(UserRole::Admin)) {
            return view('dashboard', [
                'user' => $user,
                'totalUsers' => User::count(),
                'activeUsers' => User::active()->count(),
                'roleCounts' => User::query()
                    ->selectRaw('role, count(*) as total')
                    ->groupBy('role')
                    ->pluck('total', 'role'),
                'latestUsers' => User::latest()->take(5)->get(),
            ]);
        }

        return view('dashboard', [
            'user' => $user,
        ]);
    }
}
