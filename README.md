# Eloquent Inheritance

[![Latest Stable Version](https://poser.pugx.org/proai/eloquent-inheritance/v/stable)](https://packagist.org/packages/proai/eloquent-inheritance) [![Total Downloads](https://poser.pugx.org/proai/eloquent-inheritance/downloads)](https://packagist.org/packages/proai/eloquent-inheritance) [![Latest Unstable Version](https://poser.pugx.org/proai/eloquent-inheritance/v/unstable)](https://packagist.org/packages/proai/eloquent-inheritance) [![License](https://poser.pugx.org/proai/eloquent-inheritance/license)](https://packagist.org/packages/proai/eloquent-inheritance)

Single table inheritance is a way to emulate object-oriented inheritance in a relational database. While other frameworks like Ruby on Rails have a built-in implementation for this pattern, Laravel has not. This package aims to provide an as easy as possible implementation for Laravel.

## Installation

You can install the package via composer:

```bash
composer require proai/eloquent-inheritance
```

Please note that you need at least **PHP 8.0** and **Laravel 9** for this package.

## Usage

First you need to add the `Inheritance` trait to your root model:

```php
use ProAI\Inheritance\Inheritance;

class Pet extends Model
{
    use Inheritance;

    //
}
```

Then you can extend the root model by other models that use the same table:

```php
class Cat extends Pet
{
    //
}

class Dog extends Pet
{
    //
}
```

> Note that you need a table `pets` with a column called `type` in order to make the example above work.

Whenever a `Cat` or `Dog` model is instantiated, the attribute `type` will be set to the classname of the class (e.g. `App\Models\Cat`).

Other than that there is no magic and a `Cat` or a `Dog` model will behave just like a normal Eloquent model. You can define cat or dog specific attributes and relationships on these models. An attribute only set for dogs for example should be a nullable column on the table, so that it is set for dogs but `null` for other pets.

### Custom type column

By default the name of the type column is `type`. However, you can set a custom type column name:

```php
use ProAI\Inheritance\Inheritance;

class Pet extends Model
{
    use Inheritance;

    protected static $inheritanceColumn = 'pet_type';
}
```

### Custom type name

If you want a different name than the classname, you can use the `$inheritanceType` property:

```php
class Cat extends Pet
{
    protected static $inheritanceType = 'cat';
}
```

> Hint: The value of `$inheritanceType` can also be an enum value.

## Support

Bugs and feature requests are tracked on [GitHub](https://github.com/proai/eloquent-inheritance/issues).

## License

This package is released under the [MIT License](LICENSE).
