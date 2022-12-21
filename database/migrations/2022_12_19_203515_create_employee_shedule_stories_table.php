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
        Schema::create('employee_shedule_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable();
            $table->integer('shedule_type')->nullable();
            $table->date('shedule_start')->nullable();
            $table->foreignId('user_id')->nullable()->comment('Пользователь, внесший изменения');
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
        Schema::dropIfExists('employee_shedule_stories');
    }
};
