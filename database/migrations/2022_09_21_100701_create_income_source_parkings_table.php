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
        Schema::create('income_source_parkings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('source_id')->nullable()->index();
            $table->string('parking_place')->nullable()->comment('Машиноместо');
            $table->string('car')->nullable()->comment('Марка и модель авто');
            $table->string('car_number')->nullable()->comment('Гос. номер авто');
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->float('price')->default(0);
            $table->string('owner_name')->nullable()->comment('Имя владельца');
            $table->string('owner_phone')->nullable()->comment('Телефон владельца');
            $table->text('comment')->nullable();
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
        Schema::dropIfExists('income_source_parkings');
    }
};
