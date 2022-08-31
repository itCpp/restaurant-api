<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cashbox_transactions', function (Blueprint $table) {
            $table->bigInteger('user_id')->nullable()->comment("Идентификатор пользователя, создавшего строку")->after('date');
            $table->index(['is_expense', 'deleted_at'], 'expenses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cashbox_transactions', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->dropIndex('expenses');
        });
    }
};
