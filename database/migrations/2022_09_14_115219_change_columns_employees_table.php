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
        Schema::table('employees', function (Blueprint $table) {
            $table->index('pin');
            $table->json('personal_data')->nullable()->comment('Анкетные данные')->after('email');
            $table->dropColumn([
                'birth_day',
                'birth_place',
                'education',
                'military',
                'seminal_position',
                'phone_work',
                'phone_home',
                'address',
                'passport_number',
                'passport_date',
                'passport_place',
                'passport_address',
                'snils_number',
                'inn_number',
                'military_id',
                'driver_license',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['pin']);
            $table->dropColumn('personal_data');
            $table->date('birth_day')->nullable()->comment('Дата рождения')->after('job_title');
            $table->string('birth_place')->nullable()->comment('Место рождения')->after('birth_day');
            $table->string('education')->nullable()->comment('Образование')->after('birth_place');
            $table->string('military')->nullable()->comment('Отношение')->after('education');
            $table->string('seminal_position')->nullable()->comment('Семеной положение')->after('military');
            $table->string('phone_work')->nullable()->comment('Телефон рабочий')->after('phone');
            $table->string('phone_home')->nullable()->comment('Телефон домашний')->after('phone_work');
            $table->string('address')->nullable()->comment('Фактический адрес')->after('email');
            $table->string('passport_number')->nullable()->comment('Серия и номер')->after('address');
            $table->date('passport_date')->nullable()->comment('Дата выдачи')->after('passport_number');
            $table->string('passport_place')->nullable()->comment('Место выдачи')->after('passport_date');
            $table->string('passport_address')->nullable()->comment('Адрес прописки')->after('passport_place');
            $table->string('snils_number')->nullable()->comment('СНИЛС')->after('passport_address');
            $table->string('inn_number')->nullable()->comment('ИНН')->after('snils_number');
            $table->string('military_id')->nullable()->comment('Воаенный билет')->after('inn_number');
            $table->string('driver_license')->nullable()->comment('Водительское удостоверение')->after('military_id');
        });
    }
};
