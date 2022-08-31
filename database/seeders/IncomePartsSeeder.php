<?php

namespace Database\Seeders;

use App\Models\IncomePart;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IncomePartsSeeder extends Seeder
{
    /**
     * Данные типов
     * 
     * @var array
     */
    protected $expense_types = [
        ['id' => 1, 'name' => "1 этаж", 'comment' => "Общая пл. 141 кв.м"],
        ['id' => 2, 'name' => "2 этаж", 'comment' => "Общая пл. 190 кв.м"],
        ['id' => 3, 'name' => "3 этаж", 'comment' => "Общая пл. 141 кв.м"],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->expense_types as $row) {
            IncomePart::create($row);
        }
    }
}
