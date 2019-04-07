<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangesaveShareTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('share', function (Blueprint $table) {
            //
            $table->json('share_folders')->comment('分享文件列表');
            $table->json('share_files')->comment('分享文件夹列表');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('share', function (Blueprint $table) {
            //
            $table->dropColumn('share_thing');
        });
    }
}
