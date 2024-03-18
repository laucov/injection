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
                fn (string $a, string $b) => '',
                ['John', 'Mark'],
            ],
            // Test dynamic dependency with variadic parameter.
            [
                fn (string ...$names) => '',
                ['John', 'Mark', 'James'],
            ],
            // Test static dependency with variadic parameter.
            [
                fn (int $num, int ...$nums) => '',
                [42, 42],
            ],
            // Test invalid dependency with nullable parameter.
            [
                fn (?\PDO $pdo) => '',
                [null],
            ],
            [
                fn (null|\PDO $pdo) => '',
                [null],
            ],
            // Test valid dependency with nullable parameter.
            [
                fn (?string $string) => '',
                ['John'],
            ],
            [
                fn (null|string $string) => '',
                ['John'],
            ],
            // Test invalid dependency with default value.
            [
                fn (?float $a = 2.45, ?string $b = 'Bob') => '',
                [2.45, 'John'],
            ],
            // Test untyped parameter.
            [
                fn ($untyped = 'DEFAULT') => '',
                ['DEFAULT'],
            ],
            [
                fn ($untyped) => '',
                [null],
            ],
            // Test class methods.
            [
                [MyClass::class, 'test'],
                ['John', 42],
            ],
        ];
    }

    /**
     * @covers ::instantiate
     * @uses Laucov\Injection\IterableDependency::__construct
     * @uses Laucov\Injection\IterableDependency::get
     * @uses Laucov\Injection\Repository::getValue
     * @uses Laucov\Injection\Repository::hasDependency
     * @uses Laucov\Injection\Repository::setIterable
     * @uses Laucov\Injection\Repository::setValue
     * @uses Laucov\Injection\Resolver::__construct
     * @uses Laucov\Injection\Resolver::getArguments
     * @uses Laucov\Injection\Resolver::pushArgument
     * @uses Laucov\Injection\Resolver::pushNamedTypeArgument
     * @uses Laucov\Injection\Resolver::resolve
     * @uses Laucov\Injection\ValueDependency::__construct
     * @uses Laucov\Injection\ValueDependency::get
     */
    public function testCanCallConstructors(): void
    {
        $instance = $this->resolver->instantiate(MyClass::class);
        $this->assertInstanceOf(MyClass::class, $instance);
        $this->assertSame('John', $instance->a);
        $this->assertInstanceOf(B::class, $instance->b);
    }

    /**
     * @covers ::call
     * @uses Laucov\Injection\IterableDependency::__construct
     * @uses Laucov\Injection\IterableDependency::get
     * @uses Laucov\Injection\IterableDependency::has
     * @uses Laucov\Injection\Repository::getValue
     * @uses Laucov\Injection\Repository::hasDependency
     * @uses Laucov\Injection\Repository::hasValue
     * @uses Laucov\Injection\Repository::setIterable
     * @uses Laucov\Injection\Repository::setValue
     * @uses Laucov\Injection\Resolver::__construct
     * @uses Laucov\Injection\Resolver::getArguments
     * @uses Laucov\Injection\Resolver::pushArgument
     * @uses Laucov\Injection\Resolver::pushNamedTypeArgument
     * @uses Laucov\Injection\Resolver::resolve
     * @uses Laucov\Injection\ValueDependency::__construct
     * @uses Laucov\Injection\ValueDependency::get
     */
    public function testCanCallFunctions(): void
    {
        $callable = fn (int $n, string ...$s) => "{$n}: " . implode(', ', $s);
        $output = '42: John, Mark, James';
        $this->assertSame($output, $this->resolver->call($callable));
    }

    /**
     * @covers ::__construct
     * @covers ::resolve
     * @covers ::getArguments
     * @covers ::pushArgument
     * @covers ::pushNamedTypeArgument
     * @covers ::pushUntypedArgument
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
    public function testCanGetArguments(array|callable $c, array $exp): void
    {
        $actual = $this->resolver->resolve($c);
        $this->assertIsArray($actual);
        $this->assertSameSize($exp, $actual);
        foreach ($exp as $i => $v) {
            $this->assertArrayHasKey($i, $actual);
            $this->assertSame($v, $actual[$i]);
        }
    }

    /**
     * @covers ::pushArgument
     * @uses Laucov\Injection\IterableDependency::__construct
     * @uses Laucov\Injection\Repository::setIterable
     * @uses Laucov\Injection\Repository::setValue
     * @uses Laucov\Injection\Resolver::__construct
     * @uses Laucov\Injection\Resolver::call
     * @uses Laucov\Injection\Resolver::getArguments
     * @uses Laucov\Injection\Resolver::resolve
     * @uses Laucov\Injection\ValueDependency::__construct
     */
    public function testCannotResolveUnionOrIntersectionTypes(): void
    {
        $this->expectException(\RuntimeException::class);
        $callable = fn (string|int $union_parameter) => $union_parameter;
        $this->resolver->call($callable);
    }

    /**
     * @covers ::pushNamedTypeArgument
     * @uses Laucov\Injection\IterableDependency::__construct
     * @uses Laucov\Injection\Repository::hasDependency
     * @uses Laucov\Injection\Repository::setIterable
     * @uses Laucov\Injection\Repository::setValue
     * @uses Laucov\Injection\Resolver::__construct
     * @uses Laucov\Injection\Resolver::call
     * @uses Laucov\Injection\Resolver::getArguments
     * @uses Laucov\Injection\Resolver::pushArgument
     * @uses Laucov\Injection\Resolver::resolve
     * @uses Laucov\Injection\ValueDependency::__construct
     */
    public function testRequiredParametersMustHaveAvailableTypes(): void
    {
        $this->expectException(\RuntimeException::class);
        $callable = fn (float $required_float) => $required_float;
        $this->resolver->call($callable);
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

class MyClass
{
    public function __construct(
        public string $a,
        public B $b,
    ) {
    }

    public function test(string $a, int $b): void
    {
    }
}
