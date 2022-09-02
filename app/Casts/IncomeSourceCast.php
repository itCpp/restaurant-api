<?php

namespace App\Casts;

use App\Models\IncomeSource;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class IncomeSourceCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function get($model, string $key, $value, array $attributes)
    {
        $row = new IncomeSource;

        foreach (json_decode($value, true) as $key => $value) {
            $row->{$key} = $value;
        }

        return $row;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return mixed
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if ($value instanceof IncomeSource)
            $value = $value->toArray();

        return json_encode($value);
    }
}
