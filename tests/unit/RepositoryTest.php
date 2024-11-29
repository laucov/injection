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
use Laucov\Injection\Interfaces\DependencyInterface;
use Laucov\Injection\Repository;
use Laucov\Injection\ValueDependency;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

/**
 * @coversDefaultClass \Laucov\Injection\Repository
 */
class RepositoryTest extends TestCase
{
    protected Repository $repo;

    /**
     * Provide names of methods that must break when a depedency is not found.
     */
    public static function provideBreakableMethods(): array
    {
        return [['getValue'], ['getValues'], ['hasValue']];
    }

    /**
     * @covers ::addRule
     * @covers ::createDependency
     * @covers ::resolve
     * @uses Laucov\Injection\FastDependency::__construct
     * @uses Laucov\Injection\FastDependency::get
     * @uses Laucov\Injection\FastDependency::getAll
     * @uses Laucov\Injection\FastDependency::has
     * @uses Laucov\Injection\Repository::find
     * @uses Laucov\Injection\Repository::getValue
     * @uses Laucov\Injection\Repository::getValues
     * @uses Laucov\Injection\Repository::hasValue
     * @uses Laucov\Injection\Repository::require
     * @uses Laucov\Injection\Repository::resolve
     * @uses Laucov\Injection\Repository::setValue
     * @uses Laucov\Injection\ValueDependency::__construct
     * @uses Laucov\Injection\ValueDependency::get
     * @uses Laucov\Injection\ValueDependency::getAll
     * @uses Laucov\Injection\ValueDependency::has
     */
    public function testAllowsCustomRules(): void
    {
        $values = [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];
        $this->repo
            ->setValue('int', 999)
            ->addRule(fn ($name) => str_contains($name, 'int'), 'int')
            ->addRule(fn ($name) => str_starts_with($name, 'num'), 'integer')
            ->addRule(
                fn ($name) => str_starts_with($name, '$'),
                fn ($name) => match (true) {
                    $name === '$values' => new ValueDependency($values),
                    array_key_exists(substr($name, 1), $values) => new FastDependency(fn () => $values[substr($name, 1)]),
                    default => new ValueDependency(null),
                },
            );
        $this->assertTrue($this->repo->hasValue('number'));
        $this->assertTrue($this->repo->hasValue('inteiro'));
        $this->assertTrue($this->repo->hasValue('$values'));
        $this->assertTrue($this->repo->hasValue('$last_name'));
        $this->assertTrue($this->repo->hasValue('$foo'));
        $this->assertSame(999, $this->repo->getValue('number'));
        $this->assertSame(999, $this->repo->getValue('inteiro'));
        $this->assertSame($values, $this->repo->getValue('$values'));
        $this->assertSame('Doe', $this->repo->getValue('$last_name'));
        $this->assertSame(null, $this->repo->getValue('$bar'));
        $this->assertSame([999], $this->repo->getValues('number'));
        $this->assertSame([999], $this->repo->getValues('inteiro'));
        $this->assertSame([$values], $this->repo->getValues('$values'));
        $this->assertSame(['John'], $this->repo->getValues('$first_name'));
        $this->assertSame([null], $this->repo->getValues('$baz'));
    }

    /**
     * @covers ::getValue
     * @covers ::getValues
     * @covers ::hasValue
     * @covers ::require
     * @uses Laucov\Injection\Repository::resolve
     * @uses Laucov\Injection\Repository::setCustom
     */
    public function testGetsValues(): void
    {
        $custom_a = $this->createMock(DependencyInterface::class);
        $custom_a
            ->expects($this->exactly(5))
            ->method('has')
            ->willReturnOnConsecutiveCalls(true, true, true, true, false);
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
            ->expects($this->exactly(3))
            ->method('has')
            ->willReturnOnConsecutiveCalls(true, true, false);
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
            ->expects($this->exactly(3))
            ->method('has')
            ->willReturnOnConsecutiveCalls(true, true, false);
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
     * @uses Laucov\Injection\Repository::require
     * @uses Laucov\Injection\Repository::resolve
     * @uses Laucov\Injection\Repository::setCustom
     */
    public function testPanicsIfCannotGetValue(): void
    {
        $dependency = $this->createMock(DependencyInterface::class);
        $dependency
            ->expects($this->exactly(2))
            ->method('has')
            ->willReturn(true, false);
        $dependency
            ->expects($this->once())
            ->method('get')
            ->willReturn('foobar');
        $this->repo->setCustom('foobar', $dependency);
        $this->repo->getValue('foobar');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No more values in dependency "foobar".');
        $this->repo->getValue('foobar');
    }

    /**
     * @covers ::require
     * @uses Laucov\Injection\Repository::find
     * @uses Laucov\Injection\Repository::hasValue
     * @uses Laucov\Injection\Repository::getValue
     * @uses Laucov\Injection\Repository::getValues
     * @uses Laucov\Injection\Repository::resolve
     * @dataProvider provideBreakableMethods
     */
    public function testPanicsIfDependencyIsNotFound(string $method): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Dependency "string" not found.');
        $this->repo->{$method}('string');
    }

    /**
     * @covers ::find
     * @covers ::getValue
     * @covers ::hasDependency
     * @covers ::hasValue
     * @covers ::removeDependency
     * @covers ::resolve
     * @covers ::setCustom
     * @covers ::setFactory
     * @covers ::setIterable
     * @covers ::setValue
     * @uses Laucov\Injection\FactoryDependency::__construct
     * @uses Laucov\Injection\IterableDependency::__construct
     * @uses Laucov\Injection\ValueDependency::__construct
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
     * @covers ::fallback
     * @covers ::find
     * @covers ::resolve
     * @uses Laucov\Injection\Repository::getValue
     * @uses Laucov\Injection\Repository::getValues
     * @uses Laucov\Injection\Repository::hasDependency
     * @uses Laucov\Injection\Repository::hasValue
     * @uses Laucov\Injection\Repository::require
     * @uses Laucov\Injection\Repository::setValue
     * @uses Laucov\Injection\ValueDependency::__construct
     * @uses Laucov\Injection\ValueDependency::has
     * @uses Laucov\Injection\ValueDependency::get
     * @uses Laucov\Injection\ValueDependency::getAll
     */
    public function testSetsFallbacks(): void
    {
        $duck = new Duck;
        $lion = new Lion;
        $owl = new Owl;
        $person = new Person;
        $this->repo
            ->setValue(Duck::class, $duck)
            ->setValue(Lion::class, $lion)
            ->setValue(Owl::class, $owl)
            ->setValue(Person::class, $person);
        $this->assertFalse($this->repo->hasDependency(Animal::class));
        $this->assertFalse($this->repo->hasDependency(Bird::class));
        $this->assertFalse($this->repo->hasDependency(Mammal::class));
        $this->repo->fallback(Duck::class);
        $this->assertTrue($this->repo->hasDependency(Animal::class));
        $this->assertTrue($this->repo->hasDependency(Bird::class));
        $this->assertFalse($this->repo->hasDependency(Mammal::class));
        $this->assertTrue($this->repo->hasValue(Animal::class));
        $this->assertTrue($this->repo->hasValue(Bird::class));
        $this->assertSame($duck, $this->repo->getValue(Animal::class));
        $this->assertSame([$duck], $this->repo->getValues(Animal::class));
    }

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->repo = new Repository();
    }
}

abstract class Animal
{
    public const CELL_TYPE = 'eukaryotic';
}

abstract class Bird extends Animal
{
    public abstract function sing(): string;
}

abstract class Mammal extends Animal
{
    public abstract function yell(): string;
}

class Duck extends Bird
{
    public function sing(): string
    {
        return 'Quack!';
    }
}

class Owl extends Bird
{
    public function sing(): string
    {
        return 'Who!';
    }
}

class Person extends Mammal
{
    public function yell(): string
    {
        return 'Aaaaaaaah!';
    }
}

class Lion extends Mammal
{
    public function yell(): string
    {
        return 'Roaaaaaar!';
    }
}
