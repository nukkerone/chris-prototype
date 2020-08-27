<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('unassigned')->default(true);
            $table->decimal('wallet', 8, 2)->default(0);
        });

        Schema::create('user_refs', function (Blueprint $table){
            $table->id();
            $table->unsignedBigInteger('user_id');

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('user_refs');

        Schema::table('users', function (Blueprint $table) {
            $table->removeColumn('unassigned');
            $table->removeColumn('wallet');
        });
    }
}
