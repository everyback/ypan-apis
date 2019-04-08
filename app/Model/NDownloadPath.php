<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class NDownloadPath extends Model
{
    //
    protected $table = 'n_download_paths';
    protected  $fillable = ["download_thing", "path", "show_name", "download_folders", "download_files", "invalidation", "sum", "user_id", "active_time","created_at"];
    protected $hidden = ['active_time','invalidation','code','mid'];
    //    protected $casts = [ 'share_thing' => 'json' ];
    //    protected $appends = ['is_file','is_folder'];
    protected $casts = [
        'download_folders' => 'array',
        'download_files'   => 'array',
        'download_thing'   => 'array',
    ];
    public $timestamps = false;

    public function  getCreatedAtAttribute(){
        return strtotime($this->attributes['created_at']);
    }
}
