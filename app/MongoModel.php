<?php
/**
 * Created by PhpStorm.
 * User: zt
 * Date: 2019/2/4
 * Time: 1:34
 */

namespace App;
namespace App\Model;

//use Emadadly\LaravelUuid\Uuids;
use Jenssegers\Mongodb\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;




class MongoModel extends Model
{
        use SoftDeletes;

        protected  $connection = "mongodb";
       // protected $collection = "";
        protected $incrementing = false;
}