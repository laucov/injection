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

use Exception;
use Laucov\Injection\Repository;
use Laucov\Injection\Resolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @coversDefaultClass \Laucov\Injection\Resolver
 */
class ResolverTest extends TestCase
{
    /**
     * Resolver.
     */
    protected Resolver $resolver;

    /**
     * Repository mock.
     */
    protected Repository&MockObject $repository;

    /**
     * Provides callables and expected outputs.
     */
    public static function provideCallables(): array
    {
        $object = new class () {
            public static function divideByThree(int $number): int
            {
                return $number / 3;
            }
            public function __invoke(string $subject): string
            {
                return str_repeat($subject, 2);
            }
            public function removeVowels(string $subject): string
            {
                return str_replace(['a', 'e', 'i', 'o', 'u'], '', $subject);
            }
        };
        return [
            'closure' => [fn (int $a, int $b) => $a + $b, 1998],
            'global function' => ['strrev', 'raboof'],
            'instance method' => [[$object, 'removeVowels'], 'fbr'],
            'static method' => [[$object::class, 'divideByThree'], 333],
            'invokable instance' => [$object, 'foobarfoobar'],
        ];
    }

    /**
     * Provides class names.
     */
    public static function provideClassNames(): array
    {
        return [
            'anonymous w/o constructor' => [
                (new class () {})::class,
            ],
            'anonymous with constructor' => [
                (new class ('', 0) {
                    public function __construct(string $a, int $b)
                    {
                    }
                })::class,
            ],
        ];
    }

    /**
     * Provides functions and expected arguments.
     */
    public static function provideResolvableFunctions(): array
    {
        return [
            'common' => [
                function (string $a, int $b, object $c) {},
                ['foobar', 999, (object) ['id' => 1, 'name' => 'John']],
            ],
            'nullable' => [
                function (?float $a, null|array $b) {},
                [null, null],
            ],
            'default value' => [
                function (null|float $a = 3.14, ?array $b = ['AZ', 42]) {},
                [3.14, ['AZ', 42]],
            ],
            'variadic' => [
                function (bool $a, string ...$b) {},
                [true, 'foobar', 'foobar', 'foobar'],
            ],
            'untyped' => [
                function ($a, $b = 1.25, ...$c) {},
                [null, 1.25],
            ],
        ];
    }

    /**
     * @covers ::call
     * @covers ::resolve
     * @uses Laucov\Injection\Resolver::__construct
     * @uses Laucov\Injection\Resolver::resolveNamedType
     * @uses Laucov\Injection\Resolver::resolveParameter
     * @dataProvider provideCallables
     */
    public function testCallsCallables($callable, $expected): void
    {
        $this->assertEquals($expected, $this->resolver->call($callable));
    }

    /**
     * @covers ::instantiate
     * @uses Laucov\Injection\Resolver::__construct
     * @uses Laucov\Injection\Resolver::resolve
     * @uses Laucov\Injection\Resolver::resolveNamedType
     * @uses Laucov\Injection\Resolver::resolveParameter
     * @dataProvider provideClassNames
     */
    public function testInstantiateClasses($class_name): void
    {
        $this->assertInstanceOf(
            $class_name,
            $this->resolver->instantiate($class_name),
        );
    }

    /**
     * @covers ::resolveNamedType
     * @uses Laucov\Injection\Resolver::__construct
     * @uses Laucov\Injection\Resolver::resolve
     * @uses Laucov\Injection\Resolver::resolveParameter
     */
    public function testPanicsIfCannotResolve(): void
    {
        $this->expectException(RuntimeException::class);
        $message = 'Could not resolve parameter of type float.';
        $this->expectExceptionMessage($message);
        $this->resolver->resolve(fn (float $a) => null);
    }

    /**
     * @covers ::resolveParameter
     * @uses Laucov\Injection\Resolver::__construct
     * @uses Laucov\Injection\Resolver::resolve
     */
    public function testPanicsIfFindsIntersectionTypes(): void
    {
        $this->expectException(RuntimeException::class);
        $message = 'Cannot resolve intersection types.';
        $this->expectExceptionMessage($message);
        $this->resolver->resolve(fn (RuntimeException&Exception $a) => null);
    }

    /**
     * @covers ::resolveParameter
     * @uses Laucov\Injection\Resolver::__construct
     * @uses Laucov\Injection\Resolver::resolve
     */
    public function testPanicsIfFindsUnionTypes(): void
    {
        $this->expectException(RuntimeException::class);
        $message = 'Cannot resolve union types.';
        $this->expectExceptionMessage($message);
        $this->resolver->resolve(fn (float|int $a) => null);
    }

    /**
     * @covers ::__construct
     * @covers ::resolve
     * @covers ::resolveNamedType
     * @covers ::resolveParameter
     * @covers ::resolveUnknownType
     * @dataProvider provideResolvableFunctions
     */
    public function testResolvesParameters($callable, array $expected): void
    {
        $actual = $this->resolver->resolve($callable);
        $this->assertEquals($expected, $actual);
        $callable(...$actual);
    }

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->repository = $this->createMock(Repository::class);
        $this->repository
            ->method('hasDependency')
            ->willReturnCallback(fn (string $name) => match ($name) {
                'array' => false,
                'bool' => true,
                'float' => false,
                'int' => true,
                'object' => true,
                'string' => true,
            });
        $this->repository
            ->method('hasValue')
            ->willReturn(true);
        $this->repository
            ->method('getValue')
            ->willReturnMap([
                ['bool', true],
                ['int', 999],
                ['object', (object) ['id' => 1, 'name' => 'John']],
                ['string', 'foobar'],
            ]);
        $this->repository
            ->method('getValues')
            ->willReturnMap([
                ['string', ['foobar', 'foobar', 'foobar']],
            ]);
        $this->resolver = new Resolver($this->repository);
    }
}
