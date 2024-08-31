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
     * @covers ::hasDependency
     * @covers ::hasValue
     * @covers ::removeDependency
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
     */
    public function testCanSetDependencies(): void
    {
        // Set absolute values.
        $this->repo
            ->setValue('string', 'John')
            ->setValue('int', 42);
        $this->assertFalse($this->repo->hasValue('string'));
        $this->assertFalse($this->repo->hasValue('int'));
        $this->assertSame('John', $this->repo->getValue('string'));
        $this->assertSame(42, $this->repo->getValue('int'));
        $this->assertFalse($this->repo->hasValue('string'));
        $this->assertFalse($this->repo->hasValue('int'));

        // Set iterable values.
        $this->repo->setIterable('float', [0.00, 0.25, 0.50, 0.75, 1.00]);
        $this->assertSame(0.00, $this->repo->getValue('float'));
        $this->assertTrue($this->repo->hasValue('float'));
        $this->assertSame(0.25, $this->repo->getValue('float'));
        $this->assertTrue($this->repo->hasValue('float'));
        $this->assertSame(0.50, $this->repo->getValue('float'));
        $this->assertTrue($this->repo->hasValue('float'));
        $this->assertSame(0.75, $this->repo->getValue('float'));
        $this->assertTrue($this->repo->hasValue('float'));
        $this->assertSame(1.00, $this->repo->getValue('float'));
        $this->assertFalse($this->repo->hasValue('float'));
        $this->assertSame(0.00, $this->repo->getValue('float'));
        $this->assertTrue($this->repo->hasValue('float'));
        $this->assertSame(0.25, $this->repo->getValue('float'));
        $this->assertTrue($this->repo->hasValue('float'));

        // Set factory function.
        // Also test if can replace a type in the repository (replacing `int`).
        $object = new stdClass;
        $object->value = 0;
        $factory = fn () => $object->value++;
        $this->repo->setFactory('int', $factory);
        $this->assertSame(0, $this->repo->getValue('int'));
        $this->assertSame(1, $this->repo->getValue('int'));
        $this->assertSame(2, $this->repo->getValue('int'));

        // Check dependency existence.
        $this->assertTrue($this->repo->hasDependency('string'));
        $this->assertTrue($this->repo->hasDependency('int'));
        $this->assertTrue($this->repo->hasDependency('float'));
        $this->assertFalse($this->repo->hasDependency('array'));

        // Remove dependency.
        $this->assertTrue($this->repo->hasDependency('float'));
        $this->repo->removeDependency('float');
        $this->assertFalse($this->repo->hasDependency('float'));
    }

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->repo = new Repository();
    }
}
