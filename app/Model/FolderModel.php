<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FolderModel extends Model
{
    //
    protected $table = 'folders';
    // protected $primaryKey = 'id';
    protected $primaryKey = 'fid';
   // public $timestamps = false;
    protected $fillable = ['belong','folder_name','creater_id','user_id','deleted'];

    protected $hidden = ['fid','belong','deleted','creater_id','user_id'];
    protected $appends = ['is_file','is_folder'];

    public function  getCreatedAtAttribute(){
        return strtotime($this->attributes['created_at']);
        //    return date('Y-m-d H:i:s',$this->attributes['created_at']);
    }

    public  function  getUpdatedAtAttribute(){
        return strtotime($this->attributes['updated_at']);
        // return date('Y-m-d H:i:s',$this->attributes['updated_at']);
    }

    public  function  getIsFileAttribute(){
        return $this->attributes['is_file'] = false;
        // return date('Y-m-d H:i:s',$this->attributes['updated_at']);
    }

    public  function  getIsFolderAttribute(){
        return $this->attributes['is_folder'] = true;
        // return date('Y-m-d H:i:s',$this->attributes['updated_at']);
    }
}
