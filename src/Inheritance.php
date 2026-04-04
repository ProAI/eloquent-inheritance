<?php

namespace ProAI\Inheritance;

use Exception;
use Illuminate\Database\Eloquent\Model;
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
     * Create a new Eloquent model instance.
     *
     * @param  array<string, mixed>  $attributes
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
    protected static function bootInheritance(): void
    {
        if (static::isRootModel()) {
            return;
        }

        static::addGlobalScope(function ($query) {
            $query->where($query->getModel()->getTable().'.'.static::$inheritanceColumn, static::getInheritanceType());
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
    protected static function isRootModel(): bool
    {
        return static::class === self::class;
    }

    /**
     * Get the column name for use with single table inheritance.
     *
     * @return string
     */
    protected static function getInheritanceType(): string
    {
        $class = static::class;
        $map = static::$inheritanceMap ?? [];

        if (is_array($map)) {
            foreach ($map as $type => $mappedClass) {
                if ($mappedClass === $class) {
                    return (string) $type;
                }
            }
        }

        return $class;
    }

    /**
     * Create a new instance of the model by type.
     *
     * @param  array<string, mixed>  $attributes
     * @return static
     */
    public static function new(array $attributes = []): static
    {
        $class = static::getChildClass($attributes);

        if ($class) {
            $instance = new $class($attributes);
            if ($instance instanceof static) {
                return $instance;
            }
        }

        return new (static::class)($attributes);
    }

    /**
     * Create a new instance of the model by type.
     *
     * @param  array<string, mixed>  $attributes
     * @param  bool  $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false): static
    {
        $class = static::getChildClass($attributes);

        $model = new (static::class);

        if ($class) {
            $child = new $class;
            if ($child instanceof static) {
                $model = $child;
            }
        }

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
     * @param  array<string, mixed>  $attributes
     * @param  string|null  $connection
     * @return static
     */
    public function newFromBuilder($attributes = [], $connection = null): static
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
    public function getTable(): string
    {
        if (isset($this->table)) {
            return $this->table;
        }

        if (method_exists($this, 'getInheritanceTable')) {
            return $this->getInheritanceTable();
        }

        return Str::snake(Str::pluralStudly(class_basename($this->getRootClass())));
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey(): string
    {
        return Str::snake(class_basename($this->getRootClass())).'_'.$this->getKeyName();
    }

    /**
     * Get the class name for polymorphic relations.
     *
     * @return string
     */
    public function getMorphClass(): string
    {
        $class = $this->getRootClass();

        if ($class === static::class) {
            return parent::getMorphClass();
        }

        $instance = new $class;
        if ($instance instanceof Model) {
            return $instance->getMorphClass();
        }

        return parent::getMorphClass();
    }

    /**
     * Get the class name of the root class.
     *
     * @return string
     */
    protected function getRootClass(): string
    {
        return self::class;
    }

    /**
     * Get the class name of the child class.
     *
     * @param  array<string, mixed>  $attributes
     * @return ?string
     */
    protected static function getChildClass(array $attributes): ?string
    {
        if (! static::isRootModel()) {
            return null;
        }

        if (! isset($attributes[static::$inheritanceColumn])) {
            return null;
        }

        $type = (string) $attributes[static::$inheritanceColumn];
        $map = static::$inheritanceMap ?? [];

        if (! is_array($map)) {
            return $type;
        }

        return isset($map[$type]) ? (string) $map[$type] : $type;
    }
}
