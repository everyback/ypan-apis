<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserFileModel extends Model
{
    //
    protected $table = 'user_files';
    protected  $fillable = ['folder_id','file_oid','file_name','file_type','updater_id','file_size','deleted'];
    protected $hidden = ['folder_id','file_oid','deleted','mid','updater_id'];
    protected $appends = ['is_file','is_folder'];

  /*  protected $casts = [
        'created_at' => 'datetime:Y-m-d ',
        'updated_at' => 'datetime:Y-m-d ',
    ];*/


    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

/*    public  function  setCreatedAtAttribute($value)
    {
        $this->attributes['created_at'] = strtotime($value);
    }

    public  function  setUptatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = strtotime($value);
    }*/

    public function  getCreatedAtAttribute(){
        return strtotime($this->attributes['created_at']);
    //    return date('Y-m-d H:i:s',$this->attributes['created_at']);
    }

    public  function  getUpdatedAtAttribute(){
        return strtotime($this->attributes['updated_at']);
       // return date('Y-m-d H:i:s',$this->attributes['updated_at']);
    }

    public  function  getFileTypeAttribute(){
        return $this->attributes['file_type'] === 'UNKNOWN' ? '': $this->attributes['file_type'];
        // return date('Y-m-d H:i:s',$this->attributes['updated_at']);
    }

    public  function  getIsFileAttribute(){
        return $this->attributes['is_file'] = true;
        // return date('Y-m-d H:i:s',$this->attributes['updated_at']);
    }

    public  function  getIsFolderAttribute(){
        return $this->attributes['is_folder'] = false;
        // return date('Y-m-d H:i:s',$this->attributes['updated_at']);
    }
}
