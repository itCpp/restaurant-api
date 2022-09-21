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
            $table->bigInteger('income_source_parking_id')->nullable()->comment('Идентификатор машиноместа')->after('income_source_id');
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
            $table->dropColumn('income_source_parking_id');
        });
    }
};
