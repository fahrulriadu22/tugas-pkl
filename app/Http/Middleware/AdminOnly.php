<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle($request, Closure $next)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses khusus admin'
            ], 403);
        }

        return $next($request);
    }
}

