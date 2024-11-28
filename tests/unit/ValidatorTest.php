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
use Laucov\Injection\Validator;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Laucov\Injection\Validator
 */
class ValidatorTest extends TestCase
{
    protected Validator $validator;

    /**
     * Provides callables and corresponding validation expectations.
     */
    public function callableValidationProvider(): array
    {
        $object = new class () {
            public function a(string $arg)
            {
            }
            public function b(float $arg)
            {
            }
        };
        return [
            [fn (string $a, string $b, string ...$c) => '', true],
            [fn (int $a, float $b) => '', false],
            [fn (int $a, float $b = 3.14) => '', true],
            [fn (int $a, ?float $b) => '', true],
            [fn (int $a, null|float $b) => '', true],
            [fn (int $a, null|float $b) => '', true],
            [fn ($a) => '', true],
            [fn (array $a) => '', true],
            [fn (object $a) => '', false],
            [fn (int|string $a) => '', false],
            [[$object::class, 'a'], true],
            [[$object::class, 'b'], false],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::allow
     * @covers ::disallow
     * @covers ::forbid
     * @covers ::validate
     * @covers ::validateParameter
     * @uses Laucov\Injection\IterableDependency::__construct
     * @uses Laucov\Injection\Repository::find
     * @uses Laucov\Injection\Repository::hasDependency
     * @uses Laucov\Injection\Repository::resolve
     * @uses Laucov\Injection\Repository::setIterable
     * @uses Laucov\Injection\Repository::setValue
     * @uses Laucov\Injection\ValueDependency::__construct
     * @dataProvider callableValidationProvider
     */
    public function testValidatesCallables(mixed $callable, bool $valid): void
    {
        $actual = $this->validator->validate($callable);
        if ($valid) {
            $this->assertTrue($actual);
        } else {
            $this->assertFalse($actual);
        }
    }

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $repo = new Repository();
        $repo->setIterable('string', ['a', 'b', 'c', 'd']);
        $repo->setValue('int', 123);
        $repo->setValue('float', 1.234);
        $this->validator = new Validator($repo);
        $this->validator->allow('array');
        $this->validator->allow('object');
        $this->validator->disallow('object');
        $this->validator->forbid('float');
    }
}
