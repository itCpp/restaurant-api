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
        Schema::create('employee_processings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id');
            $table->double('from')->nullable()->comment('Переработка до');
            $table->double('to')->nullable()->comment('Переработка после');
            $table->foreignId('user_id')->nullable()->comment("Пользователь, внесший изменения");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_processings');
    }
};
