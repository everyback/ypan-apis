<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Model\UserFileModel;

class test extends Controller
{
    //


    function getrandpath()
    {
       /* if (rand(1,100) > 80)
        {
            return response()->json(['data'=>[
                [
                    'folder_name'=>'aaa',
                ],
                [
                    'folder_name'=>'bbb',
                ],
                [
                    'folder_name'=>'ccc',
                ],
                [
                    'folder_name'=>'ddd',
                ],
                [
                    'folder_name'=>'eee',
                ],
                [
                    'folder_name'=>'fff',
                ],
                [
                    'folder_name'=>'ggg',
                ],
                ]
            ]);
        }
        else
        {
            return response()->json(['data'=>['empty'
            ]]);
        }*/
       $get = UserFileModel::where("updater_id",12)->pluck('mid','file_name');
       dump($get);
        return response()->json($get);
    }
}
