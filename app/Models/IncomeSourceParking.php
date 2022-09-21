<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IncomeSourceParking extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'source_id',
        'parking_place',
        'car',
        'car_number',
        'date_from',
        'date_to',
        'price',
        'owner_name',
        'owner_phone',
        'comment',
    ];
}
