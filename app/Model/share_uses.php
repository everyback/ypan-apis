<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class share_uses extends Model
{
    //
    protected $table = 'share_uses';
    protected  $fillable = ["user_ip", "action", "user_id", "created_at", "updated_at"];
}
