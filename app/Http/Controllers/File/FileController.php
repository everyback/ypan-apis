<?php

namespace App\Http\Controllers\File;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\FileModel as File;
use MongoDB;
use MongoDB\BSON;
use App\Model\UserFileModel as UserFile;
use DB;
use Psy\Exception;
use App\Model\FolderModel as Folder;
use MongoGrid\MongoGrid as Grid;
use zip\zip;
use SendFile\SendFile;
use App\Model\FileDownloadPathModel as DownloadPath;

class FileController extends Controller
{
    //
    function __construct()
    {
        $this->middleware('auth:api');
    }

    function addfile(Request $request,Grid $grid)
    {
        $file = $request->file("file");
        $from_md5 = strtolower($request->input('md5'));
        $slice_sha1 = strtolower($request->input('silce_sha1'));
        $filename = $request->input('filename');
        $userspace = auth('api')->user()->space;
        $userspaceused = auth('api')->user()->space_used;
        $user_id = auth('api')->user()->id;
        if (!$file )
        {
            $size = $request->input('filesize');
            if ($userspaceused+$size >$userspace)
            {
                return response()->json(['error'=>'space limit'],400);
            }
            $getfile = File::where([['md5',$from_md5],['slice_sha1',$slice_sha1],['file_size',$size]])->get();
            if (count($getfile) === 0)
            {
                return response()->json(['error'=>'No such file'],200);
            }
            else
            {
                try{
                    $save = $this->saveFile($request->attributes->get('dirarray'),auth('api')->user()->user_root,$user_id,$filename,$size,$getfile->first()->oid);
                }catch (\Exception $e)
                {
                    return response()->json(['error'=>$e],500);
                }
                if ($save)
                {
                    return response()->json(['success'=>'save complete,by quick'],200);
                }
            }
        }
        else
        {
            $size = $file->getSize();
            $slicefile = file_get_contents($file->getPathname(),0,null,0,10*(1024*1024));
            $slice_sha1 = bin2hex(hash('sha1', $slicefile, true));
            $hash = bin2hex(hash_file('sha256', $file->getPathname(), true));
            $md5 = bin2hex(hash_file('md5', $file->getPathname(), true));
            $crc32 =bin2hex(hash_file('crc32', $file->getPathname(), true));
            //$sha1 = bin2hex(hash_file('sha1', $file->getPathname(), true));
            $from_md5 = strtolower($request->input('md5'));
            if ($from_md5 !== $md5)
            {
                return response()->json(['error'=>'md5 check failed'],413);
            }
            $getfile = File::where([['md5',$from_md5],['slice_sha1',$slice_sha1],['file_size',$size]])->get();
            //dd(count($getfile));
            if (count($getfile) === 0)
            {
                $ret_oid = $grid->savefile($hash,$file->getPathname(),'r');
                $getoid = $ret_oid->__toString();
               // dd($getoid);
                $handle=finfo_open(FILEINFO_MIME_TYPE);//This function opens a magic database and returns its resource.
                $fileInfo=finfo_file($handle,$file->getPathname());// Return information about a file
               // dd($getoid);
                finfo_close($handle);
                File::create(
                    [
                        'oid'=>$getoid,
                        'first_name'=>$filename,
                        'file_size'=>$size,
                        'first_updater_id'=>$user_id,
                        'md5'=>$md5,
                        'sha256'=>$hash,
                        'slice_sha1'=>$slice_sha1,
                        'crc32'=>$crc32,
                        'file_type'=>$fileInfo,
                    ]
                );
                try{
                    $save = $this->saveFile($request->attributes->get('dirarray'),auth('api')->user()->user_root,$user_id,$filename,$size,$getoid);
                }catch (\Exception $e)
                {
                    return response()->json(['error'=>$e->getMessage()],500);
                }
                if ($save)
                {
                    return response()->json(['success'=>'save complete'],200);
                }
            }
            else
            {
                //dd($getfile->first()->oid);
               // dd(get_class_methods($getfile));
                try{
                    $save = $this->saveFile($request->attributes->get('dirarray'),auth('api')->user()->user_root,$user_id,$filename,$size,$getfile->first()->oid);
                }catch (\Exception $e)
                {
                    return response()->json(['error'=>$e->getMessage()],500);
                }
                if ($save)
                {
                    return response()->json(['success'=>'save complete,by quick'],200);
                }
            }

        }
        return response()->json(['error'=>'unknow error'],500);
    }

    function movefile(Request $request)
    {
        $dir = $request->attributes->get('dirarray');
        $user_id = auth('api')->user()->id;
        $user_root = auth('api')->user()->user_root;
        $filename = $request->input('filename');
        $fid = $this->searchFolder($dir,$user_root,$user_id);
        $user_id = auth('api')->user()->id;
        if (!$fid)
            return response()->json(['error'=>'no such folder'],404);
        $dir_to = $request->attributes->get('dir_to');


        $fid_to = $this->searchFolder($dir_to,$user_root,$user_id);
        if (!$fid_to)
            return response()->json(['error'=>'no such folder'],404);
        $find = UserFile::where(
            [
                [ 'folder_id',$fid],
                ['updater_id',$user_id],
                ['deleted', 0],
                //  'role'=>0,
            ]
        )->whereIn('file_name',$filename)->count();
        if ($find !== count($filename))
        {
            return response()->json(['error'=>'param false'],404);
        }

        \DB::beginTransaction();
        try
        {
            $res = UserFile::where(
                [
                    [ 'folder_id',$fid],
                    ['updater_id',$user_id],
                    ['deleted', 0],
                    //  'role'=>0,
                ]
            )->whereIn('file_name',$filename)->update(['folder_id' => $fid_to]);
            if ($res !== count($filename))
            {
                throw new \Exception('param false');
            }
                //return
            \DB::commit();

        }catch (\Exception $e)
        {
            \DB::rollBack();
            return response()->json(['error'=>$e],404);
        }
        return response()->json(['success'=>'move complete']);
    }

    function renamefile(Request $request)
    {
        $dir = $request->attributes->get('dirarray');
        $user_id = auth('api')->user()->id;
        $user_root = auth('api')->user()->user_root;
        $filename = $request->input('filename');
        $fid = $this->searchFolder($dir,$user_root,$user_id);
        $user_id = auth('api')->user()->id;
        $new_filename = $request->input('new_filename');
        $type = pathinfo("$new_filename",PATHINFO_EXTENSION);
        if (!$fid)
            return response()->json(['error'=>'no such folder'],404);
        $find = UserFile::where(
            [
                [ 'folder_id',$fid],
                ['updater_id',$user_id],
                ['deleted', 0],
                //  'role'=>0,
            ]
        )->where('file_name',$filename)->count();
        if ($find !== count($filename) || $find === 0)
        {
            return response()->json(['error'=>'param false'],404);
        }

        \DB::beginTransaction();
        try
        {
            $res = UserFile::where(
                [
                    [ 'folder_id',$fid],
                    ['updater_id',$user_id],
                    ['deleted', 0],
                    //  'role'=>0,
                ]
            )->where('file_name',$filename)->update(['file_name' => $new_filename],['file_type'=>$type]);
            if ($res !== count($filename))
            {
                throw new \Exception('param false');
            }
            //return
            \DB::commit();

        }catch (\Exception $e)
        {
            \DB::rollBack();
            return response()->json(['error'=>$e],404);
        }
        return response()->json(['error'=>'unknow error'],404);
    }

    function deletefile(Request $request)//可以多个文件一起删
    {
        $dir = $request->attributes->get('dirarray');
        $user_id = auth('api')->user()->id;
        $user_root = auth('api')->user()->user_root;
        $filename = $request->input('filename');
        $fid = $this->searchFolder($dir,$user_root,$user_id);
        $user_id = auth('api')->user()->id;
        if (!$fid)
            return response()->json(['error'=>'no such folder'],404);
        /*if (count($filename) === 1)
        {
            \DB::beginTransaction();
            try
            {
                $res = UserFile::where([
                    ['folder_id', $fid],
                    ['updater_id', $user_id],
                    ['deleted', 0],//  'role'=>0,
                ])->where('file_name', $filename)->pluck('file_size');

                if (count($res) === 0)
                {
                    throw new \Exception('no such file');
                }
                if (count($res) !== count($filename))
                {
                    throw new \Exception('param false');
                }
                //return
                $res = UserFile::where([
                    ['folder_id', $fid],
                    ['updater_id', $user_id],
                    ['deleted', 0],//  'role'=>0,
                ])->where('file_name', $filename)->update(['deleted' => 1]);
                $res2 = User::where('id',$user_id)->update([''=>]);
                \DB::commit();
                 }catch (\Exception $e)
                 {
                     \DB::rollBack();
                     return response()->json(['error'=>$e->getMessage()],404);
                 }
        }
        else */if(count($filename)!==0)
        {
            //dd($filename);
            $find = UserFile::where(
                [
                    [ 'folder_id',$fid],
                    ['updater_id',$user_id],
                    ['deleted',0],
                    //  'role'=>0,
                ]
            )->whereIn('file_name',$filename)->pluck('file_size')->toArray();
            $size = array_sum($find);
            if (count($find) !== count($filename))
            {
                return response()->json(['error'=>'param false'],404);
            }
            \DB::beginTransaction();
            try
            {
                $res = UserFile::where(
                    [
                        [ 'folder_id',$fid],
                        ['updater_id',$user_id],
                        ['deleted',0],
                        //  'role'=>0,
                    ]
                )->whereIn('file_name',$filename)->update(['deleted' => 1]);
                if ($res !== count($filename))
                {
                    throw new \Exception('param false');
                }
                $userspace = auth('api')->user()->space_used;
                $res2 = User::where('id',$user_id)->update(['space_used'=>$userspace - $size]);
                //return
                \DB::commit();

            }catch (\Exception $e)
            {
                \DB::rollBack();
                return response()->json(['error'=>$e],404);
            }
        }
        return response()->json(['success'=>'remove '.count($filename).' file(s)'],200);
    }

    function showfiles(Request $request)
    {
        $dir = $request->attributes->get('dirarray');
        $user_id = auth('api')->user()->id;
        $user_root = auth('api')->user()->user_root;
        $filename = $request->input('filename');
        $fid = $this->searchFolder($dir,$user_root,$user_id);
        $user_id = auth('api')->user()->id;
        $pagesize = $request->input('pagesize') ? $request->input('pagesize') :1;
        $dir = implode("/", $dir);
        $page = $request->input('page');
        if (!$page)
            $page = 1;
        if (!$fid)
            return response()->json(['error'=>'no such folder'],404);
        return UserFile::where([['folder_id',$fid],['deleted',0]])->paginate($pagesize)->appends(['dir' => $dir]);




/*        $filelist = UserFile::where(
            [
                [ 'folder_id',$fid],
                ['updater_id',$user_id],
                ['deleted',0],
            ]
        )->paginate($pagesize, $columns = ['file_name','file_type','file_size','created_at','updated_at'], $pageName = '',$page);*/
/*        if (!$filelist )
        {
            return response()->json(['success'=>'no file'],200);
        }*/





    }

    function copyfile(Request $request)
    {
        $dir = $request->attributes->get('dirarray');
        $user_id = auth('api')->user()->id;
        $user_root = auth('api')->user()->user_root;
        $filename = $request->input('filename');
        $fid = $this->searchFolder($dir,$user_root,$user_id);
        $user_id = auth('api')->user()->id;
        $dir_to = $request->attributes->get('dir_to');
        if (!$fid)
            return response()->json(['error'=>'no such folder'],404);

        $fid_to = $this->searchFolder($dir_to,$user_root,$user_id);
        if (!$fid_to)
            return response()->json(['error'=>'no such folder'],404);
        try{
            $res = UserFile::where(
                [
                    [ 'folder_id',$fid],
                    ['updater_id',$user_id],
                    ['deleted',0],
                    //  'role'=>0,
                ]
            )->whereIn('file_name',$filename)->pluck('file_size')->toArray();
            if (count($res) !== count($filename))
                throw new \Exception('no such files');
            $sum = array_sum($res);
            $userspace = auth('api')->user()->space_used;
            $allspace = auth('api')->user()->space;
            if ($sum + $userspace > $allspace)
                throw new \Exception('no enough space');
            $res = UserFile::where(
                [
                    [ 'folder_id',$fid_to],
                    ['updater_id',$user_id],
                    ['deleted',0],
                    //  'role'=>0,
                ]
            )->whereIn('file_name',$filename)->count();
            if ($res !== 0)
                throw new \Exception('have same files');
            $str = $this->setmult(count($filename));
            $datas = array_merge([$fid_to,$fid,$user_id],$filename);
            \DB::beginTransaction();
            try{
                $res2 = DB::insert("INSERT INTO user_files 
(folder_id,file_oid,file_name,file_type,updater_id,file_size,deleted,created_at,updated_at)
SELECT ?,file_oid,file_name,file_type,updater_id,file_size,deleted,created_at,updated_at 
FROM user_files WHERE(deleted=0 AND folder_id=? AND updater_id=?) AND file_name IN ($str)",
                    $datas);
                if (!$res2)
                {
                    throw new \Exception('unknow error');
                }
                $res3 = User::where('id',$user_id)->update(['space_used'=>$sum+$userspace]);
                DB::commit();
            }catch (\Exception $e)
            {
                DB::rollBack();
                throw new \Exception($e->getMessage());
            }
        }catch (\Exception $e)
        {
            return response()->json(['error'=>$e->getMessage()],400);
        }

        return response()->json(['success'=>'copy complete']);

       // dd($res2);
    }

    function countfile(Request $request)
    {

    }

    function createdownloadpath(Request $request, zip $zip,Grid $grid,SendFile $SendFile)//完整dir地址
    {
        $dir = $request->attributes->get('dirarray');
        $user_id = auth('api')->user()->id;
        $user_root = auth('api')->user()->user_root;
        $filename = $request->input('filename');
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
        if (count($res) !== count($filename) && count($res) === 0 )
        {
            return response()->json(['error'=>'param false'],404);
        }
        if (count($res) === 1)
        {
            $path = $this->makePath(40);
            $file_oid = $res[0]->file_oid;
            $file_name = json_encode($res[0]->file_name);
            $size = $res[0]->file_size;
            \DB::beginTransaction();
            try{
                $res = DownloadPath::create(
                    [
                        'file_oid'=>$file_oid,
                        'file_name'=>$file_name,
                        'file_download_path'=>$path,
                        'user_id'=>$user_id,
                        'file_size'=>$size,
                        'active_time'=>1,
                        'created_at'=>date('Y-m-d H:i:s'),
                    ]
                );
                \DB::commit();
            }catch (\Exception $e)
            {
                \DB::rollBack();
                return response()->json(['error'=>$e],404);
            }
            $path = $_SERVER["HTTP_HOST"].'/api/download/'.$path;
            return response()->json(['success'=>['path'=>$path]],200);
        }
        else
        {

        }
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

    protected function saveFile(array $dirarray,$user_root,$user_id,$filename,$size,$file_oid)// 0 正常 1 fid不对 ，2 存储问题
    {
        try
        {
            $fid = $this->searchFolder($dirarray,$user_root,$user_id);
            if (!$fid)
            {
                throw new \Exception("No such file");
            }
            $filetype =pathinfo($filename,PATHINFO_EXTENSION);
            if ($filetype === "")
                $filetype = "UNKNOWN";
            \DB::beginTransaction();
            try{
                $same = UserFile::where(
                    [
                        [ 'folder_id',$fid],
                        ['file_name',$filename],
                        ['updater_id',auth('api')->user()->id],
                        //  'role'=>0,
                    ]
                )->count();
                if ($same !==0)
                {
                    throw new \Exception('already have same name file');
                }
                UserFile::create(
                    [
                        'file_oid'=> $file_oid,
                        'folder_id'=>$fid,
                        'file_name'=>$filename,
                        'file_type'=>$filetype,
                        'updater_id'=>auth('api')->user()->id,
                        'file_size'=>$size,

                        //  'role'=>0,
                    ]
                );
                $user = User::find(auth('api')->user()->id);
                $user->space_used = $size + auth('api')->user()->space_used;;
                $user->save();
                \DB::commit();
            }catch (\Exception $e)
            {
                \DB::rollBack();
                dump($e);
                throw new \Exception($e->getMessage());
                //return response()->json(['error'=>'Bad Request'],400);
            }
        }catch (\Exception $exception)
        {
            throw new \Exception($e->getMessage());
        }

        return true;
    }



    protected function makePath( $length = 8 )
    {
        $arr = [1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz"];

        $string = implode("", $arr);

        $count = strlen($string) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $string[rand(0, $count)];
        }
        return $code;
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

}
