<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomeSource extends Model
{
    use HasFactory, SoftDeletes;

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
        'date_to',
        'is_free',
        'is_parking',
        'is_internet',
        'is_overdue',
        'settings',
    ];

    /**
     * Атрибуты, которые необходимо преобразовать
     * 
     * @var array
     */
    protected $casts = [
        'date' => "date:Y-m-d",
        'is_free' => "boolean",
        'is_parking' => "boolean",
        'is_internet' => "boolean",
        'is_overdue' => "boolean",
        'settings' => AsCollection::class,
    ];
}
