<?php

namespace App\Http\Middleware;

use Closure;
use App\Rules\FoldernameRule as foldername;
use App\Rules\FilenameRule as filename;
use Validator;
use myglobal\myglobal;
//use App\Rules\OnlywordnumberRule as onlywn;

class FileMiddleware
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
        ini_set('memory_limit', '4048M');
        $validator = Validator::make($request->all(), [
            'dir' => 'string|required',
            'dir_to'=>'string|nullable|bail',
            'folder_name' => [new foldername(),'nullable','bail'],
            'filename'=>[new filename(),'nullable','bail'],
            'md5'=>['string','alpha_num','bail','nullable'],
            'silce_sha1'=>['string','alpha_num','bail','nullable'],
            'pagesize'=>'numeric|nullable',
//            'index'=>'numeric|nullable',
            'new_filename'=>[new filename(),'nullable','bail'],
            'downloadpath'=>['string','alpha_num','bail','nullable'],
            'page'=>['numeric','min:1'],
            // 'secret'
        ]);
        if ($validator->fails())
        {
            return response()->json(['error'=>'Bad Request'],400);
        }

        $dirarray =   myglobal::getdirarr($request->input('dir'));
        if ($dirarray === false)
        {
            return response()->json(['error'=>'Bad Request'],400);
        }
        if ($request->input('dir_to'))
        {
            $dir_to = myglobal::getdirarr($request->input('dir_to'));
            if ($dir_to === false)
            {
                return response()->json(['error'=>'Bad Request'],400);
            }
            $request->attributes->add(compact('dir_to'));
        }
        $request->attributes->add(compact('dirarray'));

        // $dirarray = array_values($dirarray);

        return $next($request);
    }
}
