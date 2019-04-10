<?php

namespace App\Http\Middleware;

use App\Rules\OnlywordnumberRule;
use Closure;
use Validator;

use \App\Rules\OnlywordnumberRule as name;
use App\Rules\OnlywordnumberRule as own;
use App\Rules\FilenameRule as filename;


class DecodeInput
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
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'title' => 'unique:posts|max:255|nullable|bail',
            'body' => 'nullable|bail',
            'name' => ['nullable',new name(),'bail'],
            'newname' => ['nullable',new name(),'bail'],
            'email'=> 'nullable|bail|email',
         //   'fid' => ['nullable','string','max:255','bail'],
           // 'secret'
        ]);
        if ($validator->fails())
        {
            return response()->json(['error'=>'Bad Request'],400);
        }
        $secret = $request->input('secret');
      //  var_dump($secret);
        if ($secret)
        {
            $privkey = file_get_contents('K:\wndowsservices\ypan\mytest\keys\private');
            if (!$privkey)
            {
                return response()->json(['error'=>'Service not ready'],502);
            }
            $decode = array_map( function ($v) use ($privkey){
                openssl_private_decrypt(base64_decode($v), $decrypted, $privkey);
                return $decrypted;
            },$secret);
          //  var_dump($decode);
            if (in_array('',$decode))
            {
                return response()->json(['error'=>'Bad Request'],400);
            }
           // var_dump($decode);
        }

        $request->attributes->add(compact('decode'));

        return $next($request);
    }

}
