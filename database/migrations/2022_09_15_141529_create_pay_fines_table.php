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
        Schema::create('pay_fines', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('source_id')->nullable()->index();
            $table->float('sum')->default(0)->comment('Сумма пени или оплаты');
            $table->float('from_sum')->default(0)->comment('Сумма от которой насчитана пеня');
            $table->float('fine_percent')->default(0)->comment('Процент пени');
            $table->boolean('is_repay')->default(false)->comment('Строка является оплатой пени');
            $table->bigInteger('user_id')->nullable()->comment('Идентификатор пользователя, внесшего информацию');
            $table->date('date')->nullable();
            $table->timestamps();
            $table->index(['source_id', 'is_repay']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pay_fines');
    }
};
