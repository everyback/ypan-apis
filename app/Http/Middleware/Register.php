<?php

namespace App\Http\Middleware;

use Closure;
use \App\Rules\OnlywordnumberRule as name;
use App\Rules\OnlywordnumberRule as own;
use App\Rules\PhoneNumberRule as phone;
use Validator;

class Register
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
            'name' => [new name(),'bail'],
            'email'=> 'bail|email',
         //   'invite'=>['bail','own',new own()],
          //  'phonenumber'=>['bail','own',new phone()],
            // 'secret'
        ]);
        if ($validator->fails())
        {
            return response()->json(['error'=>'Bad Request'],400);
        }
        return $next($request);
    }
}
