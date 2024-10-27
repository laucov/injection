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

use Laucov\Injection\Interfaces\DependencyInterface;
use Laucov\Injection\Repository;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @coversDefaultClass \Laucov\Injection\Repository
 */
class RepositoryTest extends TestCase
{
    protected Repository $repo;

    /**
     * @covers ::getValue
     * @covers ::getValues
     * @covers ::hasValue
     * @uses Laucov\Injection\Repository::setCustom
     */
    public function testGetsValues(): void
    {
        $custom_a = $this->createMock(DependencyInterface::class);
        $custom_a
            ->expects($this->exactly(3))
            ->method('has')
            ->willReturnOnConsecutiveCalls(true, true, false);
        $custom_a
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls('John', 'Mary');
        $custom_b = $this->createMock(DependencyInterface::class);
        $custom_b
            ->expects($this->once())
            ->method('get')
            ->willReturn(42);
        $custom_b
            ->expects($this->exactly(2))
            ->method('has')
            ->willReturnOnConsecutiveCalls(true, false);
        $custom_c = $this->createMock(DependencyInterface::class);
        $custom_c
            ->expects($this->once())
            ->method('getAll')
            ->willReturn([1.25, 1.5, 1.75]);
        $custom_c
            ->expects($this->exactly(2))
            ->method('has')
            ->willReturnOnConsecutiveCalls(true, false);
        $custom_d = $this->createMock(DependencyInterface::class);
        $custom_d
            ->expects($this->once())
            ->method('get')
            ->willReturn(99);
        $custom_d
            ->expects($this->exactly(2))
            ->method('has')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->repo
            ->setCustom('string', $custom_a)
            ->setCustom('int', $custom_b)
            ->setCustom('float', $custom_c);
        $this->assertTrue($this->repo->hasValue('string'));
        $this->assertSame('John', $this->repo->getValue('string'));
        $this->assertTrue($this->repo->hasValue('string'));
        $this->assertSame('Mary', $this->repo->getValue('string'));
        $this->assertFalse($this->repo->hasValue('string'));
        $this->assertTrue($this->repo->hasValue('int'));
        $this->assertSame(42, $this->repo->getValue('int'));
        $this->assertFalse($this->repo->hasValue('int'));
        $this->assertTrue($this->repo->hasValue('float'));
        $this->assertEquals([1.25, 1.5, 1.75], $this->repo->getValues('float'));
        $this->assertFalse($this->repo->hasValue('float'));
        $this->repo->setCustom('int', $custom_d);
        $this->assertTrue($this->repo->hasValue('int'));
        $this->assertSame(99, $this->repo->getValue('int'));
        $this->assertFalse($this->repo->hasValue('int'));
    }

    /**
     * @covers ::getValue
     * @covers ::hasDependency
     * @covers ::hasValue
     * @covers ::removeDependency
     * @covers ::setCustom
     * @covers ::setFactory
     * @covers ::setIterable
     * @covers ::setValue
     * @uses Laucov\Injection\FactoryDependency::__construct
     * @uses Laucov\Injection\FactoryDependency::get
     * @uses Laucov\Injection\IterableDependency::__construct
     * @uses Laucov\Injection\IterableDependency::get
     * @uses Laucov\Injection\IterableDependency::has
     * @uses Laucov\Injection\ValueDependency::__construct
     * @uses Laucov\Injection\ValueDependency::get
     * @uses Laucov\Injection\ValueDependency::has
     */
    public function testSetsDependencies(): void
    {
        $this->assertFalse($this->repo->hasDependency('float'));
        $this->assertFalse($this->repo->hasDependency('string'));
        $this->assertFalse($this->repo->hasDependency('int'));
        $this->assertFalse($this->repo->hasDependency('bool'));
        $this->repo
            ->setIterable('float', [0.00, 0.25, 0.50, 0.75, 1.00])
            ->setValue('string', 'John')
            ->setValue('int', 42);
        $this->assertTrue($this->repo->hasDependency('float'));
        $this->assertTrue($this->repo->hasDependency('string'));
        $this->assertTrue($this->repo->hasDependency('int'));
        $this->assertFalse($this->repo->hasDependency('bool'));
        $object = new stdClass();
        $object->value = 0;
        $factory = fn () => $object->value++;
        $custom = $this->createMock(DependencyInterface::class);
        $this->repo
            ->setFactory('float', $factory)
            ->setCustom('bool', $custom)
            ->removeDependency('string')
            ->setValue('int', 99);
        $this->assertTrue($this->repo->hasDependency('float'));
        $this->assertFalse($this->repo->hasDependency('string'));
        $this->assertTrue($this->repo->hasDependency('int'));
        $this->assertTrue($this->repo->hasDependency('bool'));
    }

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->repo = new Repository();
    }
}
