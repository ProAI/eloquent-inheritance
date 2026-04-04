<?php

namespace ProAI\Inheritance\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use ProAI\Inheritance\Inheritance;

class Animal extends Model
{
    use Inheritance;

    protected $fillable = ['name', 'type'];
}
