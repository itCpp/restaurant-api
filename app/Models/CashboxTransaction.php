<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashboxTransaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Атрибуты, которые необходимо преобразовать
     * 
     * @var array
     */
    protected $casts = [
        'is_income' => "boolean",
        'is_expense' => "boolean",
    ];
}
