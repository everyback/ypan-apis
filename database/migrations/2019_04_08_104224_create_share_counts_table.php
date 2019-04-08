<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShareCountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('share_counts', function (Blueprint $table) {
//            $table->increments('id');
            $table->string('share_path')->comment("外键：分享路径");
            $table->foreign('share_path')->references('share_path')->on('share')->onUpdate('CASCADE');
            $table->integer('read')->comment("阅读量")->default(0);
            $table->integer('resave')->comment("转存量")->default(0);
            $table->integer('download')->comment("下载量")->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('share_counts');
    }
}
