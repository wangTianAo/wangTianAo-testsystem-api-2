<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOpTable extends Migration
{

    public function up()
    {
        Schema::create('Op', function(Blueprint $table) {
            $table->increments('id');
            // Schema declaration
            // Constraints declaration

        });
    }

    public function down()
    {
        Schema::drop('Op');
    }
}
