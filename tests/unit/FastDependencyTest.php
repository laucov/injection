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

use Laucov\Injection\FastDependency;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Laucov\Injection\FastDependency
 */
class FastDependencyTest extends TestCase
{
    /**
     * Dependency 1
     */
    protected FastDependency $d1;

    /**
     * Dependency 2
     */
    protected FastDependency $d2;

    /**
     * @covers ::__construct
     * @covers ::getAll
     * @covers ::has
     * @uses Laucov\Injection\FastDependency::get
     */
    public function testGetsAllValues(): void
    {
        $this->assertTrue($this->d1->has());
        $this->assertSame([1], $this->d1->getAll());
        $this->assertSame([2], $this->d1->getAll());
        $this->assertSame([3], $this->d1->getAll());
        $this->assertSame([4], $this->d1->getAll());
        $this->assertTrue($this->d1->has());
        $this->assertTrue($this->d2->has());
        $this->assertSame([81, 27, 9, 3], $this->d2->getAll());
        $this->assertFalse($this->d2->has());
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::has
     */
    public function testGetsSingleValues(): void
    {
        $this->assertTrue($this->d1->has());
        $this->assertSame(1, $this->d1->get());
        $this->assertSame(2, $this->d1->get());
        $this->assertSame(3, $this->d1->get());
        $this->assertSame(4, $this->d1->get());
        $this->assertTrue($this->d1->has());
        $this->assertTrue($this->d2->has());
        $this->assertSame(81, $this->d2->get());
        $this->assertSame(27, $this->d2->get());
        $this->assertSame(9, $this->d2->get());
        $this->assertSame(3, $this->d2->get());
        $this->assertFalse($this->d2->has());
    }

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $count = 1;
        $this->d1 = new FastDependency(function () use (&$count) {
            return $count++;
        });
        $number = 81;
        $this->d2 = new FastDependency(
            get: function () use (&$number) {
                $result = $number;
                $number /= 3;
                return $result;
            },
            getAll: function () use (&$number) {
                $numbers = [];
                while ($number % 3 === 0) {
                    $numbers[] = $number;
                    $number /= 3;
                }
                return $numbers;
            },
            has: function () use (&$number) {
                return $number % 3 === 0;
            },
        );
    }
}
