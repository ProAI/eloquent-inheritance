<?php

namespace ProAI\Inheritance;

use Illuminate\Support\Str;
use Exception;

trait Inheritance
{
    /**
     * Defines the column name for use with single table inheritance.
     *
     * @var string
     */
    public static $inheritanceColumn = 'type';

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $type = $attributes[static::$inheritanceColumn] ?? null;

        if (static::isRootModel() && $type) {
            [$one, $caller] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            if (! isset($caller['class']) || ! $caller['class'] || ! $this instanceof $caller['class']) {
                throw new Exception('Model cannot be instantiated. Please use '.static::class.'::new instead.');
            }
        }

        parent::__construct($attributes);
    }

    /**
     * Bootstrap the inheritance trait.
     *
     * @return void
     */
    protected static function bootInheritance()
    {
        if (static::isRootModel()) {
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
     * Check if model is root model.
     *
     * @return bool
     */
    protected static function isRootModel()
    {
        return self::class === static::class;
    }

    /**
     * Get the column name for use with single table inheritance.
     *
     * @return mixed
     */
    protected static function getInheritanceType()
    {
        $class = static::class;
        $flippedMap = array_flip(static::$inheritanceMap ?? []);

        if (isset($flippedMap[$class])) {
            $type = $flippedMap[$class];
        } else {
            $type = $class;
        }

        return $type;
    }

    /**
     * Create a new instance of the model by type.
     *
     * @param  array  $attributes
     * @return static
     */
    public static function new($attributes = [])
    {
        $class = static::getChildClass($attributes);

        if ($class) {
            return new $class($attributes);
        }

        return new static($attributes);
    }

    /**
     * Create a new instance of the model by type.
     *
     * @param  array  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $class = static::getChildClass($attributes);

        // This method just provides a convenient way for us to generate fresh model
        // instances of this current model. It is particularly useful during the
        // hydration of new objects via the Eloquent query builder instances.
        $model = $class ? new $class : new static;

        $model->exists = $exists;

        $model->setConnection(
            $this->getConnectionName()
        );

        $model->setTable($this->getTable());

        $model->mergeCasts($this->casts);

        $model->fill((array) $attributes);

        return $model;
    }

    /**
     * Create a new model instance that is existing.
     *
     * @param  array  $attributes
     * @param  string|null  $connection
     * @return static
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $attributes = (array) $attributes;

        $type = $attributes[static::$inheritanceColumn] ?? null;

        $model = $this->newInstance([static::$inheritanceColumn => $type], true);

        $model->setRawAttributes($attributes, true);

        $model->setConnection($connection ?: $this->getConnectionName());

        $model->fireModelEvent('retrieved', false);

        return $model;
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

    /**
     * Get the class name of the child class.
     *
     * @param  array  $attributes
     * @return ?string
     */
    protected static function getChildClass($attributes)
    {
        if (! static::isRootModel()) {
            return null;
        }

        if (! isset($attributes[static::$inheritanceColumn])) {
            return null;
        }

        $type = $attributes[static::$inheritanceColumn];

        if (! isset(static::$inheritanceMap)) {
            return $type;
        }

        return static::$inheritanceMap[$type] ?? $type;
    }
}
