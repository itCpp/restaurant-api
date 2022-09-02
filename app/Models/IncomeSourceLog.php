<?php

namespace App\Models;

use App\Casts\IncomeSourceCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class IncomeSourceLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'source_id',
        'source_data',
        'request_data',
        'change_date',
    ];

    /**
     * Атрибуты, которые необходимо преобразовать
     * 
     * @var array
     */
    protected $casts = [
        'change_date' => "date:Y-m-d",
        'source_data' => IncomeSourceCast::class,
        'request_data' => "array",
    ];

    /**
     * Логирование изменений
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\IncomeSource $row
     * @return \App\Models\IncomeSourceLog
     */
    public static function log(Request $request, IncomeSource $row)
    {
        return static::create([
            'user_id' => optional($request->user())->id,
            'source_id' => $row->id ?? null,
            'source_data' => $row,
            'request_data' => $request->all(),
            'change_date' => now(),
        ]);
    }
}
