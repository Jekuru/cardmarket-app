<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardssalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cardssales', function (Blueprint $table) {
            $table->id();
            $table->string('card_name');
            $table->integer('quantity');
            $table->float('price');
            $table->string('user_users');
            $table->timestamps();           
        });

        Schema::table('cardssales', function(Blueprint $table){
            $table->foreign('card_name')->references('name')->on('cards');
            $table->foreign('user_users')->references('user')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cardssales');
    }
}
