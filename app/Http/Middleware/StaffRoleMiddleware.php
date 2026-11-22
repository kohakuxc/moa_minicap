<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffRoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if (empty($roles)) {
            abort_unless($user->isStaff(), 403, 'Unauthorized staff action.');
            return $next($request);
        }

        // Flatten any comma-separated roles e.g. "optometrist,admin"
        $allowedRoles = [];
        foreach ($roles as $roleGroup) {
            foreach (explode(',', $roleGroup) as $r) {
                $trimmed = trim($r);
                if ($trimmed !== '') {
                    $allowedRoles[] = $trimmed;
                }
            }
        }

        // Admin always has access to staff routes
        if ($user->isAdmin() || in_array($user->role, $allowedRoles, true)) {
            return $next($request);
        }

        // Legacy doctor compatibility for optometrist
        if (in_array('optometrist', $allowedRoles, true) && $user->role === 'doctor') {
            return $next($request);
        }

        abort(403, 'You do not have the required staff permission for this section.');
    }
}
