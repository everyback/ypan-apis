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
use App\Model\NDownloadPath as Path;

class DownloadFileController extends Controller
{
    //

    function createpath(Request $request)//完整dir地址
    {
        $dir = $request->attributes->get('dirarray');
        $user_id = auth('api')->user() === null ? -1:auth('api')->user()->id;
        $user_root = auth('api')->user()->user_root;
        $filename = $request->input('filename') === null ? [] : $request->input('filename');
        $foldername = $request->input('foldername') === null ? [] : $request->input('foldername');
        $fid = $this->searchFolder($dir,$user_root,$user_id);
       // $user_id = auth('api')->user()->id;
        if (!$fid)
            return response()->json(['error'=>'no such folder'],404);

        $files = [];
        $folders = [];
        $count = 0 ;

        if ($foldername !== null &&count($foldername) !== 0)//代表有值
        {
            $folders = Folder::where(
                [
                    [ 'belong',$fid],
                    ['user_id',$user_id],
                    ['deleted',0],
                ]
            )->whereIn('folder_name',$foldername)->get(["fid","folder_name"]);
            if ($folders === null)
                return response()->json(['error'=>'no such folder'],404);
            $temp = [];
            $folders = $folders->all();
            foreach( $folders as $value)
                array_push($temp,$value->fid);
            $showname = $folders[0]->folder_name;
            $folders = $temp;
//            dd($folders);
            $count +=count($folders);
        }

        if ($filename !== null &&count($filename) !== 0)//代表有值
        {
            $files = UserFile::where(
                [
                    [ 'folder_id',$fid],
                    ['updater_id',$user_id],
                    ['deleted',0],
                ]
            )->whereIn('file_name',$filename)->get(["mid","file_name"]);
            if ($files === null || count($files) !== count($filename))
                return response()->json(['error'=>'no such file'],404);
            $temp = [];

            $files = $files->all();
            foreach( $files as $value)
                array_push($temp,$value->mid);
            $showname = $files[0]->file_name;

            if ($showname === '')
                $showname = $files[$temp[0]];
            $files = $temp;
//            dd($files);
            $count +=count($files);
        }

        if (($files === null && $folders === null ) || $count !== count($filename) + count($foldername) )
        {
            return response()->json(['error'=>'no such folder or file'],404);
        }
       if ($count > 1 )
           $show_name = $showname."等文件";
        else
            $show_name = $showname;
      /*   {*/
            $path = myglobal::makePath(40);

//            $size = $res[0]->file_size;
            \DB::beginTransaction();
            try{
                $res = Path::create(
                    [
                        'show_name'=>$show_name,
                        'path'=>$path,
                        'user_id'=>$user_id,
                        'active_time'=>3*24*60*60,//3天
                        'download_thing'=>['folder'=>$folders,'file'=>$files],
                        'download_folders'=>$folders,
                        'download_files'=>$files,
                        'sum'=>$count,
                        'created_at'=>date('Y-m-d H:i:s'),
                    ]
                );
                \DB::commit();
            }catch (\Exception $e)
            {
                \DB::rollBack();
                return response()->json(['error'=>$e->getMessage()],403);
            }

            $path = $_SERVER["HTTP_HOST"].'/api/download/'.$path;
            return response()->json(['success'=>['path'=>$path,"name"=>$show_name]],200);
    }

    function download($downloadpath = null,Grid $grid,SendFile $SendFile,zip $zip)//完整dir地址
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

        $alivetime = 60*60*24*3;  //1天

        $res = Path::where(
            [
                ['path',$downloadpath],
                ['invalidation',0],
              //  ['created_at','>',time()-$alivetime],
                //  'role'=>0,
            ]
        )->first();
        if (!$res ||($res->created_at + $alivetime < time() ))
        {
            $res = Path::where(
                'path',$downloadpath
            )->update(['invalidation',1]);
            return response()->json(['error'=>'file not found ,maybe out of alive time'],404);
        }
        $fids = $res->download_folders;
        $mids = $res->download_files;
        //$filename = json_decode($res['file_name'],true);
        //$filename = json_decode($res['file_name'],true)[0];
       /* $file_oid = $res['file_oid'];
        $destination = fopen('php://temp', 'w+b');
        $size = $res['file_size'];*/
        //dd($res[0]->file_oid);
       // dd(sizeof($destination));
       // dd($mids);
       // getfile()
        if (count($mids)+count($fids) === 0)
        {
            $res = Path::where(
                'path',$downloadpath
            )->update(['invalidation',1]);
            return response()->json(['error'=>'file or folder not found ,maybe out of alive time'],404);
        }

        if (count($mids) === 1 && count($fids) === 0)//单个文件
        {
            $files = UserFile::where(
                [
                    ['mid',$mids[0]],
                    ['deleted',0],
                ]
            )->first();
            if ($files === null)
            {
                $res = Path::where(
                    'path',$downloadpath
                )->update(['invalidation',1]);
                return response()->json(['error'=>'no such file'],404);
            }
            $destination = fopen('php://temp', 'w+b');
            $destination = $grid->getfile($files['file_oid'],$destination);
            $SendFile->sethead($res->show_name);
/*            $zip->addFile(stream_get_contents($destination, -1, 0),iconv("utf-8","gbk",'folder/fiule.s'));
            flush();
            $zip->setComments(iconv("utf-8","gbk","一些注释"));
            $zip->file();*/
           /* $SendFile->zipfile($destination,$files['file_name']);
            flush();
            $SendFile->endzipsend();*/
            $SendFile->singlefile($destination,$files['file_size'],$files['file_name']);
        }
        else if (count($mids) >= 1 && count($fids) === 0)//多文件，无文件夹
        {
           // dd("dsfdsfdsgdsfg");
            $files = UserFile::where(
                [
                    ['deleted',0],
                ]
            )->whereIn('mid',$mids)->get();
            $files = $files->all();
          //  var_dump($files);
          //  die;
           $SendFile->sethead($res->show_name);
            dd($files);
            foreach ($files as $value)
            {
                $destination = fopen('php://temp', 'w+b');
                $destination = $grid->getfile($value->file_oid,$destination);
                //var_dump($destination);
                $SendFile->zipfile($destination,$value->file_name);
                $destination = null;

                //var_dump("aaaaa");
            }
            $SendFile->endzipsend();

        }
        else if (count($mids) == 0 && count($fids) >= 1)//只有文件夹，没有文件
        {
            $str = myglobal::setmult(count($fids));
            $datas = $fids;
            $fid = Folder::where('fid',$fids[0])->value('belong');
            $folders = \DB::select("with recursive mys  as(
                  select fid,belong
                  from folders
                  where
                    ( deleted='0') and fid in($str)
                  union all
                  select
                    f.fid,
                    f.belong
                  from folders f inner join mys m on   m.fid = f.belong )
                select * from mys;",
                $datas);
            $folders = array_merge([(object)['fid'=>$fid,'belong'=>-1]],$folders);//拿到正式的文件夹树平面
            $tree = myglobal::arrayToTree($folders,$fid);//文件夹树构建完成
            try
            {
                $files = $this->getfilesfromtree($tree,$fid);
               /// dd($files);
                $SendFile->sethead($res->show_name);
                foreach ($files as $value)
                {
                    $destination = fopen('php://temp', 'w+b');
                    $destination = $grid->getfile($value["file_oid"],$destination);//这里拿回来的是因为数组
                    //var_dump($destination);
                    $SendFile->zipfile($destination,$value["file_name"]);
                    $destination = null;
                    //var_dump("aaaaa");
                }
                $SendFile->endzipsend();

            }catch (\Exception $e)
           {
                return response()->json(['error'=>$e->getMessage()],403);
           }
        }
       else //啥都有    完美
        {

            $files = UserFile::where(
                [
                    ['deleted',0],
                ]
            )->whereIn('mid',$mids)->get();
            $files = $files->all();
            $str = myglobal::setmult(count($fids));
            $datas = $fids;
            $fid = Folder::where('fid',$fids[0])->value('belong');
            $folders = \DB::select("with recursive mys  as(
                  select fid,belong
                  from folders
                  where
                    ( deleted='0') and fid in($str)
                  union all
                  select
                    f.fid,
                    f.belong
                  from folders f inner join mys m on   m.fid = f.belong )
                select * from mys;",
                $datas);
            $folders = array_merge([(object)['fid'=>$fid,'belong'=>-1]],$folders);//拿到正式的文件夹树平面
            $tree = myglobal::arrayToTree($folders,$fid);//文件夹树构建完成
            try
            {
                $files = array_merge($this->getfilesfromtree($tree,$fid),$files);
               // dd($files);
                $SendFile->sethead($res->show_name);
                foreach ($files as $value)
                {
                    $destination = fopen('php://temp', 'w+b');
                    $destination = $grid->getfile($value["file_oid"],$destination);//这里拿回来的是因为数组
                    //var_dump($destination);
                    $SendFile->zipfile($destination,$value["file_name"]);
                    $destination = null;
                    //var_dump("aaaaa");
                }
                $SendFile->endzipsend();

            }catch (\Exception $e)
            {
                return response()->json(['error'=>$e->getMessage()],403);
            }



            //return response()->json(["error"=>'else if '],500);
        }
       // var_dump(count($mids));
       // return response()->json(["error"=>'unknow error '],500);

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

    protected function getfilesfromtree($tree, $fid, $path = '')
    {
        //  $fid =
        //  dump($fid);
        $size  = 0;
        $files = [];//['f_oid'=>,'filename'=>] 要有path
       // $path = '';
        foreach ($tree as $value)
        {
            $newpath = Folder::where([
                ["deleted",0],
                ["fid",$value["fid"]]
            ])->value('folder_name');
//            if ($path === '' )//指定文件path前缀
//            {
                $inpath = $path.$newpath.'/';
//            }
           /* else
            {
                $path = $newpath.'/';
            }*/

            $getfile = UserFile::where([
                ['folder_id',$value["fid"]],
                ["deleted",0],
                ])->get(["file_oid","file_name",'file_size']);//归属的文件
            if (count($getfile) >600)
            {
                throw new \Exception('too much files');
            }
            $getfile = $getfile->all();
           // dd($files);
            foreach ($getfile as $index =>$items)
            {
                    if ($items->file_size > 1024*1024*300)
                        throw new \Exception('file too large');
                    $files [] = ['file_oid'=>$items->file_oid,'file_name'=>$inpath.$items->file_name];
            }


            //  dd($res2);
            if ($value["child"] !== 0)
            {
                $files = array_merge($this->getfilesfromtree($value["child"],0,$inpath),$files);
            }

        }
        return $files;
    }


}



/*    function createdownloadpath(Request $request)//完整dir地址
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
        if (/*count($res) !== count($filename) && count($res) === 0 )
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
    }*/