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
        Schema::create('incomes_files', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('income_id')->nullable()->comment("Связь с источником")->index();
            $table->string('name')->nullable()->comment('Имя файла для вывода');
            $table->string('file_name')->nullable()->comment('Имя файла в каталогах');
            $table->string('path')->nullable()->comment('Путь до каталога с файлом');
            $table->string('extension')->nullable()->comment('Расширение файла');
            $table->string('mime_type')->nullable()->comment('Тип файла');
            $table->bigInteger('size')->nullable()->comment('Размер файла');
            $table->string('md5_hash')->nullable()->comment('Хэш файла');
            $table->bigInteger('user_id')->nullable()->comment('Идентификатор пользователя, загрузившего файл');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['income_id', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('incomes_files');
    }
};
