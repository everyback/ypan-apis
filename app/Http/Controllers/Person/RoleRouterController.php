<?php

namespace App\Http\Controllers\Person;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RoleRouterController extends Controller
{
    //
    function __construct()
    {
        $this->middleware('auth:api');
    }


    function getrouter()
    {
        $role = auth('api')->user()->role;
        if ($role === 1)
        {
            $routes = [
                [
                    'path'=> '/manage',
                    'component'=> 'manage',
                    'children'=>[
                        [
                            'path'=>'/',
                            'alias'=>'managehome',
                            'name'=> 'managehome',
                            'component'=> 'managehome',
                        ],
                        /*[
                            'path'=>'steptwo',
                            'name'=> 'steptwo',
                            'component'=> 'forgetsteptwo',
                        ],
                        [
                            'path'=>'stepthree',
                            'name'=> 'stepthree',
                            'component'=> 'forgetstepthree',
                        ],*/
                    ],
                ]
            ];
            $items = [
              ["name"=>"manage","path"=>"/manage"],
            ];
            return response()->json(["success"=>["routes"=>$routes,"items"=>$items]]);
        }
        return response()->json(["error"=>"no such role"],406);
    }
}
