<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', [App\Http\Controllers\User::class, "user"]);

/** Маршрутизация сотрудников */
Route::group(['prefix' => "user"], function () {

    /** Авторизация */
    Route::post('auth', [App\Http\Controllers\User::class, "login"]);
    /** Выход */
    Route::get('logout', [App\Http\Controllers\User::class, "logout"])->middleware('auth:sanctum');
});

/** Ссылка для скачивания файла */
Route::get("download/file/{name}", [App\Http\Controllers\Expenses\Files::class, "download"]);

Route::middleware('auth:sanctum')->group(function () {

    /** Главная страница */
    Route::get("main", [App\Http\Controllers\Main::class, "index"]);

    /** Маршруты по расходам */
    Route::group(['prefix' => "expenses"], function () {

        /** Вывод расхода */
        Route::post('/', [App\Http\Controllers\Expenses::class, "index"]);

        /** Вывод данных одного маршрута */
        Route::post('get', [App\Http\Controllers\Expenses::class, "get"]);

        /** Сохранение расхода */
        Route::put('save', [App\Http\Controllers\Expenses::class, "save"]);

        /** Удаление строки */
        Route::post('drop', [App\Http\Controllers\Expenses::class, "drop"]);

        /** Вывод фиксированных пунктов наименования */
        Route::post('types', [App\Http\Controllers\Expenses\Types::class, "getSubTypesList"]);

        /** Список файлов по расходу */
        Route::post('files', [App\Http\Controllers\Expenses\Files::class, "index"]);

        /** Загрузка нового файла */
        Route::post('file/upload', [App\Http\Controllers\Expenses\Files::class, "upload"]);
    });

    /** Доходы */
    Route::group(['prefix' => "incomes"], function () {

        /** Вывод всех данных */
        Route::get("/", [App\Http\Controllers\Incomes::class, "index"]);

        /** Вывод строк оплаты */
        Route::post("view", [App\Http\Controllers\Incomes::class, "view"]);

        /** Данные для внесения строки дохода */
        Route::post("add", [App\Http\Controllers\Incomes::class, "add"]);

        /** Save data income row */
        Route::post('save', [App\Http\Controllers\Incomes::class, "save"]);

        /** Выводит список помещений */
        Route::post('sources', [App\Http\Controllers\Incomes\Sources::class, "index"]);

        /** Данные источника дохода */
        Route::get('source/get', [App\Http\Controllers\Incomes\Sources::class, "get"]);

        /** Сохранение данных помещения */
        Route::put('source/save', [App\Http\Controllers\Incomes\Sources::class, "save"]);
    });
});
