<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\FolderModel as Folder;
use App\Model\UserFileModel as UserFile;
use App\Model\ShareModel as Share;
use myglobal\myglobal;
use App\Model\share_count as ShareCount;
use App\Events\ShareEvent;
use App\Model\NDownloadPath as Path;
use App\User;
use DB;
//use Psy\Exception;

class ShareController extends Controller
{
    //
    function __construct()
    {
        $this->middleware('auth:api' ,['except' => ['showlists','refresh',"showshare","searchshare","createdownload"]]);
    }

    function createshare(Request $request)
    {
        $user_id = auth('api')->user()->id;
        $user_root = auth('api')->user()->user_root;
        $dir = $request->attributes->get('dirarray');
        $filename = $request->input('filename');
        $folder_name = $request->input('foldername');
        $active_time = $request->input('active_time') ? $request->input('active_time') : 7*24*24*60*60;
        $active_time = $active_time <= 0 ? -1:$active_time;
        $private = $request->input('private') ? $request->input('private'): false;
        $fid = $this->searchFolder($dir,$user_root,$user_id);
        $outfilemid = [];
        $outfid = null;
        $things = null;
        if (!$fid)
            return response()->json(['error'=>'no such folder'],404);
        $showname = "";
        $count = 0;
        if ($folder_name !== null &&count($folder_name) !== 0)
        {
            $outfid = Folder::where([["belong",$fid],["deleted",0],['user_id',$user_id]])->whereIn("folder_name",$folder_name)
                                                                                         ->pluck("folder_name","fid");
            if ($outfid === null)
                return response()->json(['error'=>'no such folder'],404);
            $temp = [];
            foreach( $outfid as $key=>$value)
                array_push($temp,$key);
            $showname = $outfid[$temp[0]];
            $outfid = $temp;
            $count +=count($outfid);
        }

        if ($filename !== null &&count($filename) !== 0)//代表有值
        {
            $outfilemid = UserFile::where([["folder_id",$fid],["deleted",0],['updater_id',$user_id]])->whereIn("file_name",$filename)
                                                                                                     ->pluck("file_name","mid");
            if ($outfilemid === null || count($outfilemid) !== count($filename))
                return response()->json(['error'=>'no such file'],404);
            $temp = [];
            foreach( $outfilemid as $key=>$value)
            {
                array_push($temp,$key);
            }
            if ($showname === '')
                $showname = $outfilemid[$temp[0]];
            $outfilemid = $temp;
            $count +=count($outfilemid);
        }

        if ($outfilemid === null && $outfid === null )
        {
            return response()->json(['error'=>'no such folder or file'],404);
        }
        $things = ['folder'=>$outfid,'file'=>$outfilemid];
        $path = myglobal::makePath(20);
        $code = $private ? strtolower(myglobal::makePath(4)) : "";
        try
        {
            $getid = Share::create([
                'share_thing'=> $things,
                'share_path'=>$path,
                'user_id'=>$user_id,
                "share_folders"=>$outfid,
                "share_files"=>$outfilemid,
                'code'=>$code,
                "active_time"=> $active_time,
                'private' => $private,
                'sum'=>$count,
                "show_name"=>$showname,
                'created_at'=>date('Y-m-d H:i:s'),
            ]);

            $get2 = ShareCount::create([
                'share_path'=>$path,
            ]);


        }catch (\Exception $e)
        {
            return response()->json(['error'=>$e->getMessage()],403);
        }
        $share = $_SERVER["HTTP_HOST"].'/api/share/link/'.$path;
        if ($private === false)
            return response()->json(['success'=>['path'=>$share]]);
        else
            return response()->json(['success'=>['path'=>$share,'code'=>$code]]);
    }

    function showshare($sharepath = null,Request $request)//展示单个share具体的内容
    {
        //dd("dsdsadasdasd");
        if ($sharepath === null || $sharepath === "all")//将错误的重新定向到share列表去
        {
            $this->showalllists($request);
        }
        $dir = $request->attributes->get('dirarray');
        $code = $request->input('code') === null ? '': $request->input('code');
        $user_id = auth('api')->user();
        $user_id = $user_id === null ? -1:$user_id->id;
        try{
            $get = $this->getsharecollection($sharepath,$code,$user_id);

        }catch (\Exception $e)
        {
            return response()->json(['error'=>$e->getMessage()],403);
        }

        $files = $get->share_files;
        $folders = $get->share_folders;

        if ( !is_array($dir) || count($dir) === 0)//没有指定dir就是显示分享的基础连接
        {
            event(new ShareEvent($user_id, \Request::getClientIp(),'read',$sharepath, time()));//是否有阅读
            $ret = ($this->pagecreatebyid($files,$folders,50));
            return response()->json(['success'=>['data'=>$ret]],200);
        }
        else{//指定dir就是该连接下面的文件树结构，直接访问分享用户文件夹
            if(count($folders) === 0)
            {
                return response()->json(['error'=>'no such folder'],404);
            }
            $pagesize = $request->input("pagesize") !== null ? $request->input("pagesize"):20  ;
            $page = $request->input("page") !== null ?  $request->input("pagesize"): 1 ;
            $fid = $this->searchShareFileFid($dir ,$folders);
            $rets = $this->pagecreatebyfolder($fid,$pagesize,$page);
            return response()->json(['success'=>['data'=>$rets]],200);
        }



    }

    function showalllists(Request $request)
    {
        $pagesize = $request->input('pagesize') !== null ? $request->input('pagesize') :20 ;
        $page = $request->input('page') !== null ? $request->input('page') :1 ;
        try{
            $get = Share::where([['invalidation',0],['private',0]])->limit($pagesize)->offset($pagesize*($page -1))->get();

        }catch (\Exception $e)
        {
            return response()->json(['error'=>$e->getMessage()],403);
        }
        return $get;
    }

    function showUserlists(Request $request)
    {
        $user_id = auth('api')->user()->id;
        $pagesize = $request->input('pagesize') !== null ? $request->input('pagesize') :20 ;
        $page = $request->input('page') !== null ? $request->input('page') :1 ;
        try{
            $get = Share::where([['invalidation',0],['user_id',$user_id]])->limit($pagesize)->offset($pagesize*($page -1))->get();

        }catch (\Exception $e)
        {
            return response()->json(['error'=>$e->getMessage()],403);
        }
        return $get;
    }


    function deleteshare(Request $request)
    {
        $user_id = auth('api')->user()->id;
        $sharepath = $request->input('share_path');
        if (!is_array($sharepath))
        {
            try{
                $get = Share::where([['share_path',$sharepath],['invalidation',0],['user_id',$user_id]])->update(['invalidation',1]);

            }catch (\Exception $e)
            {
                return response()->json(['error'=>$e->getMessage()],403);
            }
        }
        else
        {
            try{
                $get = Share::where([['invalidation',0],['user_id',$user_id]])->whereIn('share_path',$sharepath)->update(['invalidation',1]);

            }catch (\Exception $e)
            {
                return response()->json(['error'=>$e->getMessage()],403);
            }
            return response()->json(['success'=>'cancel complete'],403);
        }
    }

    function searchshare(Request $request)
    {
        $pagesize = $request->input('pagesize') !== null ? $request->input('pagesize') :20 ;
        $page = $request->input('page') !== null ? $request->input('page') :1 ;
        $search = $request->input('sraech');
        try{
            $get = Share::where([['invalidation',0],['private',0]])->where('showname', 'like', "%.$search.%")->limit($pagesize)->offset($pagesize*($page -1))->get();

        }catch (\Exception $e)
        {
            return response()->json(['error'=>$e->getMessage()],403);
        }
        return $get;
    }

    function searchusershare(Request $request)
    {
        $pagesize = $request->input('pagesize') !== null ? $request->input('pagesize') :20 ;
        $page = $request->input('page') !== null ? $request->input('page') :1;
        $user_id = auth('api')->user()->id;
        $search = $request->input('sraech');
        try{
            $get = Share::where([['invalidation',0],['user_id',$user_id]])->where('showname', 'like', "%.$search.%")->limit($pagesize)->offset($pagesize*($page -1))->get();

        }catch (\Exception $e)
        {
            return response()->json(['error'=>$e->getMessage()],403);
        }
        return $get;
    }


    function saveto(Request $request)
    {
        $user_root = auth('api')->user()->user_root;
        $user_id = auth('api')->user()->id;
        $foldername = $request->input('foldername') === null ? []:$request->input('foldername');
        $sharepath = $request->input('sharepath');
        $filename = $request->input('filename') === null ? []:$request->input('filename');
        $dir_to = $request->attributes->get('dir_to');
        if (!$sharepath || !isset($dir_to) )
            return response()->json(['error'=>'Bad Request'],400);

        $code = $request->input('code') === null ? '': $request->input('code');
        try{
            $get = $this->getsharecollection($sharepath,$code,$user_id);//获取整个share的集合

        }catch (\Exception $e)
        {
            return response()->json(['error'=>$e->getMessage()],403);
        }
        $folders = $get->share_folders;
        $files = $get->share_files;

        if (count($dir_to) === 0 )
            $newfid = $user_root;
        else
            $newfid = $this->searchFolder($request->attributes->get('dir_to'),$user_root,$user_id );
        if (count($foldername) !== 0)
        {
            $foldersum = Folder::where([['deleted','0']])->whereIn('folder_name',$foldername)->whereIn('fid',$folders)->count();//测文件夹数量
            //dd($foldersum);
            if ($foldersum !== count($foldername))
            {
                return response()->json(['error'=>'sum folder false ,please fresh it and reselect'],403);
            }
            $cover = Folder::where([['user_id',$user_id],['belong',$newfid],['deleted','0']])->whereIn('folder_name',$foldername)->exists();//测新位置重复
            if ($cover === true )
            {
                return response()->json(['error'=>'have same name folder'],403);
            }

            try{//先检查文件大小
                $res = UserFile::where(
                    [
                        ['deleted',0],
                    ]
                )->whereIn('file_name',$filename)->whereIn('mid',$files)->pluck('file_size')->toArray();
                if (count($res) !== count($filename))
                    throw new \Exception('no such files');
                $sum = array_sum($res);
                $userspace = auth('api')->user()->space_used;
                $allspace = auth('api')->user()->space;
                if ($sum + $userspace > $allspace)
                    throw new \Exception('no enough space');
                $res = UserFile::where(
                    [
                        [ 'folder_id',$newfid],
                        ['updater_id',$user_id],
                        ['deleted',0],
                        //  'role'=>0,
                    ]
                )->whereIn('file_name',$filename)->exists();
                // if ($res !== 0)
                if ($res === true)
                    throw new \Exception('have same files');
                $str = myglobal::setmult(count($filename));
                $str2 = myglobal::setmult(count($files));
                $datas = array_merge([$newfid,$user_id],$filename,$files);
                \DB::beginTransaction();
                try{            //开始写入文件
                    $res2 = DB::insert("INSERT INTO user_files 
    (folder_id,file_oid,file_name,file_type,updater_id,file_size,deleted,created_at,updated_at)
    SELECT ?,file_oid,file_name,file_type,?,file_size,deleted,created_at,updated_at 
    FROM user_files WHERE(deleted=0 ) AND file_name IN ($str) AND mid IN ($str2)",
                        $datas);
                    if (!$res2)
                    {
                        throw new \Exception('unknow error');
                    }
//                    dd($sum);
                    $res3 = User::where('id',$user_id)->update(['space_used'=>$sum+$userspace]);
                    DB::commit();
                }catch (\Exception $e)
                {
                    DB::rollBack();
                    throw new \Exception($e->getMessage());
                }
            }catch (\Exception $e)
            {
                return response()->json(['error'=>$e->getMessage()],403);
            }
        }

        if (count($foldername) !== 0)
        {
            $str = myglobal::setmult(count($foldername));
            $str2 = myglobal::setmult(count($folders));
            $datas = array_merge($foldername,$folders);
            $fid = Folder::whereIn('folder_name',$foldername)->whereIn('fid',$folders)->value('belong');
            $res = \DB::select("with recursive mys  as(
                  select fid,belong
                  from folders
                  where
                    ( deleted='0') AND folder_name IN ($str) AND fid IN ($str2)
                  union all
                  select
                    f.fid,
                    f.belong
                  from folders f inner join mys m on   m.fid = f.belong )
                select * from mys;",
                $datas);
            $res = array_merge([(object)['fid'=>$fid,'belong'=>-1]],$res);//拿到正式的文件夹树平面
            if(count($res) > 100)
            {
                return response()->json(['error'=>'too much, gun'],403);
            }
            $tree = myglobal::arrayToTree($res,$fid);//文件夹树生成
            //深度优先吧。。。。。
          //  dd($user_id);
            \DB::beginTransaction();
            try{
                $allsize = $this->setfolders($tree,$newfid,$user_id);
                //var_dump($get);
                $userspace = auth('api')->user()->space_used;
                $allspace = auth('api')->user()->space;
                //dd($allsize);
                if ($userspace + $allsize < $allspace)
                    $res2 = User::where('id',$user_id)->update(['space_used'=>$userspace + $allsize]);
                else
                    throw new \Exception("not enough space");
                event(new ShareEvent($user_id, \Request::getClientIp(),'resave',$sharepath, time()));//被转存
                \DB::commit();
            }catch (\Exception $e)
            {
                \DB::rollBack();
                return  response()->json(['error'=>$e->getMessage()],403);
            }
        }

        return  response()->json(['success'=>'copy completed']);

    }

    function createdownload(Request $request)
    {
        $dir = $request->attributes->get('dirarray');
        $user_id = auth('api')->user() === null ? -1:auth('api')->user()->id ;
//        $user_root = auth('api')->user()->user_root;
        $sharepath = $request->input('sharepath');
        $filename = $request->input('filename') === null ? [] : $request->input('filename');
        $foldername = $request->input('foldername') === null ? [] : $request->input('foldername');

        $code = $request->input('code') === null ? '': $request->input('code');
        try{
            $get = $this->getsharecollection($sharepath,$code,$user_id);//获取整个share的集合

        }catch (\Exception $e)
        {
            return response()->json(['error'=>$e->getMessage()],403);
        }
        $folders = $get->share_folders;
        $files = $get->share_files;
        $getfiles = [];
        $getfolders = [];
        $dircount = count($dir);
        if ($dircount !== 0)//代表选择的不是表层的元素
        {
            $fid = $this->searchShareFileFid($dir ,$folders);
        }
        else//代表选择的是表层的元素
        {
            $fid = -1;
        }

        if (!$fid)
            return response()->json(['error'=>'no such folder'],404);
        $count = 0 ;

        if ($foldername !== null &&count($foldername) !== 0)//代表有值
        {
            if ($dircount !== 0)
            {
                $getfolders = Folder::where(
                    [
                        [ 'belong',$fid],
                        ['deleted',0],
                        //  'role'=>0,
                    ]
                )->whereIn('folder_name',$foldername)->get(["fid","folder_name"]);
            }
            else
            {
                $getfolders = Folder::where(
                    [
                        ['deleted',0],
                        //  'role'=>0,
                    ]
                )->whereIn('folder_name',$foldername)->whereIn('fid',$folders)->get(["fid","folder_name"]);
            }
           // dd($getfolders);
            if ($getfolders === null)
                return response()->json(['error'=>'no such folder'],404);
            $temp = [];
            $getfolders = $getfolders->all();
            foreach( $getfolders as $value)
                array_push($temp,$value->fid);
            $showname = $getfolders[0]->folder_name;
            $getfolders = $temp;
            //            dd($folders);
            $count +=count($getfolders);
        }

        if ($filename !== null &&count($filename) !== 0)//代表有值
        {
            if ($dircount !== 0)
            {
                $getfiles = UserFile::where([['folder_id', $fid],['deleted', 0], //                mid, folder_id, file_oid, , file_type, updater_id, file_size, deleted, created_at, updated_at
                        //  'role'=>0,
                    ])->whereIn('file_name', $filename)->get(["mid", "file_name"]);
            }
            else
            {
                $getfiles = UserFile::where([
                    ['deleted', 0], //                mid, folder_id, file_oid, , file_type, updater_id, file_size, deleted, created_at, updated_at
                    //  'role'=>0,
                ])->whereIn('file_name', $filename)->whereIn("mid",$files)->get(["mid", "file_name"]);
            }
           // dd($filename);
            if ($getfiles === null || count($getfiles) !== count($filename))
                return response()->json(['error'=>'no such file'],404);
            $temp = [];

            $getfiles = $getfiles->all();
            foreach( $getfiles as $value)
                array_push($temp,$value->mid);
            $showname = $getfiles[0]->file_name;

            if ($showname === '')
                $showname = $getfiles[$temp[0]];
            $getfiles = $temp;
            //            dd($files);
            $count +=count($getfiles);
        }

        if (($getfiles === null && $getfolders === null ) || $count !== count($filename) + count($foldername) )
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
                    'download_folders'=>$getfolders,
                    'download_files'=>$getfiles,
                    'sum'=>$count,
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
        event(new ShareEvent($user_id, \Request::getClientIp(),'download',$sharepath, time()));//是否有阅读
        $path = $_SERVER["HTTP_HOST"].'/api/download/'.$path;
        return response()->json(['success'=>['path'=>$path,"name"=>$show_name]],200);
    }


    protected function getsharecollection($sharepath,$code = '',$user_id = -1)
    {
        $get = Share::where([['share_path',$sharepath],['invalidation',0]])->first();//找到分享链接

        if ($get->code !== "" )//是否是加密链接
        {

            if (strtolower($code) !== $get->code && $user_id !== $get->user_id)
            {
                throw new \Exception('code error');
               // return response()->json(['error'=>['code error']],403);
            }
        }
        if ($get === null)
        {
            throw new \Exception('no such share or sharelink had out of time');
           // return response()->json(['error'=>['no such share or sharelink had out of time']],404);
        }
        if ($get->active_time === -1 && time() - $get->created_at > $get->active_time)//永久与超时
        {
            $get = Share::where([['share_path',$sharepath],['invalidation',0]])->update(['invalidation'=>1]);
            throw new \Exception('no such share or sharelink had out of time');
//            return response()->json(['error'=>['no such share or sharelink had out of time']]);
        }
        return $get;
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

    protected function searchShareFileFid(array $dir,array $fids)//$dir 不为0
    {
        $name = array_shift($dir);
        $point_id = Folder::where([['folder_name',$name],['deleted','0']])->whereIn('fid',$fids)->value('fid');//获取分享点fid

        foreach ( $dir as $value )
        {
            $last = $point_id;
            $point_id = Folder::where([['belong',$point_id],['folder_name',$value],['deleted','0']])->value('fid');//输出当前f_name的fid
            if ($point_id === null)
            {
                return false;
            }
        }
        return $point_id === null || $point_id === '' ? false:$point_id;
    }

    protected function setfolders($tree,$fid,$user_id = -1)
    {
        //  $fid =
        //  dump($fid);
        $size  = 0;
        foreach ($tree as $value)
        {
            // dump($value["fid"]);
            //复制文件夹
            $res2 = \DB::insert("INSERT INTO folders 
            ( belong, folder_name, creater_id, user_id, deleted, created_at, updated_at)
            SELECT  ?, folder_name, creater_id, ?, deleted, created_at, updated_at
            FROM folders WHERE(deleted=0 AND fid=? )",[$fid,$user_id,$value["fid"]]);
            //
            $nfid = \DB::select("SELECT LAST_INSERT_ID() l")[0]->l;
            //dump($value["child"]);

            $sizes= UserFile::where([["deleted",0],["folder_id",$value["fid"]]])->pluck("file_size")->toArray();
            $size += array_sum($sizes);
          //  dump($size);
            //复制文件
            $res2 = \DB::insert("INSERT INTO user_files 
            (folder_id,file_oid,file_name,file_type,updater_id,file_size,deleted,created_at,updated_at)
            SELECT ?,file_oid,file_name,file_type,?,file_size,deleted,created_at,updated_at 
            FROM user_files WHERE(deleted=0 AND folder_id=? ) ",[$nfid,$user_id,$value["fid"]]);
            //  dd($res2);
            if ($value["child"] !== 0)
            {
                $size += $this->setfolders($value["child"],$nfid,$user_id);
            }

        }
        return $size;
    }




    protected function pagecreatebyid( array $fileids,array $fids,int $pagesize,int $pageindex = 1)
    {
        $cfile = count($fileids);
        $cfolder = count($fids);
        $offset = $pagesize * ($pageindex - 1);
        if ($cfile === 0 &&$cfolder !== 0)// 只有文件夹
        {
            $folders = myglobal::setmult($cfolder);
            $datas = array_merge($fids,[$pagesize,$offset]);
            $res = \DB::select("SELECT folder_name name,created_at, updated_at ,0 AS 'isfile'  FROM folders f 
                      WHERE (deleted=0)  AND f.fid IN ($folders) limit ? OFFSET ?",
                $datas);
        }else if ($cfile !== 0 &&$cfolder === 0)// 只有文件
        {
            $files =  myglobal::setmult($cfile);
            $datas = array_merge($fileids,[$pagesize,$offset]);
            $res = \DB::select("SELECT file_name name,created_at ,updated_at ,1 AS 'isfile' FROM user_files
                      WHERE (deleted=0 ) AND mid IN ($files) limit ?  OFFSET ?",
                $datas);
        }
        else if ($cfile !== 0 &&$cfolder !== 0)//都有
        {
            $files =  myglobal::setmult($cfile);
            $folders = myglobal::setmult($cfolder);
            $datas = array_merge($fids,$fileids,[$pagesize,$offset]);
            $res = \DB::select("SELECT folder_name name,created_at, updated_at ,0 AS 'isfile'  FROM folders f 
                      WHERE (deleted=0)  AND f.fid IN ($folders)
                      UNION ALL SELECT file_name name,created_at ,updated_at ,1 AS 'isfile' FROM user_files 
                      WHERE (deleted=0 ) AND mid IN ($files) limit ? OFFSET ?",
                $datas);
        }
        else
        {
            return false;
        }

        return $res;
    }

    protected function pagecreatebyfolder (int $folderid,int $pagesize = 50,int $pageindex = 1)
    {
        $offset = $pagesize * ($pageindex - 1);
        $datas = [$folderid,$folderid,$pagesize,$offset];
        $res = \DB::select("SELECT folder_name name,created_at, updated_at ,0 AS 'isfile'  FROM folders f WHERE (deleted=0)  AND f.belong = ?
UNION ALL SELECT file_name name,created_at ,updated_at ,1 AS 'isfile' FROM user_files WHERE (deleted=0 ) AND folder_id = ? limit ?  OFFSET ?",
            $datas);
        if ($res === null)
        {
            return false;
        }
        else
        {
            return $res;
        }
    }

}




/*function createshare(Request $request)
{
    $user_id = auth('api')->user()->id;
    $user_root = auth('api')->user()->user_root;
    $dir = $request->attributes->get('dirarray');
    $filename = $request->input('filename');
    $folder_name = $request->input('folder_name');
    $active_time = $request->input('active_time') ? $request->input('active_time') : 7*24*24*60*60;
    $private = $request->input('private') ? $request->input('private'): false;
    $fid = $this->searchFolder($dir,$user_root,$user_id);
    $outfilemid = [];
    $outfid = null;
    $things = null;
    if (!$fid)
        return response()->json(['error'=>'no such folder'],404);
    $showname = "";
    $count = 0;
    if ($folder_name !== null &&count($folder_name) !== 0)
    {
        $outfid = Folder::where([["belong",$fid],["deleted",0],['user_id',$user_id]])->whereIn("folder_name",$folder_name)
                        ->get(["folder_name","fid"]);
        if ($outfid === null)
            return response()->json(['error'=>'no such folder'],404);
        $temp = [];

        foreach( $outfid as $value)
        {
            //$temp[] = [ "fid"=>$key,"folder_name"=>$value];
            array_push($temp,['name'=>$value->folder_name,'id'=>$value->fid]);
        }
        //dd($temp);
        $showname = $temp[0]['name'];
        $outfid = $temp;
        $count +=count($outfid);
    }

    if ($filename !== null &&count($filename) !== 0)//代表有值
    {
        $outfilemid = UserFile::where([["folder_id",$fid],["deleted",0],['updater_id',$user_id]])->whereIn("file_name",$filename)
                              ->get(["file_name","mid"]);
        if ($outfilemid === null || count($outfilemid) !== count($filename))
            return response()->json(['error'=>'no such file'],404);
        $temp = [];
        foreach( $outfilemid as $value)
        {
            array_push($temp,['name'=>$value->file_name,'id'=>$value->mid]);
            // array_push($temp,[$key,$value]);
        }
        if ($showname === '')
            $showname = $temp[0]['name'];
        //dd($showname);
        $outfilemid = $temp;
        $count +=count($outfilemid);
    }

    if ($outfilemid === null && $outfid === null )
    {
        return response()->json(['error'=>'no such folder or file'],404);
    }
    $things = ['folder'=>$outfid,'file'=>$outfilemid];
    $path = myglobal::makePath(20);
    $code = $private ? strtolower(myglobal::makePath(4)) : "";
    try
    {
        $getid = Share::create([
            'share_thing'=> $things,
            'share_path'=>$path,
            'user_id'=>$user_id,
            "share_folders"=>$outfid,
            "share_files"=>$outfilemid,
            'code'=>$code,
            "active_time"=> $active_time,
            'private' => $private,
            'sum'=>$count,
            "show_name"=>$showname,
            'created_at'=>date('Y-m-d H:i:s'),
        ]);
    }catch (\Exception $e)
    {
        return response()->json(['error'=>$e->getMessage()],403);
    }
    $share = $_SERVER["HTTP_HOST"].'/api/share/link/'.$path;
    if ($private === false)
        return response()->json(['success'=>['path'=>$share]]);
    else
        return response()->json(['success'=>['path'=>$share,'code'=>$code]]);
}*/