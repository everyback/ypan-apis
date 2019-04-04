<?php

namespace App\Http\Controllers\Person;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use App\Model\FolderModel as Folder;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    //
    function samename(Request $request)
    {
        if ($data = $request->input('name'))
            if ( $this->searchsame('name',$data))
                return response()->json(['message'=>'have same name'],200);
            else
                return response()->json(['message'=>'no same name'],200);
        return response()->json(['error'=>'Bad Request'],400);
    }

    function sameemail(Request $request)
    {
        if ($data = $request->input('email'))
            if ( $this->searchsame('email',$data))
                return response()->json(['message'=>'have same email'],200);
            else
                return response()->json(['message'=>'no same email'],200);
        return response()->json(['error'=>'Bad Request'],400);
    }

    function register(Request $request)
    {
        $email = $request->input('email');
        $name = $request->input('name');
        $decode = $decode = $request->attributes->get('decode');
        $password = $decode['password'];
        //$phonenumber = $decode['phonenumber'];
        //$invite =  $decode['invite'];
        $data = [$email,$name];
        if ($this->searchsame(['name','email'],$data))
            return response()->json(['error'=>'no....'],403);


/*        var_dump($getid);
        die;*/
        \DB::beginTransaction();
        try
        {
            $getid = User::insertGetId([
                'name'=>$name,
                'email'=>$email,
                'password'=>Hash::make($password),
                //'phonenumber'=>$phonenumber,
                'space'=>10*1024*1024*1024,
                'role'=>0,
                'updated_at'=>date('Y-m-d H:i:s'),
                'created_at'=>date('Y-m-d H:i:s'),
            ]);
            $fid = Folder::insertGetId(
                [
                    'belong'=>1,
                    'folder_name'=>$getid,
                    'creater_id'=>$getid,
                    'user_id'=>$getid,
                    'updated_at'=>date('Y-m-d H:i:s'),
                    'created_at'=>date('Y-m-d H:i:s'),
                  //  'role'=>0,
                ]
            );
            $gos = User::where('id',$getid)->update(['user_root'=>$fid]);

            \DB::commit();
        }catch (\Exception $e)
        {
            \DB::rollBack();
            dump($e);
            return response()->json(['failed'=>'Bad Request'],400);
        }

       // 'user_root'=> $getid,

        if ($gos)
            return response()->json(['success'=>'registed'],200);
        else
            return response()->json(['error'=>'Unknow error'],500);
    }


    protected function searchsame($name,$data)
    {
        $flag = false;
        if (gettype($name) === 'string')
        {
            $flag = User::where($name, $data)->exists();
        }
        elseif (gettype($name) === 'array')
        {
            $flag = User::where($name[0], $data[0])->orWhere($name[1], $data[1])->exists();

        }
        return $flag;
       // return response()->json(['error'=>'unknow error'],500);
    }

}
