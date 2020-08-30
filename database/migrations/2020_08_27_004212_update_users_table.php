<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kalnoy\Nestedset\NestedSet;

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
            $table->decimal('wallet', 8, 2)->default(100);
        });

        Schema::create('user_refs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();

            NestedSet::columns($table);

            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::create('flowers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('current_week')->nullable()->default(null);
            $table->decimal('enter_payment', 8, 2)->default(100);
            $table->decimal('accumulated_payments', 8, 2)->default(0);
            $table->unsignedBigInteger('root_user_ref_id')->nullable();

            $table->foreign('root_user_ref_id')->references('id')->on('user_refs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('flowers');

        Schema::drop('user_refs');

        Schema::table('users', function (Blueprint $table) {
            $table->removeColumn('unassigned');
            $table->removeColumn('wallet');
        });
    }
}
