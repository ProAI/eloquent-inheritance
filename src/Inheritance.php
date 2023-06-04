<?php

namespace ProAI\Inheritance;

use Illuminate\Support\Str;

trait Inheritance
{
    /**
     * Defines the column name for use with single table inheritance.
     *
     * @var string
     */
    public static $inheritanceColumn = 'type';

    /**
     * Defines the type name for use with single table inheritance.
     *
     * @var mixed
     */
    public static $inheritanceType;

    /**
     * Bootstrap the inheritance trait.
     *
     * @return void
     */
    protected static function bootInheritance()
    {
        if (! static::modelInherited()) {
            return;
        }

        static::addGlobalScope(function ($query) {
            $query->where(static::$inheritanceColumn, static::getInheritanceType());
        });

        static::creating(function ($model) {
            $model->{static::$inheritanceColumn} = static::getInheritanceType();
        });
    }

    /**
     * Check if model is inherited.
     *
     * @return bool
     */
    protected static function modelInherited()
    {
        return self::class !== static::class;
    }

    /**
     * Get the column name for use with single table inheritance.
     *
     * @return mixed
     */
    protected static function getInheritanceType()
    {
        if (isset(static::$inheritanceType)) {
            return static::$inheritanceType;
        }

        return static::$inheritanceType = static::class;
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table ?? Str::snake(Str::pluralStudly(class_basename($this->getRootClass())));
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return Str::snake(class_basename($this->getRootClass())).'_'.$this->getKeyName();
    }

    /**
     * Get the class name of the root class.
     *
     * @return string
     */
    protected function getRootClass()
    {
        return self::class;
    }
}
