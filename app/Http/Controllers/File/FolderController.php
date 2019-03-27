<?php

namespace App\Http\Controllers\File;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\FolderModel as Folder;
use App\Model\UserFileModel as UserFile;

class FolderController extends Controller
{
    //
    function __construct()
    {
        $this->middleware('auth:api');
    }

    function createFolder(Request $request)
    {
        $user_root = auth('api')->user()->user_root;
        $user_id = auth('api')->user()->id;
        $folder_name = $request->input('folder_name');
        if (!$folder_name)
            return response()->json(['error'=>'Bad Request'],400);
        $fid = $this->searchFolder($request->attributes->get('dirarray'),$user_root,$user_id );
        if (!$fid)
        {
            return response()->json(['error' => 'no such folder'],404);
        }
        try{
            $flight = Folder::where([['user_id',$user_id],['belong',$fid],['folder_name',$folder_name],['deleted','0']])->exists();
            if ($flight)
            {
                return  response()->json(['error'=>'already have same name'],401);
            }
        }catch (\Exception $e)
        {
            return  response()->json(['error'=>$e],500);
        }
        $newfid = Folder::create(
            [
                'belong'=>$fid,
                'folder_name'=>$folder_name,
                'creater_id'=>$user_id,
                'user_id'=>$user_id,
                //  'role'=>0,
            ]
        );
        return  $newfid ? response()->json(['success'=>['folder_name' =>$folder_name]]):response()->json(['error'=>'unknow error'],500);
    }

    function deleteFolder (Request $request)
    {
        $user_root = auth('api')->user()->user_root;
        $user_id = auth('api')->user()->id;
        $folder_name = $request->input('folder_name');
        if (!$folder_name && count($request->attributes->get('dirarray'))>=1 )
            return response()->json(['error'=>'Bad Request'],400);
        $fid = $this->searchFolder($request->attributes->get('dirarray'),$user_root,$user_id );
        if (!$fid  )
            return response()->json(['error' => 'no such folder'],404);
        \DB::beginTransaction();
        try{
          //  $get = Folder::where([['user_id',$user_id],['belong',$fid],['deleted','0']])->whereIn('folder_name',$folder_name)->update(["deleted" => 1]);

            $str = $this->setmult(count($folder_name));
            $datas = array_merge([$fid,$user_id],$folder_name);

            $get = \DB::select("with recursive mys  as(
                  select fid,belong
                  from folders
                  where
                    (belong=? and creater_id=? and deleted='0') and folder_name in($str)
                  union all
                  select
                    f.fid,
                    f.belong
                  from folders f inner join mys m on   m.fid = f.belong )
                select fid from mys ;",$datas);
            ;//全是对象。。。。
            $getarray = [];
            if (count($get) === 0)
                throw new \Exception("no such folder");
            $getarray = array_map(function ($value){
                return $value->fid;
            },$get);

           // dd($getarray);
           /* $get = \DB::update("with recursive mys  as(
                  select fid,belong
                  from folders
                  where
                    (belong=? and creater_id=? and deleted='0') and folder_name in($str)
                  union all
                  select
                    f.fid,
                    f.belong
                  from folders f inner join mys m on   m.fid = f.belong )
                update folders set deleted = 1  where fid in (select fid from mys) ;",$datas
                );*/

                $deletefolder =  Folder::where([['user_id',$user_id],['belong',$fid],['deleted','0']])->whereIn('fid',$getarray)->update(["deleted" => 1]);

                $find = UserFile::where(
                    [
                        [ 'folder_id',$fid],
                        ['updater_id',$user_id],
                        ['deleted',0],
                        //  'role'=>0,
                    ]
                )->whereIn('folder_id',$getarray)->pluck('file_size')->toArray();
                $size = array_sum($find);
                $userspace = auth('api')->user()->space_used;
                $res2 = User::where('id',$user_id)->update(['space_used'=>$userspace - $size]);
                $deletefile = UserFile::where([['updater_id',$user_id],['deleted','0']])->whereIn('folder_id',$getarray)->update(["deleted" => 1]);



            /*$flight = Folder::findOrFail($fid);
            $flight->deleted = 1;
            $flight->save();*/
            /*if ($get !== count($folder_name))
            {
                throw new \Exception('count file false');
            }*/
            \DB::commit();
        }catch (\Exception $e)
        {
            \DB::rollBack();
            return  response()->json(['error'=>$e->getMessage()],500);
        }

        return  response()->json(['success'=>'deleted']);
    }

    function renameFolder (Request $request)
    {
        $user_root = auth('api')->user()->user_root;
        $user_id = auth('api')->user()->id;
        $folder_name = $request->input('folder_name');
        $new_folder_name = $request->input('new_folder_name');
        if (!$folder_name || !$new_folder_name)
            return response()->json(['error'=>'Bad Request'],400);
        $fid = $this->searchFolder($request->attributes->get('dirarray'),$user_root,$user_id );
        if (!$fid)
        {
            return response()->json(['error' => 'no such folder'],404);
        }
        $fid = Folder::where([['user_id',$user_id],['belong',$fid],['folder_name',$folder_name],['deleted','0']])->value('fid');
        if (!$fid )
        {
            return response()->json(['error' => 'no such folder'],404);
        }

        try{
            $flight = Folder::findOrFail($fid);
            $flight->folder_name = $new_folder_name;
            $flight->save();
        }catch (\Exception $e)
        {
            return  response()->json(['error'=>$e],500);
        }

        return  response()->json(['success'=>'foldername changed']);
    }

    function moveFolder (Request $request)
    {
        $user_root = auth('api')->user()->user_root;
        $user_id = auth('api')->user()->id;
        $folder_name = $request->input('folder_name');
        $dir_to = $request->attributes->get('dir_to');
        if (!$folder_name || $dir_to === null)
            return response()->json(['error'=>'Bad Request'],400);
        $fid = $this->searchFolder($request->attributes->get('dirarray'),$user_root,$user_id );
        if (!$fid)
        {
            return response()->json(['error' => 'no such folder'],404);
        }
        if (count($request->attributes->get('dirarray')) !== 0 && count($dir_to) !== 0 )
        {
            if ($request->input('dir') === $request->input('dir_to'))
            {
                return response()->json(['error' => 'can t use same directory'],400);
            }
            $long = count($request->attributes->get('dirarray'));
            $from = '/'.implode("/", $request->attributes->get('dirarray'));
            $tofile = '';
            for ($i = 0; $i<= $long;$i++)
            {
                $tofile .= "/".$dir_to[$i];
            }
            foreach ($folder_name as $value)
            {
                if ($from."/".$value === $tofile)
                {
                    /*var_dump($from."/".$value);
                    die;*/
                    return response()->json(['error' => 'can t move to child directory'],400);
                }
            }
        }
        else
        {
            $tofile = '';
            $long = 1;
            if (count($dir_to) !== 0)
            {
                for ($i = 0; $i< $long;$i++)
                {
                    $tofile .= "/".$dir_to[$i];
                }
                foreach ($folder_name as $value)
                {

                    if ("/".$value === $tofile)
                    {
                        return response()->json(['error' => 'can t move to child directory'],400);
                    }
                }
            }
            else
            {
                if ($request->input('dir') === $request->input('dir_to'))
                {
                    return response()->json(['error' => 'can t use same directory'],400);
                }
            }
        }
        if (count($dir_to) === 0 )
            $newfid = $user_root;
        else
            $newfid = $this->searchFolder($request->attributes->get('dir_to'),$user_root,$user_id );
        $countfolder = Folder::where([['user_id',$user_id],['belong',$newfid],['deleted','0']])->whereIn('folder_name',$folder_name)->get();
        if (count($countfolder) !== 0)
            return response()->json(['error' => 'have same name folder'],400);
        \DB::beginTransaction();
        try{
            $fid = Folder::where([['user_id',$user_id],['belong',$fid],['deleted','0']])->whereIn('folder_name',$folder_name)->update(['belong' => $newfid]);
            if (!$fid)
                return response()->json(['error' => 'no such folder'],404);
            if($fid !== count($folder_name))
                throw new \Exception("count error");
            \DB::commit();
        }catch (\Exception $e)
        {
            \DB::rollback();
            return  response()->json(['error'=>$e->getMessage()],500);
        }
        return  response()->json(['success'=>'folder moved']);
    }

    function copyFolder (Request $request)
    {
       // return response()->json(['error' => 'bu hui'],500);
        $user_root = auth('api')->user()->user_root;
        $user_id = auth('api')->user()->id;
        $folder_name = $request->input('folder_name');
        $dir_to = $request->attributes->get('dir_to');
        //dd($dir_to);
        if (!$folder_name )
            return response()->json(['error'=>'Bad Request'],400);
        $fid = $this->searchFolder($request->attributes->get('dirarray'),$user_root,$user_id );
        //dd($request->attributes->get('dirarray'));
        if (!$fid)
        {
            return response()->json(['error' => 'no such folder'],404);
        }
        if (count($dir_to) === 0 )
            $newfid = $user_root;
        else
            $newfid = $this->searchFolder($request->attributes->get('dir_to'),$user_root,$user_id );
        $foldersum = Folder::where([['user_id',$user_id],['belong',$fid],['deleted','0']])->whereIn('folder_name',$folder_name)->count();//测数量
       // dd($fid);
        if ($foldersum !== count($folder_name))
        {
            return response()->json(['error'=>'sum folder false ,please fresh it and reselect'],400);
        }
        $cover = Folder::where([['user_id',$user_id],['belong',$newfid],['deleted','0']])->whereIn('folder_name',$folder_name)->count();//测重复
        if ($cover !== 0 )
        {
            return response()->json(['error'=>'have same name folder'],400);
        }
        $str = $this->setmult(count($folder_name));
        $datas = array_merge([$fid,$user_id],$folder_name);
        $res = \DB::select("with recursive mys  as(
                  select fid,belong
                  from folders
                  where
                    (belong=? and creater_id=? and deleted='0') and folder_name in($str)
                  union all
                  select
                    f.fid,
                    f.belong
                  from folders f inner join mys m on   m.fid = f.belong )
                select * from mys;",
            $datas);
       $res = array_merge([(object)['fid'=>$fid,'belong'=>-1]],$res);//拿到正式的文件夹树平面
        if(count($res) > 1000)
        {
            return response()->json(['error'=>'too much, gun'],400);
        }
       $tree = $this->arrayToTree($res,$fid);//文件夹树生成
            //深度优先吧。。。。。
        $get = $this->setfolders($tree,$newfid);
       // dd($get);

        return  response()->json(['success'=>'folder copyed']);
    }

    function folderList(Request $request)
    {
        $user_root = auth('api')->user()->user_root;
        $user_id = auth('api')->user()->id;
        $fid = $this->searchFolder($request->attributes->get('dirarray'),$user_root,$user_id );
        $get = Folder::where([['user_id',$user_id],['belong',$fid],['deleted','0']])->get();
        if (count($get) === 0)
        {
            return response()->json(['empty']);
        }
        else
        {
            return Folder::where([['user_id',$user_id],['belong',$fid],['deleted','0']])->get();
        }
    }



    protected function searchFolder(array $dir,$user_root,$user_id)
    {
       /* $user_root = auth('api')->user()->user_root;
        $user_id = auth('api')->user()->id;*/
       // $point_id = -1;
        // $point_id = Folder::where([['user_id',$user_id],['folder_name',$user_root]])->value('fid');//先定位到user_root 的fid;
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

    protected function setmult(int $number)
    {
        $str = '';
        $flag = true;
        for($i = 0 ;$i<$number;$i++)
        {
            if ($flag)
            {
                $str .= '?';
                $flag = false;
            }
            else
            {
                $str .= ',?';
            }
        }
        return $str;
    }


    protected function arrayToTree($source,$fid){
        $childMap = [];
        foreach ($source as $key => $value) {

            $k = $value->belong;
            if( !isset( $childMap[$k] ) )
            {
                $childMap[$k] = [];
            }
            $childMap[$k][] = $value;
        }
        return $this->makeTree($childMap,$fid);
       // return $childMap;
    }

    protected function makeTree($childMap,$parentid=0){
        $k = $parentid;
        $items = isset( $childMap[$k] )?$childMap[$k]:[];
        if(!$items)
        {
            return 0;
        }
        $trees = [];
        //dd($items);

        foreach ($items as  $value) {
            //dump($value);
            $trees[] = ['fid'=>$value->fid,'child'=>$this->makeTree($childMap,$value->fid)];
        }
        return $trees;
    }

    protected function setfolders($tree,$fid)
    {
      //  $fid =
      //  dump($fid);
        foreach ($tree as $value)
        {
           // dump($value["fid"]);
            $res2 = \DB::insert("INSERT INTO folders 
( belong, folder_name, creater_id, user_id, deleted, created_at, updated_at)
SELECT  ?, folder_name, creater_id, user_id, deleted, created_at, updated_at
FROM folders WHERE(deleted=0 AND fid=? )",[$fid,$value["fid"]]);
          //
            $nfid = \DB::select("SELECT LAST_INSERT_ID() l")[0]->l;
            //dump($value["child"]);
            $res2 = \DB::insert("INSERT INTO user_files 
(folder_id,file_oid,file_name,file_type,updater_id,file_size,deleted,created_at,updated_at)
SELECT ?,file_oid,file_name,file_type,updater_id,file_size,deleted,created_at,updated_at 
FROM user_files WHERE(deleted=0 AND folder_id=? ) ",[$nfid,$value["fid"]]);
          //  dd($res2);
            if ($value["child"] !== 0)
            {
                $this->setfolders($value["child"],$nfid);
            }

        }
        return 0;
    }
}




/* $fid = Folder::where([['user_id',$user_id],['belong',$fid],['deleted','0']])->whereIn('folder_name',$folder_name)->pluck('fid');
        if (!$fid)
            return response()->json(['error' => 'no such folder'],404);
       // dd($fid);
        if (count($fid) !== count($folder_name) )
        {
            return response()->json(['error'=>'no some folder'],400);
        }

        if (!$fid || !$newfid )
        {
            return response()->json(['error' => 'no such folder'],404);
        }
        if (count($fid) === 1)
        {
            try{
                $flight = Folder::findOrFail($fid);
                $flight->belong = $newfid;
                $flight->save();
            }catch (\Exception $e)
            {
                return  response()->json(['error'=>$e],500);
            }
        }
        else
        {
            try{
                $flight = Folder::where('id', 1)
                                ->update(['fid' => $newfid]);
                $flight->belong = $newfid;
                $flight->save();
            }catch (\Exception $e)
            {
                return  response()->json(['error'=>$e],500);
            }
        }*/










/*function searchFullPath(Request $request) //获取fid下全部文件夹树 废弃不用
{
        $fid = $request->input('fid');
        if (!$fid)
        {
            return response()->json(['error'=>'Bad Request'],400);
        }
        $gets = \DB::select('with recursive mys  as(
                    select fid,belong,folder_name,creater_id
                    from file_paths
                    where
                        fid=:fid AND deleted=0
                    union all
                    select
                     f.fid,
                     f.belong,
                     f.folder_name,
                     f.creater_id
                     from folders f inner join mys m on m.belong = f.fid )
                        select * from mys;',
            [':fid'=>$fid]);

        if ($i=count($gets) >0)
        {
            $path='/';
            $paths = [];
            for($i =count($gets)-1;$i>=0;$i--)
            {
                $path .= $gets[$i]->folder_name.'/';
                //$gets[$i] = (array)$gets[$i];
                array_push($paths,(array)$gets[$i]);
            }
            // $path .= $fid;

            //  dd($paths);
            return response()->json(['success'=>['fullpath'=>$path,'paths'=>$paths]]);
        }
        else
        {
            return response()->json(['failed'=>'找不到对应路径'],400);
        }

 //   return response()->json(['failed'=>$message],400);
}*/

/* function searchChildFiles(Request $request)
 {
     //只查找一层的，要查找父组件就在发起一次查询吧
     $validator = Validator::make($request->all(), [
         'fid' => ['string','max:255', new FilenameRule(),'bail','required'],
     ]);
     if ($validator->fails())
     {
         $arrs = $validator->failed();
         foreach($arrs as $key=>$row)
         {
             $errs[] = $key;
         }
         $message = $errs;
         $status = 400;
     }
     else
     {
         $fid = $request->input('fid');
         $gets = \DB::table('file_paths')->select('fid','belong','folder_name','creater_id')
                    ->where([['belong','=',$fid],['deleted','=','0']])->get();
         if ($i=count($gets) >0)
         {
             $path='/';
             $paths = [];
             for($i =count($gets)-1;$i>=0;$i--)
             {
                 array_push($paths,(array)$gets[$i]);
             }
             return response()->json(['success'=>['paths'=>$paths]]);
         }
         else
         {
             return response()->json(['failed'=>'找不到对应路径'],400);
         }
     }
     return response()->json(['failed'=>$message],400);
 }*/

/* function lastFolder()
 {
     $sql = 'select fid,belong,folder_name,creater_id
from file_paths f
where fid not in
    (select file_paths.belong from file_paths where file_paths.belong is not null) and deleted=0;';
     $gets = \DB::select($sql);
     // dd($gets);
     return response()->json(['success'=>['paths'=>$gets]]);
 }*/

/*function add(Request $request)
{
    // dd($request->all());
    $validator = Validator::make($request->all(), [
        'foldername' => ['string','max:255', new FilenameRule(),'bail','required'],
        'belong'     => ['numeric','max:255','bail','min:1','required'],
    ]);
    if ($validator->fails())
    {
        $arrs = $validator->failed();
        foreach($arrs as $key=>$row)
        {
            $errs[] = $key;
        }
        $message = $errs;
        $status = 400;
    }
    $hasfid = \DB::table('file_paths')->select()->where([['fid','=',$request->input('belong')],['deleted','=','0']])->get();

    // dd(count($hasfid->all()));
    if (!isset($message))
    {
        if (count($hasfid->all()) ===0)
        {
            $message = 'fid错误';
            $status = 400;
        }
        else
        {
            $rename = \DB::table('file_paths')->select()->where([['belong','=',$request->input('belong')],
                ['folder_name','=',$request->input('foldername')],])->get();
            if (count($rename) !==0)
            {
                $message = '重名';
                $status = 400;
            }
            else{
                $new = DB::table('file_paths')->insertGetId([
                    //'creater_id'    => session()->get('uid'),
                    'creater_id'    => 1,
                    'belong'        => $request->input('belong'),
                    'folder_name'  => $request->input('foldername'),
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
                //   var_dump($new);
            }
        }
    }
    if (isset($message))
    {
        return response()->json(['failed'=>$message],$status);
    }
    else
    {
        return response()->json(['success'=>[
                'message'=>'创建成功',
                'fid'     => "$new",
            ]
            ]
        );
    }
}*/
/*
    function deleteFolder(Request $request)
    {
        //  dd($request->all());
        $validator = Validator::make($request->all(), [
            'fid'     => ['numeric','max:255','bail','min:1','required'],
        ]);
        if ($validator->fails())
        {
            $arrs = $validator->failed();
            foreach($arrs as $key=>$row)
            {
                $errs[] = $key;
            }
            $message = $errs;
            $status = 400;
        }

        $sql = '
        with recursive child_fid as
        (
              select fid,belong from file_paths where fid=30
                   union all
              select f.fid,f.belong from file_paths f inner join child_fid c on f.belong=c.fid
        )select fid from child_fid;
';
        $getfid = \DB::select($sql,[':fid'=>$request->input('fid')]);
        $fids = [];
        foreach ($getfid as $row)
        {
            array_push($fids,$row->fid);
        }
        // dd($fids);

        if (count($fids) ==0)
        {
            //  DB::rollBack();
            return response()->json(['failed'=>'对应fid 不存在'],404);
        }
        else
        {
            DB::beginTransaction();
            try{
                $getfid = \DB::table('file_paths')->whereIn('fid',$fids)
                             ->update(['deleted'=>1,'updated_at'=>date('Y-m-d H:i:s')]);
                $updatearticle = \DB::table('articles')
                                    ->whereIn('belong',$fids)
                                    ->update(['deleted'=>1,'updated_at'=>date('Y-m-d H:i:s')]);
            }catch (\Exception $e)
            {
                DB::rollBack();
                return response()->json([
                    'failed'=> '删除失败，未知错误',
                ],400);
            }
            DB::commit();
            return response()->json(['success'=>[
                'message'=> '删除成功',
                'count'  => "$getfid",
            ]]);
        }*/
// $getfid = DB::table('file_paths')->where('belong',$request->input('fid'))->delete();

// }

/* function rename(Request $request)
 {
     $validator = Validator::make($request->all(), [
         'fid'     => ['numeric','max:255','bail','min:1','required'],
         'foldername' => ['string','max:255', new FilenameRule(),'bail','required'],
     ]);
     if ($validator->fails())
     {
         $arrs = $validator->failed();
         foreach($arrs as $key=>$row)
         {
             $errs[] = $key;
         }
         $message = $errs;
         $status = 400;
     }
     else{
         $named = DB::table('file_paths')->where([['fid',$request->input('fid')],[ 'folder_name'  , $request->input('foldername')]])
                    ->get();
         if (count($named)!==0)
         {
             $message = '重名了';
             $status = 400;
         }
         else{
             $new = DB::table('file_paths')->where('fid',$request->input('fid'))->update([
                 'folder_name'  => $request->input('foldername'),
                 'updated_at'   => date('Y-m-d H:i:s'),
             ]);
             if ($new ==1)
             {
                 return response()->json(['success'=>'修改成功']);
             }
             $message = '修改失败';
             $status = 400;
         }
     }
     return response()->json(['failed'=>$message],$status);
 }*/

/*  function movefolder(Request $request)
  {
      $validator = Validator::make($request->all(), [
          'fid'     => ['numeric','max:255','bail','min:1','required'],
          'belong' => ['numeric','max:255','bail','min:1','required'],
      ]);
      if ($validator->fails())
      {
          $arrs = $validator->failed();
          foreach($arrs as $key=>$row)
          {
              $errs[] = $key;
          }
          $message = $errs;
          $status = 400;
      }
      else
      {
          $belong = DB::table('file_paths')->where('fid',$request->input('belong'))->get();
          if (count($belong)===0)
          {
              $message = '依赖文件夹不存在';
              $status = 400;
          }
          else
          {
              $belong = DB::table('file_paths')->where('fid',$request->input('fid'))->get();
              if (count($belong)===0)
              {
                  $message = '该文件夹不存在';
                  $status = 400;
              }
              else
              {
                  $new = DB::table('file_paths')->where('fid',$request->input('fid'))->update([
                      'belong'  => $request->input('belong'),
                      'updated_at'   => date('Y-m-d H:i:s'),
                  ]);
              }
          }
          if (count($belong)===1)
          {
              return response()->json(['success' => '修改成功']);
          }
          else
          {
              $message = '修改失败';
              $status = 400;
          }
      }
      return response()->json(['failed'=>$message],$status);
  }*/