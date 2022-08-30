<?php

namespace Database\Seeders;

use App\Models\ExpenseType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpenseTypesSeeder extends Seeder
{
    /**
     * Данные типов
     * 
     * @var array
     */
    protected $expense_types = [
        ['id' => 1, 'name' => "Персонал"],
        ['id' => 2, 'name' => "Коммуналка"],
        ['id' => 3, 'name' => "Текущие"],
    ];
    
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->expense_types as $row) {
            ExpenseType::create($row);
        }
    }
}
