<?php

/**
 * This file is part of Laucov's Dependency Injection Library project.
 * 
 * Copyright 2024 Laucov Serviços de Tecnologia da Informação Ltda.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @package injection
 * 
 * @author Rafael Covaleski Pereira <rafael.covaleski@laucov.com>
 * 
 * @license <http://www.apache.org/licenses/LICENSE-2.0> Apache License 2.0
 * 
 * @copyright © 2024 Laucov Serviços de Tecnologia da Informação Ltda.
 */

declare(strict_types=1);

namespace Tests;

use Laucov\Injection\Repository;
use Laucov\Injection\Resolver;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Laucov\Injection\Resolver
 */
class ResolverTest extends TestCase
{
    protected Resolver $resolver;

    public function callableProvider(): array
    {
        return [
            // Test simple dynamic dependencies.
            [
                fn (string $a, string $b) => "{$a} and {$b}",
                'John and Mark',
            ],
            // Test dynamic dependency with variadic parameter.
            [
                fn (string ...$names) => implode(', ', $names),
                'John, Mark, James',
            ],
            // Test static dependency with variadic parameter.
            [
                fn (int $num, int ...$nums) => implode(', ', [$num, ...$nums]),
                '42, 42',
            ],
            // Test invalid dependency with nullable parameter.
            [
                fn (?\PDO $pdo) => $pdo,
                null,
            ],
            [
                fn (null|\PDO $pdo) => $pdo,
                null,
            ],
            // Test valid dependency with nullable parameter.
            [
                fn (?string $string) => $string,
                'John',
            ],
            [
                fn (null|string $string) => $string,
                'John',
            ],
            // Test invalid dependency with default value.
            [
                fn (?float $a = 2.45, ?string $b = 'Bob') => "{$a}, {$b}",
                '2.45, John',
            ],
            // Test untyped parameter.
            [
                fn ($untyped = 'DEFAULT') => $untyped,
                'DEFAULT',
            ],
            [
                fn ($untyped) => $untyped,
                null,
            ],
            // Test union types.
            // PHP does not sort the parameter types by declaration order:
            // These are listed first in the order they're declared:
            // - Classes, interfaces and traits
            // - parent
            // - self
            // These are listed last in the following order:
            // - static
            // - callable
            // - object
            // - array
            // - string
            // - int
            // - float
            // - bool
            // - false
            // - true
            // - null
            // Type "iterable" is splitted into "array" and "Traversable".
            [
                fn (int|string $v) => $v,
                'John',
            ],
            [
                fn (string|int $v) => $v,
                'John',
            ],
            [
                fn (A|B $obj) => $obj::class,
                A::class,
            ],
            [
                fn (B|A $obj) => $obj::class,
                B::class,
            ],
            [
                // Treated as "iterable"
                fn (iterable $i) => implode('', $i),
                'foobar',
            ],
            [
                // Treated as "Traversable|array"
                // Note: "Traversable" has preference over "array"
                fn (iterable|string $i) => implode('', $i),
                'barfoo',
            ],
            [
                fn (float|bool $v) => $v,
                true,
            ],
            // @todo Test intersection types.
        ];
    }

    /**
     * @covers ::call
     * @covers ::pushArgument
     * @uses Laucov\Injection\IterableDependency::__construct
     * @uses Laucov\Injection\IterableDependency::get
     * @uses Laucov\Injection\IterableDependency::has
     * @uses Laucov\Injection\Repository::getValue
     * @uses Laucov\Injection\Repository::hasDependency
     * @uses Laucov\Injection\Repository::hasValue
     * @uses Laucov\Injection\Repository::setIterable
     * @uses Laucov\Injection\Repository::setValue
     * @uses Laucov\Injection\Resolver::__construct
     * @uses Laucov\Injection\ValueDependency::__construct
     * @uses Laucov\Injection\ValueDependency::get
     * @dataProvider callableProvider
     */
    public function testCanResolveDependencies(callable $fn, mixed $out): void
    {
        $this->assertSame($out, $this->resolver->call($fn));
    }

    protected function setUp(): void
    {
        $repo = new Repository();
        $repo->setValue(A::class, new A());
        $repo->setValue(B::class, new B());
        $repo->setValue('int', 42);
        $repo->setValue('array', ['bar', 'foo']);
        $repo->setValue('bool', true);
        $repo->setValue('iterable', ['foo', 'bar']);
        $repo->setIterable('string', ['John', 'Mark', 'James']);

        $this->resolver = new Resolver($repo);
    }
}

class A
{
}

class B
{
}
