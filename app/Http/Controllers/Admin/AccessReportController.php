<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AccessMatrix;
use Illuminate\View\View;

class AccessReportController extends Controller
{
    public function index(): View
    {
        $modules = AccessMatrix::modules();
        $users = User::query()->get(['role', 'is_active']);
        $roles = UserRole::cases();

        $roleSummaries = collect($roles)->map(function (UserRole $role) use ($modules, $users): array {
            $accounts = $users->filter(fn (User $user): bool => $user->role === $role);
            $modulesForRole = collect($modules)
                ->filter(fn (array $module): bool => in_array($role, $module['roles'], true))
                ->values()
                ->all();

            return [
                'role' => $role,
                'total' => $accounts->count(),
                'active' => $accounts->where('is_active', true)->count(),
                'modules' => $modulesForRole,
            ];
        });

        return view('admin.access.index', [
            'roles' => $roles,
            'modules' => $modules,
            'moduleGroups' => collect($modules)->groupBy('group'),
            'roleSummaries' => $roleSummaries,
            'totalModules' => count($modules),
            'totalActiveUsers' => $users->where('is_active', true)->count(),
            'adminOnlyModules' => collect($modules)
                ->filter(fn (array $module): bool => count($module['roles']) === 1 && $module['roles'][0] === UserRole::Admin)
                ->count(),
        ]);
    }
}
