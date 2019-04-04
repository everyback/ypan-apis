<?php

namespace App\Http\Controllers\File;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\FolderModel as Folder;
use App\Model\UserFileModel as UserFile;

class ShowFolderFrameworkController extends Controller
{
    //
    function __construct()
    {
        $this->middleware('auth:api');
    }

    function count(Request $request)
    {
        $user_root = auth('api')->user()->user_root;
        $user_id = auth('api')->user()->id;
        $folder_name = $request->input('folder_name');

        if (!$folder_name)
            return response()->json(['error'=>'Bad Request'],400);
        $fid = $this->searchFolder($request->attributes->get('dirarray'),$user_root,$user_id );
        if (!$fid)
            return response()->json(['error' => 'no such folder'],404);
        $fid = Folder::where([['user_id',$user_id],['belong',$fid],['folder_name',$folder_name],['deleted','0']])->value('fid');
        if (!$fid)
            return response()->json(['error' => 'no such folder'],404);
        $count = Folder::where([['user_id',$user_id],['belong',$fid],['deleted','0']])->count();
        $count += UserFile::where([['folder_id',$fid],['deleted',0]])->count();
        return response()->json(['success'=>$count],200);

    }

    function showpageinate(Request $request)//完全地址
    {
        $user_root = auth('api')->user()->user_root;
        $user_id = auth('api')->user()->id;
       // $folder_name = $request->input('folder_name');
        $pagesize = $request->input('pagesize') ? $request->input('pagesize') :5;
        $page = $request->input('page') ? $request->input('page') :1;

        $fid = $this->searchFolder($request->attributes->get('dirarray'),$user_root,$user_id );
        if (!$fid)
            return response()->json(['error' => 'no such folder'],404);
        $count = Folder::where([['user_id',$user_id],['belong',$fid],['deleted','0']])->count();//总文件夹数
        $over = $count%$pagesize;
        //dd($over);
        if ($count >= $pagesize*$page )//总量大于当前已读量并大于单页量
        {
            $folder = Folder::where([['user_id',$user_id],['belong',$fid],['deleted','0']])->orderBy('folder_name', 'asc')->limit($pagesize)->offset(($page - 1)*$pagesize)->get();
            return response()->json(['success'=>[$folder]],200);
        }
       else if ($count > $pagesize*($page-1)  && $count < $pagesize*$page   )//总量大于当前已读量但小于单页量，需要补充文件
       {
           //dd('me');
           $folder = Folder::where([['user_id',$user_id],['belong',$fid],['deleted','0']])->orderBy('folder_name', 'desc')->limit($over )->get();
           $files = UserFile::where([['folder_id',$fid],['deleted',0]])->limit($pagesize - $over )->get();
           //链接两个
           return response()->json(['success'=>[$folder,$files]],200);
          // return response()->json($files,$folder);
       }
       else if($count - $pagesize*$page < 0)//总量小于当前已读量
       {
          // dd('ofile');
           $oversfile = ($page-1)*$pagesize-$count;// 计算后多出的file 起点

           $files = UserFile::where([['folder_id',$fid],['deleted',0]])->limit($pagesize )->orderBy('file_name', 'asc')->offset($oversfile)->get();
         //  dd(($page -1)*$pagesize-$count );
           return response()->json(['success'=>[$files]],200);
       }

    }


    function showsearch(Request $request)
    {
        $user_root = auth('api')->user()->user_root;
        $user_id = auth('api')->user()->id;
        $key =  $request->input('key');
        /*$fid = $this->searchFolder($request->attributes->get('dirarray'),$user_root,$user_id );
        if (!$fid)
            return response()->json(['error' => 'no such folder'],404);*/
        return Folder::where([['user_id',$user_id],['deleted','0'],['folder_name','like','%'.$key.'%']])->orderBy('folder_name', 'desc')->get();
    }




    protected function searchFolder(array $dir,$user_root,$user_id)
    {
        $point_id = $user_root;//先定位到user_root 的fid;
        foreach ( $dir as $value )
        {
            $point_id = Folder::where([['user_id',$user_id],['belong',$point_id],['folder_name',$value],['deleted','0']])->value('fid');//输出当前f_name的fid
            if ($point_id === null)
            {
                break;
            }
        }
        return $point_id === null || $point_id === '' ? false:$point_id;
    }



}
