<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FileModel extends Model
{
    //
    protected $table = 'files';
    protected  $fillable = ['oid','first_name','file_type','updater_id','first_updater_id','md5','sha256','crc32','slice_sha1','file_size','deleted'];

}
