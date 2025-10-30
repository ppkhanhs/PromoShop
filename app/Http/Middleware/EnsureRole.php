<?php

namespace App\Http\Middleware;

use App\Auth\CassandraAuthenticatable;
use App\Models\Cassandra\CassandraModel;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  array<int, string>  $roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var Authenticatable|mixed|null $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $currentRole = '';
        $currentRole = match (true) {
            $user instanceof CassandraModel => strtolower((string) $user->get('role', '')),
            $user instanceof CassandraAuthenticatable => strtolower((string) $user->getAttribute('role')),
            $user instanceof EloquentModel => strtolower((string) $user->getAttribute('role')),
            isset($user->role) => strtolower((string) $user->role),
            default => '',
        };
        $allowedRoles = collect($roles)
            ->map(fn ($role) => strtolower(trim($role)))
            ->filter()
            ->all();

        $isAllowed = empty($allowedRoles) || in_array($currentRole, $allowedRoles, true);

        if (!$isAllowed) {
            if ($request->expectsJson()) {
                abort(403, 'Bạn không có quyền truy cập trang này.');
            }

            return redirect()
                ->route('client.home')
                ->with('error', 'Bạn không có quyền truy cập trang này.');
        }

        return $next($request);
    }
}
