<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Share extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        if (!Schema::hasTable("share"))
        Schema::create('share', function (Blueprint $table)
        {
            //$table->increments('file');
            $table->increments('mid');
            //   $table->integer('oid')->comment('mongodb objectid')->index();
            // $table->unsignedInteger('folder_id')->comment('归属文件夹id');
//            $table->string('file_oid')->comment('文件oid');
//            $table->json('share_thing')->comment('分享文件，文件夹列表');
//            $table->string('file_name')->comment('文件名');
            $table->string('share_path')->comment('分享链接')->index();
            $table->unsignedInteger('user_id')->comment('创建链接用户的id');
            $table->unsignedInteger('sum')->comment('数量');
            $table->string('private')->comment('私有')->default(0);
            $table->string('code')->comment('提取码')->default(-1);
            $table->string('active_time')->comment('有效时间')->default(-1);
            $table->boolean('invalidation')->comment('失效')->default(0);
            $table->timestamp('created_at')->nullable();
            //   $table->timestamp('');
        });



    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        //Schema::dropIfExists('share');
    }
}
