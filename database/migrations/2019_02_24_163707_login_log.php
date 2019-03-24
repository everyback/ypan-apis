<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LoginLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        if (!Schema::hasTable('Login_log'))
        {
            Schema::create('Login_log', function (Blueprint $table) {
                $table->increments('mid');
                $table->string('user_id')->default('unknow')->comment('用户id');
                $table->string('address');
                $table->string('device')->default('unknow')->comment('设备');
                $table->string('device_type')->default('unknow')->comment('设备类型');
                $table->string('language')->default('unknow');
                $table->string('browser')->default('unknow');
                $table->string('platform')->default('unknow')->comment('操作系统');
                $table->string('action')->default('unknow');//记录登陆登出动作
                $table->boolean('result')->default('0');//记录结果
                //  $table->string('create_at')->comment('创建时间')->default(date('Y-m-d H:i:s'));
                $table->string('create_at')->comment('创建时间')->default(time());
                //$table->timestamp('created_at')->nullable();
            });
        }
    }



/**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
     //   Schema::dropIfExists('password_resets');

    }
}
