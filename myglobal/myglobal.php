<?php
/**
 * Created by PhpStorm.
 * User: zt
 * Date: 2019/4/4
 * Time: 18:37
 */

namespace  myglobal;

class myglobal
{
    static function getdirarr($dir)
    {
        if (strpos($dir,"/",0) !== false)
        {
            $dirarray = explode("/",$dir);
        }
        elseif (!!strpos($dir,"\\",0) !== false)
        {
            $dirarray = explode("\\",$dir);
        }
        else
        {
            return false;
        }
        $dirarray = array_values(array_filter($dirarray, function ($v)
        {
            return !($v === ''|| $v === '..') ;
        }));
        return $dirarray;
    }


    static function setmult(int $number)
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

    static function makePath( $length = 8 )
    {
        $arr = [1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz",3=>'ABCDEFGHIJKLMNOPQRSTUVWXYZ'];

        $string = implode("", $arr);

        $count = strlen($string) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $string[rand(0, $count)];
        }
        return $code;
    }
}

