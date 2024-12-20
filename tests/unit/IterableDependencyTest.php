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

use Laucov\Injection\IterableDependency;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @coversDefaultClass \Laucov\Injection\IterableDependency
 */
class IterableDependencyTest extends TestCase
{
    /**
     * @covers ::getAll
     * @uses Laucov\Injection\IterableDependency::__construct
     * @uses Laucov\Injection\IterableDependency::get
     * @uses Laucov\Injection\IterableDependency::has
     */
    public function testGetsAllValues(): void
    {
        $dependency = new IterableDependency(['abcdef', 3.14, 42]);
        $this->assertTrue($dependency->has());
        $this->assertEquals(['abcdef', 3.14, 42], $dependency->getAll());
        $this->assertFalse($dependency->has());
    }

    /**
     * @covers ::__construct
     * @covers ::get
     * @covers ::has
     */
    public function testGetsSingleValues(): void
    {
        $dependency = new IterableDependency(['foo', 'bar', 'baz']);
        $this->assertTrue($dependency->has());
        $this->assertSame('foo', $dependency->get());
        $this->assertTrue($dependency->has());
        $this->assertSame('bar', $dependency->get());
        $this->assertTrue($dependency->has());
        $this->assertSame('baz', $dependency->get());
        $this->assertFalse($dependency->has());
        $this->expectException(RuntimeException::class);
        $message = 'No available values in iterable dependency.';
        $this->expectExceptionMessage($message);
        $dependency->get();
    }
}
