<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class User extends Controller
{
    /**
     * Формирует данные пользователя
     * 
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function userData(Request $request)
    {
        return $request->user();
    }

    /**
     * Выводит данные пользователя
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return response()->json(
            $this->userData($request)
        );
    }

    /**
     * Авторизация пользователя
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = [
            'email' => $request->input('login'),
            'password' => $request->input('password'),
        ];

        if (!Auth::attempt($credentials))
            return response()->json(['message' => "Неверный логин или пароль"], 400);

        $token = $request->user()->createToken(now()->format("YmdHis"));

        return response()->json([
            'message' => "Добро пожаловать, {$request->user()->name}!",
            'user' => $this->userData($request),
            'token' => $token->plainTextToken,
        ]);
    }
}
