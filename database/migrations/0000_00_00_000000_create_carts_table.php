<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCartsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create(config('cart.database.table'), function (Blueprint $table) {
            $table->integer('user_id')->unsigned()->unique();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->json('content');
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::drop(config('cart.database.table'));
    }
}