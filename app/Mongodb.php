<?php
/**
 * Created by PhpStorm.
 * User: zt
 * Date: 2019/2/4
 * Time: 1:15
 */

namespace App;

use DB;

class Mongodb
{
    public function __construct()
    {
        return DB::connection('mongodb');
    }
}