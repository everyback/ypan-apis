<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Floder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        if (!Schema::hasTable('folders'))
        {
            Schema::create('folders', function (Blueprint $table)
            {
                $table->increments('fid');
                $table->integer('belong')->comment('从属文件夹');
                $table->string('folder_name')->comment('文件夹名称');
                $table->unsignedInteger('creater_id')->comment('创建人id');
                $table->unsignedInteger('user_id')->comment('用户id');
               // $table->unsignedInteger('hide')->comment('是否隐藏')->default(0);
                $table->unsignedInteger('deleted')->comment('是否删除')->default(0);
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
       // Schema::dropIfExists('folders');
    }
}
