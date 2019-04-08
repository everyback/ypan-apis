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

    static function makePath( $length = 8 ,$lower = false)
    {
        $arr = [1 => "0123456789", 2 => "abcdefghijklmnopqrstuvwxyz",3=>'ABCDEFGHIJKLMNOPQRSTUVWXYZ'];

        $string = implode("", $arr);

        $count = strlen($string) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $string[rand(0, $count)];
        }
        if ($lower === true)
            return strtolower($code);
        else
            return $code;
    }

    static function arrayToTree($source,$fid){//初步整理，将上下节点整理归属
        $childMap = [];
        foreach ($source as $key => $value) {

            $k = $value->belong;
            if( !isset( $childMap[$k] ) )
            {
                $childMap[$k] = [];
            }
            $childMap[$k][] = $value;//向子项目添加树结构
        }
        return self::makeTree($childMap,$fid);
        // return $childMap;
    }

    static function makeTree($childMap,$parentid=0){
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
            $trees[] = ['fid'=>$value->fid,'child'=>self::makeTree($childMap,$value->fid)];
        }
        return $trees;
    }

}

