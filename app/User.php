<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;



class User extends Authenticatable implements JWTSubject
{
     use Notifiable;
    protected  $table = 'users';
    protected $primaryKey = 'id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email','role','phonenumber','space',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token','created_at', 'updated_at','email_verified_at'
    ];


    public function getJWTIdentifier()
    {
     //   dd($this->getKey());
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {/*'name', 'email','role','phonenumber','space'*/
        return [];
    }
}
