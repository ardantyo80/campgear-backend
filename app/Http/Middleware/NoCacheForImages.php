<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NoCacheForImages
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Jika request adalah gambar di folder images
        if (str_contains($request->path(), 'images/')) {
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
        }
        
        return $response;
    }
}