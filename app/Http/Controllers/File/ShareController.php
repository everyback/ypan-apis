<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\FolderModel as Folder;
use App\Model\UserFileModel as UserFile;
use App\Model\ShareModel as Share;
use myglobal\myglobal;

class ShareController extends Controller
{
    //
    function __construct()
    {
        $this->middleware('auth:api' ,['except' => ['showlists','refresh',"showshare"]]);
    }

    function createshare(Request $request)
    {
        $user_id = auth('api')->user()->id;
        $user_root = auth('api')->user()->user_root;
        $dir = $request->attributes->get('dirarray');
        $filename = $request->input('filename');
        $folder_name = $request->input('folder_name');
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
        if ($sharepath === null || $sharepath === "all")//将错误的重新定向到share列表去
        {
            $this->showalllists($request);
        }
        $dir = $request->attributes->get('dirarray');
        $get = Share::where([['share_path',$sharepath],['invalidation',0]])->first();//找到分享链接

        if ($get->code !== "" )//是否是加密链接
        {
            $code = $request->input('code');
            if (strtolower($code) !== $get->code)
            {
                return response()->json(['error'=>['code error']],403);
            }
        }
        if ($get === null)
        {
            return response()->json(['error'=>['no such share or sharelink had out of time']],404);
        }
        if ($get->active_time === -1 && time() - $get->created_at > $get->active_time)//超时与永久
        {
            $get = Share::where([['share_path',$sharepath],['invalidation',0]])->update(['invalidation'=>1]);
            return response()->json(['error'=>['no such share or sharelink had out of time']]);
        }
        $files = $get->share_files;
        $folders = $get->share_folders;
        if ( !is_array($dir) || count($dir) === 0)//没有指定dir就是显示分享的基础连接
        {
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