<?php

namespace App\Http\Middleware;

use Closure;
use Validator;

class DownloadFileMiddleware
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
        $validator = Validator::make($request->all(), [
            'downloadpath'=>['string','alpha_num','bail','nullable'],
        ]);
        if ($validator->fails())
        {
            return response()->json(['error'=>'Bad Request'],400);
        }

        $dirarray =  $this->getdirarr($request->input('dir'));
        if ($request->input('dir_to'))
        {
            $dir_to = $this->getdirarr($request->input('dir_to'));
            $request->attributes->add(compact('dir_to'));
        }
        $request->attributes->add(compact('dirarray'));

        // $dirarray = array_values($dirarray);

        return $next($request);
    }

        protected function getdirarr($dir)
    {
        $dirarray = explode("/",$dir);
        $dirarray = array_values(array_filter($dirarray, function ($v)
        {
            return !($v === ''|| $v === '..') ;
        }));
        return $dirarray;
    }
}
