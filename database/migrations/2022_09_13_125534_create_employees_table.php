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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('pin')->nullable();
            $table->bigInteger('employee_otdel_id')->nullable()->comment('Идентификатор отдела');
            $table->string('surname')->nullable()->comment('Фамилия');
            $table->string('name')->nullable()->comment('Имя');
            $table->string('middle_name')->nullable()->comment('Отчество');
            $table->string('job_title')->nullable()->comment('Должность');
            $table->date('birth_day')->nullable()->comment('Дата рождения');
            $table->string('birth_place')->nullable()->comment('Место рождения');
            $table->string('education')->nullable()->comment('Образование');
            $table->string('military')->nullable()->comment('Отношение');
            $table->string('seminal_position')->nullable()->comment('Семеной положение');
            $table->string('phone')->nullable()->comment('Номер телефона');
            $table->string('phone_work')->nullable()->comment('Телефон рабочий');
            $table->string('phone_home')->nullable()->comment('Телефон домашний');
            $table->string('telegram_id')->nullable()->comment('Telegram');
            $table->string('email')->nullable();
            $table->string('address')->nullable()->comment('Фактический адрес');
            $table->string('passport_number')->nullable()->comment('Серия и номер');
            $table->date('passport_date')->nullable()->comment('Дата выдачи');
            $table->string('passport_place')->nullable()->comment('Место выдачи');
            $table->string('passport_address')->nullable()->comment('Адрес прописки');
            $table->string('snils_number')->nullable()->comment('СНИЛС');
            $table->string('inn_number')->nullable()->comment('ИНН');
            $table->string('military_id')->nullable()->comment('Воаенный билет');
            $table->string('driver_license')->nullable()->comment('Водительское удостоверение');
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
        Schema::dropIfExists('employees');
    }
};
