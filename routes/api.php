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

/** Маршруты по расходам */
Route::group(['prefix' => "expenses"], function () {

    /** Вывод данных одного маршрута */
    Route::post('get', [App\Http\Controllers\Expenses::class, "get"]);

    /** Сохранение расхода */
    Route::put('save', [App\Http\Controllers\Expenses::class, "save"]);
});
