<?php

use ProAI\Inheritance\Tests\Fixtures\Animal;
use ProAI\Inheritance\Tests\Fixtures\Car;
use ProAI\Inheritance\Tests\Fixtures\Cat;
use ProAI\Inheritance\Tests\Fixtures\Dog;
use ProAI\Inheritance\Tests\Fixtures\Truck;
use ProAI\Inheritance\Tests\Fixtures\Vehicle;

// --- Table name ---

test('root model uses pluralized snake-case class name as table', function () {
    expect((new Animal)->getTable())->toBe('animals');
});

test('child model shares root model table', function () {
    expect((new Dog)->getTable())->toBe('animals');
    expect((new Cat)->getTable())->toBe('animals');
});

test('child model with inheritanceMap shares root model table', function () {
    expect((new Car)->getTable())->toBe('vehicles');
    expect((new Truck)->getTable())->toBe('vehicles');
});

// --- Foreign key ---

test('root model foreign key is based on root class name', function () {
    expect((new Animal)->getForeignKey())->toBe('animal_id');
});

test('child model foreign key is based on root class name', function () {
    expect((new Dog)->getForeignKey())->toBe('animal_id');
});

// --- isRootModel ---

test('root model is identified as root', function () {
    expect(Animal::isRootModel())->toBeTrue();
    expect(Vehicle::isRootModel())->toBeTrue();
});

test('child model is not identified as root', function () {
    expect(Dog::isRootModel())->toBeFalse();
    expect(Car::isRootModel())->toBeFalse();
});

// --- getInheritanceType ---

test('child model without map uses fully-qualified class name as type', function () {
    expect(Dog::getInheritanceType())->toBe(Dog::class);
    expect(Cat::getInheritanceType())->toBe(Cat::class);
});

test('child model with inheritanceMap uses mapped key as type', function () {
    expect(Car::getInheritanceType())->toBe('car');
    expect(Truck::getInheritanceType())->toBe('truck');
});

// --- new() factory ---

test('new() on root model without type returns root instance', function () {
    $animal = Animal::new(['name' => 'Generic']);
    expect($animal)->toBeInstanceOf(Animal::class);
    expect(get_class($animal))->toBe(Animal::class);
});

test('new() on root model resolves child class from type', function () {
    $dog = Animal::new(['type' => Dog::class, 'name' => 'Rex']);
    expect($dog)->toBeInstanceOf(Dog::class);
});

test('new() on root model with inheritanceMap resolves child class from mapped key', function () {
    $car = Vehicle::new(['type' => 'car', 'make' => 'Ford']);
    expect($car)->toBeInstanceOf(Car::class);

    $truck = Vehicle::new(['type' => 'truck', 'make' => 'Volvo']);
    expect($truck)->toBeInstanceOf(Truck::class);
});

// --- Direct instantiation guard ---

test('directly instantiating root model with a type attribute throws an exception', function () {
    new Animal(['type' => Dog::class, 'name' => 'Rex']);
})->throws(Exception::class);

// --- Boot: creating listener ---

test('creating a child model automatically sets the type column', function () {
    $dog = Dog::create(['name' => 'Rex']);
    expect($dog->type)->toBe(Dog::class);
    expect($dog->getAttributes()['type'])->toBe(Dog::class);
});

test('creating a child model with inheritanceMap sets the mapped type key', function () {
    $car = Car::create(['make' => 'Ford']);
    expect($car->type)->toBe('car');
});

// --- Boot: global scope ---

test('querying a child model only returns records of that type', function () {
    Dog::create(['name' => 'Rex']);
    Dog::create(['name' => 'Buddy']);
    Cat::create(['name' => 'Whiskers']);

    expect(Dog::count())->toBe(2);
    expect(Cat::count())->toBe(1);
});

test('querying the root model returns all records regardless of type', function () {
    Dog::create(['name' => 'Rex']);
    Cat::create(['name' => 'Whiskers']);

    expect(Animal::count())->toBe(2);
});

test('querying a child model with inheritanceMap is scoped to its type', function () {
    Car::create(['make' => 'Ford']);
    Car::create(['make' => 'Toyota']);
    Truck::create(['make' => 'Volvo']);

    expect(Car::count())->toBe(2);
    expect(Truck::count())->toBe(1);
    expect(Vehicle::count())->toBe(3);
});

// --- newFromBuilder (hydration) ---

test('records hydrated from DB resolve to the correct child class', function () {
    Dog::create(['name' => 'Rex']);
    Cat::create(['name' => 'Whiskers']);

    $animals = Animal::all();

    expect($animals->first(fn ($a) => $a->name === 'Rex'))->toBeInstanceOf(Dog::class);
    expect($animals->first(fn ($a) => $a->name === 'Whiskers'))->toBeInstanceOf(Cat::class);
});

test('records with inheritanceMap hydrated from DB resolve to the correct child class', function () {
    Car::create(['make' => 'Ford']);
    Truck::create(['make' => 'Volvo']);

    $vehicles = Vehicle::all();

    expect($vehicles->first(fn ($v) => $v->make === 'Ford'))->toBeInstanceOf(Car::class);
    expect($vehicles->first(fn ($v) => $v->make === 'Volvo'))->toBeInstanceOf(Truck::class);
});

// --- getMorphClass ---

test('child model morph class matches root model morph class', function () {
    expect((new Dog)->getMorphClass())->toBe((new Animal)->getMorphClass());
    expect((new Cat)->getMorphClass())->toBe((new Animal)->getMorphClass());
});

test('child model with inheritanceMap morph class matches root model morph class', function () {
    expect((new Car)->getMorphClass())->toBe((new Vehicle)->getMorphClass());
});
