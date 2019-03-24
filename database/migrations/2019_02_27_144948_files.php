<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Files extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //可能跟mongodb 有重复
        if (!Schema::hasTable('files'))
        {
            Schema::create('files', function (Blueprint $table)
            {
                //$table->increments('file');
                $table->string('oid')->comment('mongodb objectid')->primary();
                $table->string('first_name')->comment('首次上传文件名');
                $table->string('file_type')->comment('文件类型');
                $table->unsignedInteger('first_updater_id')->comment('首次上传用户的id');
                $table->string('md5')->comment('文件md5');
                $table->string('sha256')->comment('文件sha256');
               // $table->string('sha1')->comment('文件sha1');
                $table->string('slice_sha1')->comment('文件头10MBsha1');
                $table->string('crc32')->comment('文件crc32');
                $table->string('file_size')->comment('文件大小');
                //$table->primary('oid');
                // $table->unsignedInteger('hide')->comment('是否隐藏')->default(0);
               // $table->string('create_at')->comment('上传时间')->default(time());
                //$table->unsignedInteger('deleted')->comment('是否删除')->default(0);
                $table->timestamps();

                //   $table->timestamp('');
            });
        }
        else
        {
            Schema::table('files', function (Blueprint $table)
            {
                $table->string('slice_sha1')->comment('文件头10MBsha1');
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
       // Schema::dropIfExists('files');
    }
}
