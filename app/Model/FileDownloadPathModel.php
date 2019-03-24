<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FileDownloadPathModel extends Model
{
    //
    protected $table = 'user_files_download';
    public $timestamps = false;
    protected  $fillable = ['file_oid','file_name','file_download_path','user_id','file_size','active_time','created_at'];
}
