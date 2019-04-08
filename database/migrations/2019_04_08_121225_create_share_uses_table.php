<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShareUsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('share_uses', function (Blueprint $table) {
            $table->increments('id');
            $table->ipAddress("user_ip")->comment("用户ip");
            $table->integer("user_id")->comment("用户id");
            $table->string("share_path")->comment("对路径");
            $table->string("action")->comment("用户动作");
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
        Schema::dropIfExists('share_uses');
    }
}
