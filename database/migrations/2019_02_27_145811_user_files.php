<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UserFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        if (!Schema::hasTable('user_files'))
        {
            Schema::create('user_files', function (Blueprint $table)
        {
            //$table->increments('file');
            $table->increments('mid');
            //   $table->integer('oid')->comment('mongodb objectid')->index();
            $table->unsignedInteger('folder_id')->comment('归属文件夹id');
            $table->string('file_oid')->comment('文件oid');
            $table->string('file_name')->comment('文件名');
            $table->string('file_type')->comment('文件类型');
            $table->unsignedInteger('updater_id')->comment('归属用户的id');
            $table->string('file_size')->comment('文件大小');

            //                $table->string('create_at')->comment('上传时间');
            //                $table->string('update_at')->comment('更新时间');
            $table->boolean('deleted')->comment('是否删除')->default(0);
            $table->timestamps();

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
       Schema::dropIfExists('user_files');
    }
}
