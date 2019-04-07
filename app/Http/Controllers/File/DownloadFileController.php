<?php

namespace App\Http\Controllers\File;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use MongoGrid\MongoGrid as Grid;
use zip\zip;
use SendFile\SendFile;
use App\Model\FileDownloadPathModel as DownloadPath;
use App\Model\UserFileModel as UserFile;
use DB;
use App\Model\FolderModel as Folder;
use myglobal\myglobal;

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

    function createdownloadpath(Request $request)//完整dir地址
    {
        $dir = $request->attributes->get('dirarray');
        $user_id = auth('api')->user()->id;
        $user_root = auth('api')->user()->user_root;
        $filename = $request->input('filename');
        $foldername = $request->input('foldername');
        $fid = $this->searchFolder($dir,$user_root,$user_id);
        $user_id = auth('api')->user()->id;
        if (!$fid)
            return response()->json(['error'=>'no such folder'],404);
        $res = UserFile::where(
            [
                [ 'folder_id',$fid],
                ['updater_id',$user_id],
                ['deleted',0],
                //  'role'=>0,
            ]
        )->whereIn('file_name',$filename)->get();
        if (/*count($res) !== count($filename) &&*/ count($res) === 0 )
        {
            return response()->json(['error'=>'param false'],400);
        }
        if (count($res) === 1 )
        {
            $path = myglobal::makePath(40);
            $file_oid = $res[0]->file_oid;
            $file_name = $res[0]->file_name;
            $size = $res[0]->file_size;
            \DB::beginTransaction();
            try{
                $res = DownloadPath::create(
                    [
                        'file_oid'=>$file_oid,
                        'file_name'=>json_encode([$file_name]),
                        'file_download_path'=>$path,
                        'user_id'=>$user_id,
                        'file_size'=>$size,
                        'active_time'=>1,
                        'created_at'=>date('Y-m-d H:i:s'),
                    ]
                );
                \DB::commit();
                //                var_dump($file_name);
                //                die;
            }catch (\Exception $e)
            {
                \DB::rollBack();
                return response()->json(['error'=>$e->getMessage()],403);
            }

            $path = $_SERVER["HTTP_HOST"].'/api/download/'.$path;
            return response()->json(['success'=>['path'=>$path,"name"=>$file_name]],200);
        }
        else
        {
            $all = $res->all();
            $fids = [];
            $filename = [];
            array_map(function ($value)use (&$fids,&$filename){
                   // dump($value->file_oid);
                array_push($fids,$value->file_oid);
                array_push($filename,$value->file_name);
            },$all);
                dd($fids,$filename);
            $path = myglobal::makePath(40);
            $file_oid = $res[0]->file_oid;
            $file_name = $res[0]->file_name."等文件";
            $size = $res[0]->file_size;
            \DB::beginTransaction();
            try{
                $res = DownloadPath::create(
                    [
                        'file_oid'=>$file_oid,
                        'file_name'=>json_encode([$file_name]),
                        'file_download_path'=>$path,
                        'user_id'=>$user_id,
                        'file_size'=>$size,
                        'active_time'=>1,
                        'created_at'=>date('Y-m-d H:i:s'),
                    ]
                );
                \DB::commit();
                //                var_dump($file_name);
                //                die;
            }catch (\Exception $e)
            {
                \DB::rollBack();
                return response()->json(['error'=>$e->getMessage()],403);
            }

            $path = $_SERVER["HTTP_HOST"].'/api/download/'.$path;
            return response()->json(['success'=>['path'=>$path,"name"=>$file_name]],200);
        }
    }

    protected function searchFolder(array $dir,$user_root,$user_id,$create = false)
    {
        $point_id = $user_root;//先定位到user_root 的fid;
        foreach ( $dir as $value )
        {
            $last = $point_id;
            $point_id = Folder::where([['user_id',$user_id],['belong',$point_id],['folder_name',$value],['deleted','0']])->value('fid');//输出当前f_name的fid
            if ($point_id === null)
            {
                if($create)
                {
                    try{
                        $point_id = Folder::insertGetId([
                            'belong'=>$last,
                            //'phonenumber'=>$phonenumber,
                            'folder_name'=>$value,
                            'deleted'=>0,
                            "creater_id"=>$user_id,
                            "user_id"=>$user_id,
                            'updated_at'=>date('Y-m-d H:i:s'),
                            'created_at'=>date('Y-m-d H:i:s'),
                        ]);
                    }catch (\Exception $e)
                    {
                        return false;
                    }
                }
                else
                {
                    break;
                }

            }
        }
        return $point_id === null || $point_id === '' ? false:$point_id;
    }

}
