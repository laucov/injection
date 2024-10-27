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

use Laucov\Injection\FactoryDependency;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Laucov\Injection\FactoryDependency
 */
class FactoryDependencyTest extends TestCase
{
    /**
     * Provide callables and expectations.
     */
    public function provideFactories(): array
    {
        $number = 0;
        $function = function () use (&$number) {
            $value = $number;
            $number++;
            return $value;
        };
        $object = new class () {
            public static int $number = 2;
            public static function getCurrentNumber(): int
            {
                return static::$number;
            }
            public static function getNextNumber(): int
            {
                $number = static::$number;
                static::$number *= 2;
                return $number;
            }
            public static function hasNumber(): bool
            {
                return static::$number < 16;
            }
            protected int $index = 0;
            protected array $records = [
                ['id' => 3, 'name' => 'John'],
                ['id' => 5, 'name' => 'Mary'],
                ['id' => 11, 'name' => 'Gary'],
            ];
            public function getCurrentName(): string
            {
                return $this->records[$this->index]['name'];
            }
            public function getNextName(): string
            {
                $name = $this->records[$this->index]['name'];
                $this->index++;
                return $name;
            }
            public function hasName(): bool
            {
                return isset($this->records[$this->index]);
            }
        };
        return [
            'function' => [
                $function,
                null,
                [0, 1, 2, 3, 4, 5, 6],
            ],
            'function (with tester)' => [
                $function,
                function () use (&$number) {
                    return $number < 8;
                },
                [7],
            ],
            'instance' => [
                [$object, 'getCurrentName'],
                null,
                ['John', 'John', 'John', 'John'],
            ],
            'instance (with tester)' => [
                [$object, 'getNextName'],
                [$object, 'hasName'],
                ['John', 'Mary', 'Gary'],
            ],
            'class' => [
                [$object::class, 'getCurrentNumber'],
                null,
                [2, 2, 2, 2],
            ],
            'class (with tester)' => [
                [$object::class, 'getNextNumber'],
                [$object::class, 'hasNumber'],
                [2, 4, 8],
            ],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::getAll
     * @covers ::has
     * @dataProvider provideFactories
     */
    public function testGetsValues(
        array|callable $get,
        null|array|callable $has,
        array $values,
    ): void {
        $dependency = new FactoryDependency($get, $has);
        foreach ($values as $value) {
            $this->assertTrue($dependency->has());
            $this->assertSame($value, $dependency->get());
        }
        if ($has === null) {
            $this->assertTrue($dependency->has());
        } else {
            $this->assertFalse($dependency->has());
        }
        $this->assertEquals([], $dependency->getAll());
    }
}
