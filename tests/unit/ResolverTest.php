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

namespace Tests\Unit;

use Laucov\Injection\Repository;
use Laucov\Injection\Resolver;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Laucov\Injection\Resolver
 */
class ResolverTest extends TestCase
{
    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        $a = new class {};
        class_alias($a::class, __NAMESPACE__ . '\\' . 'A');
        $b = new class {};
        class_alias($b::class, __NAMESPACE__ . '\\' . 'B');
    }

    /**
     * Resolver instance.
     */
    protected Resolver $resolver;

    /**
     * Provides resolvable callables and expected arguments.
     */
    public function callableProvider(): array
    {
        $object = new class {
            public function doSomething(string $a, int $b): void
            {
            }
        };
        return [
            'dynamic dependency' => [
                fn (string $a, string $b) => '',
                ['John', 'Mark'],
            ],
            'dynamic variadic dependency' => [
                fn (string ...$names) => '',
                ['John', 'Mark', 'James'],
            ],
            'static variadic dependency' => [
                fn (int $num, int ...$nums) => '',
                [42, 42],
            ],
            'invalid but nullable depedency #1' => [
                fn (?\PDO $pdo) => '',
                [null],
            ],
            'invalid but nullable depedency #2' => [
                fn (null|\PDO $pdo) => '',
                [null],
            ],
            'valid nullable dependency #1' => [
                fn (?string $string) => '',
                ['John'],
            ],
            'valid nullable dependency #2' => [
                fn (null|string $string) => '',
                ['John'],
            ],
            'invalid dependency with default value' => [
                fn (?float $a = 2.45, ?string $b = 'Bob') => '',
                [2.45, 'John'],
            ],
            'untyped dependency with default value' => [
                fn ($untyped = 'DEFAULT') => '',
                ['DEFAULT'],
            ],
            'untyped dependency w/o default value' => [
                fn ($untyped) => '',
                [null],
            ],
            'uninstantiated class method' => [
                [$object::class, 'doSomething'],
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
        // Create class.
        $object = new class ('', new B) {
            public function __construct(
                public string $a,
                public B $b,
            ) {
            }
        };

        // Resolve constructor dependencies.
        $instance = $this->resolver->instantiate($object::class);
        $this->assertInstanceOf($object::class, $instance);
        $this->assertSame('John', $instance->a);
        $this->assertInstanceOf(B::class, $instance->b);

        // Test class with no constructor.
        $object = new class {};
        $this->assertInstanceOf(
            $object::class,
            $this->resolver->instantiate($object::class),
        );
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
        $object = new class {
            public static function join(array $array): string
            {
                return implode(',', $array);
            }
            public function sum(int $a, int $b): int
            {
                return $a + $b;
            }
        };
        $this->assertSame(84, $this->resolver->call([$object, 'sum']));
        $actual = $this->resolver->call([$object::class, 'join']);
        $this->assertSame('bar,foo', $actual);
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
    public function testCanGetArguments(mixed $callable, array $expected): void
    {
        $actual = $this->resolver->resolve($callable);
        $this->assertIsArray($actual);
        $this->assertSameSize($expected, $actual);
        foreach ($expected as $i => $expected_argument) {
            $this->assertArrayHasKey($i, $actual);
            $this->assertSame($expected_argument, $actual[$i]);
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

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $repository = new Repository();
        $repository->setValue(A::class, new A());
        $repository->setValue(B::class, new B());
        $repository->setValue('int', 42);
        $repository->setValue('array', ['bar', 'foo']);
        $repository->setValue('bool', true);
        $repository->setValue('iterable', ['foo', 'bar']);
        $repository->setIterable('string', ['John', 'Mark', 'James']);
        $this->resolver = new Resolver($repository);
    }
}
