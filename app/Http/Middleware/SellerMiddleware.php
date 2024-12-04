<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SellerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->isSeller()) {
            return response()->json(['message' => 'Unauthorized. Seller access required.'], 403);
        }

        return $next($request);
    }
}
