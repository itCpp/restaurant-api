<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

        /** Полномочия токена */
        $abilities = ['*'];

        $token_name = $request->ip() . " - " . $request->userAgent();

        $token = $request->user()->createToken(
            Str::limit($token_name, 255, "..."),
            $abilities,
            $request->remember ? null : now()->endOfDay(),
        );

        return response()->json([
            'message' => "Добро пожаловать, {$request->user()->name}!",
            'user' => $this->userData($request),
            'token' => $token->plainTextToken,
        ]);
    }

    /**
     * Выход пользователя
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        if ($token_id = $request->user()->currentAccessToken()->id ?? null) {
            $request->user()->tokens()->whereId($token_id)->delete();
        }

        return response()->json([
            'message' => "До скорой встречи!",
            'tokenId' => $token_id,
        ]);
    }
}
