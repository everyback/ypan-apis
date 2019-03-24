<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
//use App\Mongodb;
use MongoDB;
use MongoDB\BSON;
use zip\zip;

class FilesController extends Controller
{
    //

    function putfiles(Request $request ,MongoDB\Client $client )
    {
        $file = $request->file("file");
        dd($file);
       // $file = $_FILES['file'];
        /*
                dump($file);
               dump(file_get_contents('php://input'));
                return response()->json([
                    "bb"=>"cc",
                    "aa"=>$file,
                ]);*/

       // $con = new Mongodb();
       // dump($mongodb->collection("fs.files")->get());
     //  var_dump($request->all());
      //  dump($file);
       // var_dump($file);
       // exit();

       // if ($file !== null )
     //   {
          //  dump($file->getPathname());
            $hash = bin2hex(hash_file('sha256', $file->getPathname(), true));
            $md5 = bin2hex(hash_file('md5', $file->getPathname(), true));
            $from_md5 = $request->input('md5');
            $getoid = (new MongoDB\client(
                "mongodb://127.0.0.1:27017",
                [
                    'username' => 'griduser',
                    'password' => '123456',
                    "authSource"=>"gridfs"
                ]))->gridfs->selectGridFSBucket(["chunkSizeBytes"=>1044480])->uploadFromStream($hash,fopen($file->getPathname(),'r'));
            return response()->json(['oid'=>$getoid->__toString()],200);
//        }
//        else
//        {
           return response()->json(['oid'=>false],400);
   //     }

       // dump($hash);
       // $hash = 'sssss';


        /*bson
         * array:7 [
  0 => "__construct"
  1 => "getTimestamp"
  2 => "__set_state"
  3 => "__toString"
  4 => "jsonSerialize"
  5 => "serialize"
  6 => "unserialize"
]*/


      //  return response()->json(['oid'=>"fdgfdgdfgdfg"],200);
        /*dump((new MongoDB\client(
            "mongodb://127.0.0.1:27017",
            [
                'username' => 'griduser',
                'password' => '123456',
                "authSource"=>"gridfs"
            ]))->gridfs->selectGridFSBucket()->uploadFromStream($hash,fopen($file->getPathname(),'r')));*/
       // dump($mongodb->selectGridFSBucket());

      //  dump($con->collection('fs.chunk')->find());

    }
    function downloadfiles(zip $zip)
    {


       $res = (new MongoDB\client(
            "mongodb://127.0.0.1:27017",
            [
                'username' => 'griduser',
                'password' => '123456',
                "authSource"=>"gridfs"
            ]))->gridfs->selectGridFSBucket()->find(["md5"=>"58d6d6332f939fd9b5435456b644b825","filename"=>"ab6f5c266be0e6e4fcb06f02e62b1ddba9fce0b7895e90225d3e3750084b6b03"])->toArray();
        $fileid = $res[0]->bsonSerialize()->_id;
        // dump($fileid->__toString());
        //dump();
        $filesize = $res[0]->bsonSerialize()->length;
        //   var_dump("Accept-Length: " .$filesize);
        /*         $destination = fopen('php://temp', 'w+b');
                $getfile = (new MongoDB\client(
                    "mongodb://127.0.0.1:27017",
                    [
                        'username' => 'griduser',
                        'password' => '123456',
                        "authSource"=>"gridfs"
                    ]))->gridfs->selectGridFSBucket()->downloadToStream($fileid,$destination);*/

        $zip->setDoWrite();




        //$o_id = new MongoDB\BSON\ObjectID("5c7ad15bce2c867e7800400c");
        $o_id = new MongoDB\BSON\ObjectID("5c7b8c6bce2c866e88002c6a");
        $destination = fopen('php://temp', 'w+b');
        $getfile = (new MongoDB\client(
            "mongodb://127.0.0.1:27017",
            [
                'username' => 'griduser',
                'password' => '123456',
                "authSource"=>"gridfs"
            ]))->gridfs->selectGridFSBucket()->downloadToStream($o_id,$destination);



        if (! $res[0]) {
            header ( "HTTP/1.1 404 Not Found" );
            return;
        }

        $size = $filesize;
        //  $time = date ( 'r', filemtime ( $location ) );
        // stream_get_contents($destination, -1, 0);
        if (! $res[0]) {
            header ( "HTTP/1.1 505 Internal server error" );
            return;
        }

        $begin = 0;
        $end = $size - 1;

        if (isset ( $_SERVER ['HTTP_RANGE'] )) {
            if (preg_match ( '/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER ['HTTP_RANGE'], $matches )) {
                // 读取文件，起始节点
                $begin = intval ( $matches [1] );

                // 读取文件，结束节点
                if (! empty ( $matches [2] )) {
                    $end = intval ( $matches [2] );
                }
            }
        }

        if (isset ( $_SERVER ['HTTP_RANGE'] )) {
            header ( 'HTTP/1.1 206 Partial Content' );
        } else {
            header ( 'HTTP/1.1 200 OK' );
        }

        Header ( "Content-type: application/octet-stream" );
        header ( 'Cache-Control: public, must-revalidate, max-age=0' );
        header ( 'Pragma: no-cache' );
        header ( 'Accept-Ranges: bytes' );
        header ( 'Content-Length:' . (($end - $begin) + 1) );

        if (isset ( $_SERVER ['HTTP_RANGE'] )) {
            header ( "Content-Range: bytes $begin-$end/$size" );
        }

        header ( "Content-Disposition: attachment; filename=ssssss.zip" );
        header ( "Content-Transfer-Encoding: binary" );
        header ( "Last-Modified: ".time() );

        set_time_limit(0);

        $zip->addFile(stream_get_contents($destination, -1, 0),iconv("utf-8","gbk",'filessss'));
        flush();
        $zip->setComments(iconv("utf-8","gbk","一些注释"));
        $zip->file();





        /*$cur = $begin;
        while (  $cur <= $end && (connection_status () == 0) ) {
            echo stream_get_contents($destination, 1024 * 16, $cur);
            $cur += 1024 * 16;
            // usleep(1*1000);
        }*/
    }
}





// dump($_SERVER);
// $file = stream_get_contents($destination, -1, 0);
/* Header ( "Content-type: application/octet-stream" );
 Header ( "Accept-Ranges: bytes" );
// Header ( "Accept-Length: " .$filesize );
 Header('Content-Length:'.$filesize);
 Header ( "Content-Disposition: attachment; filename=8c2.vmem" );
 //echo stream_get_contents($destination, -1, 0);

 $chunkSize = 1024 * 1024;
 $nowpoint = 0;
 while ($nowpoint <= $filesize  && connection_status () == 0 ) {

     $buffer = stream_get_contents($destination, $chunkSize, $nowpoint);
     echo $buffer;
     ob_flush();
     flush();
     $nowpoint += $chunkSize;
 }*/
//  fclose($destination);
// echo $file;
// dump();