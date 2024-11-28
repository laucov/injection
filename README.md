# Laucov's Dependency Injection Library

Library for easy callable dependency injection.

## Installation

```shell
composer require laucov/injection
```

## Usage

Use the `Repository` class to register all available values. Get arguments, call functions and instantiate classes with the `Resolver` class. See the examples below.

### Value and iterable dependencies

Use `setValue()` to set fixed value dependencies that are always available and `setIterable()` to iterate over a limited set of values.

```php
use Laucov\Injection\Repository;
use Laucov\Injection\Resolver;

require __DIR__ . '/vendor/autoload.php';

$repository = new Repository;
$resolver = new Resolver($repository);

$repository->setValue('float', 3.14);
$repository->setIterable('string', ['John', 'Mary', 'Mark']);
$callback = function (string $name, float $number) {
    echo "Name: {$name}; Number: {$number}." . PHP_EOL;
};
$resolver->call($callback);
$resolver->call($callback);
$repository->setValue('float', 3.99);
$resolver->call($callback);
```

Output:

```text
Name: John; Number: 3.14.
Name: Mary; Number: 3.14.
Name: Mark; Number: 3.99.
```

### Variadic parameters

When a function or a method with variadic parameters is resolved, the `Resolver` object requests all available values from its dependencies.

Fixed values are returned as single-element arrays and iterables are sliced from its next to its last available value.

```php
use Laucov\Injection\Repository;
use Laucov\Injection\Resolver;

require __DIR__ . '/vendor/autoload.php';

$repository = new Repository;
$resolver = new Resolver($repository);

$repository->setIterable('int', [14, 29, 71]);
$repository->setValue(DateTime::class, new DateTime('2024-10-27 11:52:00'));
$callable = function (DateTime $date, int ...$ids): void {
    $date = $date->format('d/m/Y');
    $ids = implode(', ', $ids);
    echo "Received ids ({$ids}) on {$date}." . PHP_EOL;
};
$resolver->call($callable);
```

Output:

```text
Received ids (14, 29, 71) on 27/10/2024.
```

### Instantiating classes

The `Resolver` object is able to resolve class constructors. Calling `instantiate()` will find the appropriate arguments and return an instance of the specified class.

```php
use Laucov\Injection\Repository;
use Laucov\Injection\Resolver;

require __DIR__ . '/vendor/autoload.php';

class Updater
{
    /**
     * Create the updater instance.
     */
    public function __construct(
        protected object $record,
        protected array $data,
    ) {
    }

    /**
     * Update the record.
     */
    public function update(): void
    {
        foreach ($this->data as $key => $value) $this->record->$key = $value;
        $date = date_create()->format('Y-m-d H:i:s');
        $this->record->updated_at = $date;
        echo 'Updated ' . $this->record->name . ' at ' . $date . PHP_EOL;
    }
}

$repository = new Repository;
$resolver = new Resolver($repository);

$object = new class {
    public string $name = 'John Doe';
    public int $age = 42;
    public null|string $updated_at = null;
};
$repository
    ->setValue('object', $object)
    ->setValue('array', ['age' => 43]);
$updater = $resolver->instantiate(Updater::class);
$updater->update();
```

Output:

```text
Updated John Doe at 2024-10-27 15:22:46
```

### Getting arguments

You may also just get the arguments that would be used with a callable without actually calling it.

```php
use Laucov\Injection\Repository;
use Laucov\Injection\Resolver;

require __DIR__ . '/vendor/autoload.php';

$repository = new Repository;
$resolver = new Resolver($repository);

$repository
    ->setIterable('int', [12, 13, 14, 15])
    ->setValue('string', 'foobar');
$callable = fn (int $num, string $text) => print($text . '; ' . $num);
$arguments = $resolver->resolve($callable);
var_dump($arguments);
```

Output:

```text
array(2) {
  [0]=>
  int(12)
  [1]=>
  string(6) "foobar"
}
```

### Accepted callables

Any callable value is accepted by the `Resolver` `resolve()` and `call()` methods.

```php
use Laucov\Injection\Repository;
use Laucov\Injection\Resolver;

require __DIR__ . '/vendor/autoload.php';

$repository = new Repository;
$resolver = new Resolver($repository);

class Formatter
{
    public static function format(DateTimeInterface $d, string $f): string
    {
        return date_format($d, $f);
    }
}

function format_my_date(DateTimeInterface $d, string $f): string
{
    return date_format($d, $f);
}

$date = new DateTime;
$repository
    ->setValue(DateTimeInterface::class, $date)
    ->setValue('string', 'Y-m-d');
echo $resolver->call('date_format') . PHP_EOL;
echo $resolver->call([$date, 'format']) . PHP_EOL;
echo $resolver->call(fn (string $f) => $date->format($f)) . PHP_EOL;
echo $resolver->call('format_my_date') . PHP_EOL;
echo $resolver->call([Formatter::class, 'format']) . PHP_EOL;
```

Output:

```text
2024-10-27
2024-10-27
2024-10-27
2024-10-27
2024-10-27
```

### Custom dependencies

Call `setCustom()` to use custom implementations of `DependencyInterface` as dependencies.

```php
use Laucov\Injection\Interfaces\DependencyInterface;
use Laucov\Injection\Repository;
use Laucov\Injection\Resolver;

require __DIR__ . '/vendor/autoload.php';

$repository = new Repository;
$resolver = new Resolver($repository);

class TimeGetter implements DependencyInterface
{
    /**
     * Get the next available value.
     */
    public function get(): mixed
    {
        return time();
    }

    /**
     * Get all available values.
     * 
     * Used with variadic parameters.
     */
    public function getAll(): array
    {
        return [$this->get()];
    }

    /**
     * Check if there are available values.
     */
    public function has(): bool
    {
        return true;
    }
}

$repository->setValue('string', 'Time Printer v1.2.1');
$repository->setCustom('int', new TimeGetter);
$callable = function (string $app_name, int $time): void {
    echo 'Welcome to ' . $app_name . PHP_EOL;
    echo 'Current time is ' . $time . PHP_EOL;
};
$resolver->call($callable);
```

Output:

```text
Welcome to Time Printer v1.2.1
Current time is 1730041394
```

## Next features

| Method | Description |
| ------ | ----------- |
| `Resolver::alias()` | Alias a dependency name |
| `Resolver::fallback()` | Allow a class to be returned when its parents are requested and not found |
| `Resolver::redirect()` | Assign another repository to handle a dependency name |
