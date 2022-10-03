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
        Schema::create('income_source_additional_service', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('income_source_id');
            $table->bigInteger('additional_service_id');
            $table->float('sum', 10, 2)->default(0);
            $table->integer('type_pay')->nullable();
            $table->date('start_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            // $table->unique(['income_source_id', 'additional_service_id'], 'income_service_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('income_source_additional_service');
    }
};
