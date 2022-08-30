<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class User extends Controller
{
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
            'user' => Auth::user(),
            'token' => $token->plainTextToken,
        ]);
    }
}
