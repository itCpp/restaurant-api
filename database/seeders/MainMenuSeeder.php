<?php

namespace Database\Seeders;

use App\Models\MainMenu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MainMenuSeeder extends Seeder
{
    /**
     * Пункты меню
     * 
     * @var array
     */
    protected $points = [
        ['title' => "Строение 1", 'url' => "/income/1", 'icon' => "building", 'sorting' => 1],
        ['title' => "Строение 2", 'url' => "/income/2", 'icon' => "building", 'sorting' => 2],
        ['title' => "Парковка", 'url' => "/income/parking", 'icon' => "car", 'sorting' => 3],
        ['title' => "Юридические адреса", 'url' => "/income/address", 'icon' => "point", 'sorting' => 4],
        ['title' => "Раcходы", 'url' => "/expenses", 'icon' => "cart", 'sorting' => 5],
        ['title' => "Сотрудники", 'url' => "/employees", 'icon' => "users", 'sorting' => 6],
        ['title' => "Зарплата", 'url' => "/salary", 'icon' => "money", 'sorting' => 7],
        ['title' => "Касса", 'url' => "/cashbox", 'icon' => "calculator", 'sorting' => 8],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->points as $point) {
            MainMenu::create($point);
        }
    }
}
