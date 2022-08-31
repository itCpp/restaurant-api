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
});

Route::middleware('auth:sanctum')->group(function () {

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
    });

    /** Доходы */
    Route::group(['prefix' => "incomes"], function () {

        /** Вывод всех данных */
        Route::get("/", [App\Http\Controllers\Incomes::class, "index"]);
    });
});
