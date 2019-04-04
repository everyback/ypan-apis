<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UserFileDownload extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        if (!Schema::hasTable('user_files_download'))
        {
            Schema::create('user_files_download', function (Blueprint $table)
            {
                //$table->increments('file');
                $table->increments('mid');
                //   $table->integer('oid')->comment('mongodb objectid')->index();
               // $table->unsignedInteger('folder_id')->comment('归属文件夹id');
                $table->string('file_oid')->comment('文件oid');
                $table->string('file_name')->comment('文件名');
                $table->string('file_download_path')->comment('下载路径');
                $table->unsignedInteger('user_id')->comment('创建链接用户的id');
                $table->string('file_size')->comment('文件大小');
                $table->string('active_time')->comment('有效时间')->default(1);
                $table->timestamp('created_at')->nullable();
                //$table->boolean('deleted')->comment('是否删除')->default(0);
                //$table->timestamps();

                //   $table->timestamp('');
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
        //Schema::dropIfExists('user_files_download');
    }
}
