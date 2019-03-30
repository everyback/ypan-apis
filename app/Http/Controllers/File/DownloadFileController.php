<?php

namespace App\Http\Controllers\File;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use MongoGrid\MongoGrid as Grid;
use zip\zip;
use SendFile\SendFile;
use App\Model\FileDownloadPathModel as DownloadPath;

class DownloadFileController extends Controller
{
    //

    function download($downloadpath = null,Request $request, zip $zip,Grid $grid,SendFile $SendFile)//完整dir地址
    {
        // $user_id = auth('api')->user()->id;

        if ($downloadpath === null)
        {
            return response()->json(['error'=>'no such path'],404);
        }
        else
        {
            $downloadpath = strtolower($downloadpath);
        }

        $alivetime = 60*60*24;  //1天

        $res = DownloadPath::where(
            [
                ['file_download_path',$downloadpath],
                ['created_at','>',time()-$alivetime],
                //  'role'=>0,
            ]
        )->first();
        if (!$res)
        {
            return response()->json(['error'=>'file not found ,maybe outof alive time'],404);
        }
        //$filename = json_decode($res['file_name'],true);
        $filename = json_decode($res['file_name'],true)[0];
        $file_oid = $res['file_oid'];
        $destination = fopen('php://temp', 'w+b');
        $size = $res['file_size'];
        //dd($res[0]->file_oid);
       // dd(sizeof($destination));
       // getfile()
        $destination = $grid->getfile($file_oid,$destination);

        $SendFile->singlefile($destination,$size,$filename);



    }
}
