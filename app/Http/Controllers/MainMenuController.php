<?php

namespace App\Http\Controllers;

use App\Models\MainMenu;
use Illuminate\Http\Request;

class MainMenuController extends Controller
{
    /**
     * Выводит все пункты меню
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $data = MainMenu::query()
            ->whereIsActive(true)
            ->orderBy('sorting')
            ->get()
            ->map(fn ($item) => [
                'title' => $item->title,
                'url' => $item->url,
                'icon' => $item->icon,
            ])
            ->toArray();

        return response()->json($data);
    }
}
