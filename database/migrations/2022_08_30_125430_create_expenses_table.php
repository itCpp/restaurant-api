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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string("name")->nullable()->comment("Наименование расхода");
            $table->float('sum')->default(0)->comment("Сумма расхода");
            $table->integer('expense_type_id')->nullable()->comment("Тип расхода");
            $table->integer('expense_subtype_id')->nullable()->comment("Тип расхода");
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('expenses');
    }
};
