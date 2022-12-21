<?php

namespace App\Http\Controllers\Employees\Salaries\Enums;

enum SheduleTypes: int
{
    case day_after_day = 1;
    case two_by_two = 2;
    case three_by_three = 3;
    case five_in_two = 4;
    case six_by_one = 5;
    case seven_through_zero = 6;

    public function counts()
    {
        return match ($this) {
            static::day_after_day => [1, 1],
            static::two_by_two => [2, 2],
            static::three_by_three => [3, 3],
            static::five_in_two => [5, 2],
            static::six_by_one => [6, 1],
            static::seven_through_zero => [7, 0],
            default => [7, 0],
        };
    }

    public function start()
    {
        return match ($this) {
            static::five_in_two => 1,
            static::six_by_one => 1,
            static::seven_through_zero => 1,
            default => null,
        };
    }
}
