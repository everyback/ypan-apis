<?php
/**
 * Created by PhpStorm.
 * User: zt
 * Date: 2019/3/3
 * Time: 17:00
 */

namespace MongoGrid;
use MongoDB;


class MongoGrid
{
    protected $concent = null;
    protected $chunksize = 1020*1024 ;

    function __construct($url,$config,$chunksize = 0)
    {
        $this->concent = new MongoDB\client('mongodb://'.$url,$config);
        if ($chunksize > 0)
        $this->chunksize = $chunksize;
    }


    function savefile($filename,$filepath,$mode = 'r')
    {
        return $this->concent->gridfs->selectGridFSBucket(["chunkSizeBytes"=>$this->chunksize])->uploadFromStream($filename,fopen($filepath,$mode));
    }

    function getfile($o_id,$destination)
    {
        $o_id = new MongoDB\BSON\ObjectID($o_id);
        $this->concent->gridfs->selectGridFSBucket()->downloadToStream($o_id,$destination);
        return $destination;
    }

    function findfile($o_id,$destination)
    {
        $o_id = new MongoDB\BSON\ObjectID($o_id);
        return $this->concent->gridfs->selectGridFSBucket()->findOne(['_id'=>$o_id]);
    }
}