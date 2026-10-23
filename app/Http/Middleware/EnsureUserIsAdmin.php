<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Libera a rota apenas para administradores ativos.
     * Deve ser usado depois de auth:sanctum.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->active || ! $user->isAdmin()) {
            return response()->json([
                'message' => 'Acesso restrito a administradores.',
            ], 403);
        }

        return $next($request);
    }
}
