<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeSource extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'part_id',
        'name',
        'inn',
        'contact_person',
        'contact_number',
        'space',
        'cabinet',
        'price',
        'date',
        'is_free',
        'settings',
    ];

    /**
     * Атрибуты, которые необходимо преобразовать
     * 
     * @var array
     */
    protected $casts = [
        'date' => "date:Y-m-d",
        'settings' => AsCollection::class,
        'is_free' => "boolean",
    ];
}
