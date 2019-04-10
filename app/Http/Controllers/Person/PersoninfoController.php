<?php

namespace App\Http\Controllers\Person;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;

class PersoninfoController extends Controller
{
    //
    public function __construct()
    {

        $this->middleware('auth:api', ['except' => ['login','refresh']]);
    }

    function changepassword(Request $request)
    {
        $oldpassword = '';
        $newpassword = '';
        $decode = $request->attributes->get('decode');
        if ($decode['oldpassword'] && $decode['newpassword'])
        {
            $oldpassword = $decode['password'];
            $newpassword = $decode['newpassword'];
        }
        else
            return response()->json(['error'=>'Bad Request'],400);
        try{
            $us = User::find(auth('api')->user()->id);
            if ($us->password === $oldpassword )
            {
                $us->password = Hash::make($newpassword);
                $us->save();
            }
            else
            {
                throw new \Exception('password wrong');
            }
        }catch (\Exception $e)
        {
            return response()->json(["error"=>$e->getMessage()],400);
        }
        return response()->json(["success"=>'change complete'],200);
    }

    function changename(Request $request)
    {
        $new_name = $request->input('new_name');
        if ($new_name === null)
            return response()->json(['error'=>'Bad Request'],400);
        try{
            $us = User::find(auth('api')->user()->id);
            $us->name = $new_name;
            $us->save();
        }catch (\Exception $e)
        {
            return response()->json(["error"=>$e->getMessage()],400);
        }
        return response()->json(["success"=>'change complete'],200);
    }


}
