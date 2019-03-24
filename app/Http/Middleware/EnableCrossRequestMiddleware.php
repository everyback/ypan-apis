<?php

namespace App\Http\Middleware;

use Closure;

class EnableCrossRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
/*    public function handle($request, Closure $next)
    {
        return $next($request);
    }*/
    public function handle($request, Closure $next)
    {
/*        $response = $next($request);
        $origin = $request->server('HTTP_ORIGIN') ? $request->server('HTTP_ORIGIN') : '';
       // var_dump($origin);
        $allow_origin = [
            'http://localhost:8082',
            'http://127.0.0.1:8082',
        ];
        if (in_array($origin, $allow_origin)) {
            $response->header('Access-Control-Allow-Origin', $origin);
           // $response->header('Access-Control-Allow-Origin:http://127.0.0.1:8082');
            $response->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Cookie, X-CSRF-TOKEN, Accept, Authorization, X-XSRF-TOKEN');
            $response->header('Access-Control-Expose-Headers', 'Authorization, authenticated');
            $response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS');
            $response->header('Access-Control-Allow-Credentials', 'true');
            return $response;
        }*/

       /* header("Access-Control-Allow-Origin: http://127.0.0.1:8082");
        $headers = [
            'Access-Control-Allow-Methods'=> 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Headers'=> 'Content-Type, X-Auth-Token, Origin'
        ];
        $response = $next($request);
        foreach($headers as $key => $value)
            $response->header($key, $value);
        return $response;*/
        header('Access-Control-Allow-Origin: http://127.0.0.1:8082');
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS");
        header('Content-Type:application/json; charset=utf-8');
        header("Access-Control-Expose-Headers: *");
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept,Authorization');
        return $next($request);
        //return response();
    }
}
