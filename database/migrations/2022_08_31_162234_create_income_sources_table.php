<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
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
        Schema::create('income_sources', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('part_id')->nullable()->index();
            $table->string('name')->nullable()->comment("Наименование организации");
            $table->bigInteger('inn')->nullable()->comment("ИНН организации");
            $table->string('contact_person')->nullable()->comment("ФИО контактного лица");
            $table->string('contact_number')->nullable()->comment("Телефон для связи");
            $table->float('space')->nullable()->comment("Площадь помещения");
            $table->string('cabinet')->nullable()->comment("Наименование кабинета");
            $table->float('price')->nullable()->comment("Стоимость за 1 кв.м");
            $table->date('date')->nullable()->comment("Дата начала аренды");
            $table->json('settings')->default(new Expression('(JSON_ARRAY())'))->comment("Прочие настройки");
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
        Schema::dropIfExists('income_sources');
    }
};
