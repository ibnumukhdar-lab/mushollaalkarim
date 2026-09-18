<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hanya akun pengelola (peran admin) yang boleh masuk panel /kelola.
 */
class PanelAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $u = $request->user();

        if (! $u || ! $u->aktif || $u->peran !== 'admin') {
            abort(403, 'Halaman ini hanya untuk pengelola musholla.');
        }

        return $next($request);
    }
}
