<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ShareModel extends Model
{
    //
    protected $table = 'share';
    protected  $fillable = ['share_thing','share_path','user_id','private','code','active_time','invalidation','share_files','share_folders','created_at','show_name','sum'];
    protected $hidden = ['active_time','invalidation','code','mid'];
//    protected $casts = [ 'share_thing' => 'json' ];
    //    protected $appends = ['is_file','is_folder'];
    protected $casts = [
        'share_folders' => 'array',
        'share_files'   => 'array',
        'share_thing'   => 'array',
    ];
    public $timestamps = false;

    public function  getCreatedAtAttribute(){
        return strtotime($this->attributes['created_at']);
    }
/*    public function  getShareFoldersAttribute(){
        return json_decode($this->attributes['share_folders'],true);
    }
    public function  getShareFilesAttribute(){
        return json_decode($this->attributes['share_files'],true);
    }
    public function  setShareFilesAttribute($value){
        dd($value);
        $this->attributes['share_files'] = json_encode($value);
        //return json_encode($this->attributes['share_files']);
    }
    public function  setShareFoldersAttribute($value){
        dd($value);
        $this->attributes['share_folders'] = json_encode($value);

        //return json_encode($this->attributes['share_folders']);

    }

    public function  setShowNameAttribute($value){
        dd($value);
       // $this->attributes['share_folders'] = json_encode($value);

        //return json_encode($this->attributes['share_folders']);

    }*/
}
