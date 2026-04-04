<?php

namespace ProAI\Inheritance\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use ProAI\Inheritance\Inheritance;

class Vehicle extends Model
{
    use Inheritance;

    /** @var array<string, string> */
    public static array $inheritanceMap = [
        'car' => Car::class,
        'truck' => Truck::class,
    ];

    protected $fillable = ['make', 'type'];
}
