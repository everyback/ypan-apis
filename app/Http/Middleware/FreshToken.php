<?php

namespace App\Http\Middleware;

use Closure;

class FreshToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $token = $request->header('Authorization');
        if ($token)
        {
            $exp = auth('api')->payload()->get('exp');
            //dd($exp);
            if ($exp - time() <60)
            {
                $newToken = auth('api')->refresh();
                return $next($request)->header('Authorization',$newToken);
               // $newToken = JWTAuth::parseToken()->refresh();
            }
        }

        return $next($request);
    }
}
