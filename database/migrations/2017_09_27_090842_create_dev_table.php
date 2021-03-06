<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('dev_id')->unsigned();
            $table->integer('proj_id')->unsigned();
            $table->foreign('dev_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
            $table->foreign('proj_id')
                ->references('id')
                ->on('projects')
                ->onDelete('cascade');
            $table->string('date_created')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('devs');
    }
}
