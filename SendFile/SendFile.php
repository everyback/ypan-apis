<?php
/**
 * Created by PhpStorm.
 * User: zt
 * Date: 2019/3/3
 * Time: 23:31
 */

namespace SendFile;

use zip\zip;

class SendFile
{
    protected $zip;

    function __construct(zip $zip)
    {
        $this->zip = $zip;
    }



    function singlefile($destination,$size,$filename)
    {
        $begin = 0;
        $end = $size - 1;

        if (isset ( $_SERVER ['HTTP_RANGE'] )) {
            if (preg_match ( '/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER ['HTTP_RANGE'], $matches )) {
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

        header ( "Content-Disposition: attachment; filename=".$filename );
        header ( "Content-Transfer-Encoding: binary" );
        header ( "Last-Modified: ".time() );

        set_time_limit(0);

        $cur = $begin;
        while (  $cur <= $end && (connection_status () == 0) ) {
            echo stream_get_contents($destination, 1024 * 16, $cur);
            $cur += 1024 * 16;
            ob_flush();
            flush();
            // usleep(1*1000);
        }
    }

    function sethead($allname)
    {
        Header ( "Content-type: application/octet-stream" );
        header ( 'Cache-Control: public, must-revalidate, max-age=0' );
        header ( 'Pragma: no-cache' );
        header ( 'Accept-Ranges: bytes' );

        header ( "Content-Disposition: attachment; filename=$allname.zip" );
        header ( "Content-Transfer-Encoding: binary" );
        header ( "Last-Modified: ".time() );

        header ( 'HTTP/1.1 200 OK' );
        $this->zip->setDoWrite();

    }


    function zipfile($destination,$filename)
    {
//        var_dump($filename);
//            die;
        $this->zip->addFile(stream_get_contents($destination, -1, 0),iconv("utf-8","gbk",$filename));
        flush();

    }

    function endzipsend()
    {
        $this->zip->file();
    }




}