<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Log extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'table_name',
        'row_id',
        'user_id',
        'model_data',
        'request_data',
        'ip',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'model_data' => 'array',
        'request_data' => 'array',
    ];

    /**
     * Write log
     * 
     * @param  mixed $model
     * @param  \Illuminate\Http\Request $request
     * @return \App\Models\Log
     */
    public static function write($model, Request $request)
    {
        try {
            return static::create([
                'table_name' => optional($model)->getTable(),
                'row_id' => $model->id ?? null,
                'user_id' => optional($request->user())->id,
                'model_data' => $model,
                'request_data' => $request->all(),
                'ip' => $request->ip(),
            ]);
        } catch (Exception) {
            return new static;
        }
    }
}
