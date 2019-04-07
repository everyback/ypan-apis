<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddShowNameShareTable extends Migration
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
            $table->string('show_name')->comment('');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

 /*   public function change()
    {
        Schema::table('share', function (Blueprint $table)
        {

        });
    }*/

    public function down()
    {
        Schema::table('share', function (Blueprint $table) {
            //
        });
    }
}
