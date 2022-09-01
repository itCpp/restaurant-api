<?php

use Brick\Math\BigInteger;
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
            $table->integer('type_pay')->nullable()->comment("Тип платежа")->after('sum');
            $table->bigInteger('income_part_id')->nullable()->comment("Идентификатор раздела здания")->after('expense_subtype_id')->index();
            $table->bigInteger('income_source_id')->nullable()->comment("Идентификатор помещения")->after('income_part_id')->index();
            $table->string('month', 7)->nullable()->after('date');

            $table->index(['is_income', 'deleted_at'], 'is_income');
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
            $table->dropColumn(['type_pay', 'income_part_id', 'income_source_id', 'month']);
            $table->dropIndex('is_income');
        });
    }
};
