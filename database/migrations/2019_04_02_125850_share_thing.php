<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ShareThing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        if (!Schema::hasTable("share_list"))
            Schema::create('share_list', function (Blueprint $table)
            {
                //$table->increments('file');
                $table->increments('mid');
                //   $table->integer('oid')->comment('mongodb objectid')->index();
                // $table->unsignedInteger('folder_id')->comment('归属文件夹id');
                //            $table->string('file_oid')->comment('文件oid');
                $table->json('share_thing')->comment('分享文件，文件夹列表');
                $table->string('path')->comment('主路径');
//                $table->unsignedInteger('user_id')->comment('创建链接用户的id');
                $table->string('ids')->comment('文件/文件夹id');
                $table->boolean('type')->default(false)->comment('是文件');
                $table->boolean('invalidation')->default(false)->comment('失效');
//                $table->unsignedInteger('user_id')->comment('创建链接用户的id');

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
    }
}
