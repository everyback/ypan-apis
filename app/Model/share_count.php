<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class share_count extends Model
{
    //
    protected $table = 'share_counts';
    protected $primaryKey = "share_path";
    protected  $fillable = ["share_path", "read", "resave", "download", "created_at", "updated_at"];
//    protected $hidden = ['active_time','invalidation','code','mid'];

}
