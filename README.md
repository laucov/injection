# Laucov's Dependency Injection Library

Library for easy callable dependency injection.

## Installation

```shell
composer require laucov/injection
```

## Usage

Use the `Repository` class to set available values and the `Resolver` or `Validator` class to handle dependent functions, method or class constructors.

Example:

```php
use Laucov\Injection\Repository;
use Laucov\Injection\Resolver;
use Laucov\Injection\Validator;

require __DIR__ . '/vendor/autoload.php';

// Instantiate
$repository = new Repository;
$resolver = new Resolver($repository);
$validator = new Validator($repository);

// Set dependencies.
$repository->setValue('float', 3.14);
$repository->setValue(stdClass::class, new stdClass());
$repository->setIterable('string', ['John', 'Mark', 'Alfred']);
$repository->setFactory('int', fn () => time());

// Create callable.
$callback = function (int $time, string ...$names) {
    $message = 'Called at %s with names %s.';
    echo sprintf($message, $time, implode(', ', $names));
};

// Validate.
$validator->validate($callback); # Returns true

// Call - supposing time is 1725111445
$resolver->call($callback);
// Output: Called at 1725111445 with names John, Mark, Alfred.
```
