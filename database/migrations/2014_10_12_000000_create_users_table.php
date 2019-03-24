<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('users'))
        {
            Schema::create('users', function (Blueprint $table)
            {
                $table->increments('id');
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->timestamp('email_verified_at')->nullable();
                $table->integer('role')->nullable()->default(0);
                $table->unsignedBigInteger('space')->nullable();
                $table->unsignedBigInteger('space_used')->default(0);
               // $table->integer('')->nullable();
                $table->integer('user_root')->nullable();
               // $table->string('space_used');
                $table->string('phonenumber')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }
        else{
/*            Schema::table('users', function (Blueprint $table) {
               // $table->integer('space_used')->default(0);
               // $table->integer('')->nullable();
              //  $table->string('user_root');
             //   $table->string('space_used');
            });*/
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
       // Schema::dropIfExists('users');
    }
}
