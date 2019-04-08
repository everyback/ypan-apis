<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNDownloadPathsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable("n_download_paths"))
            Schema::create('n_download_paths', function (Blueprint $table)
            {
                //$table->increments('file');
                $table->increments('mid');
                //   $table->integer('oid')->comment('mongodb objectid')->index();
                // $table->unsignedInteger('folder_id')->comment('归属文件夹id');
                //            $table->string('file_oid')->comment('文件oid');
                $table->json('download_thing')->comment('下载的文件，文件夹列表');
                $table->string('path')->comment('下载路径')->index();
                //                $table->unsignedInteger('user_id')->comment('创建链接用户的id');
                $table->string('show_name')->comment('下载的总文件名称')->default("files");
                $table->json('download_folders')->comment('分享文件列表');
                $table->json('download_files')->comment('分享文件夹列表');
                $table->boolean('invalidation')->default(false)->comment('失效');
                $table->integer('sum')->comment('数量')->default(0);
                $table->integer('user_id')->comment('创建链接用户的id');
                $table->string('active_time')->comment('有效时间')->default(1);
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
        Schema::dropIfExists('n_download_paths');
    }
}
