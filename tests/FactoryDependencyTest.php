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

use Laucov\Injection\Interfaces\DependencyInterface;
use Laucov\Injection\FactoryDependency;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Laucov\Injection\FactoryDependency
 */
class FactoryDependencyTest extends TestCase
{
    /**
     * @covers ::__construct
     * @covers ::get
     */
    public function testCanSetAndGet(): void
    {
        $callable = fn () => ValueHolderB::$value * 2;
        $dependency = new FactoryDependency($callable);
        $this->assertInstanceOf(DependencyInterface::class, $dependency);
        $this->assertSame(4, $dependency->get());
        ValueHolderB::$value = 5;
        $this->assertSame(10, $dependency->get());
    }
}

class ValueHolderB
{
    public static int $value = 2;
}
