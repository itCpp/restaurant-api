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
            $table->date('period_start')->nullable()->comment('Начало затронутого периода')->after('month');
            $table->date('period_stop')->nullable()->comment('Окончание затронутого периода')->after('period_start');
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
            $table->dropColumn(['period_start', 'period_stop']);
        });
    }
};
