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

use Laucov\Injection\Repository;
use Laucov\Injection\Validator;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Laucov\Injection\Validator
 */
class ValidatorTest extends TestCase
{
    protected Validator $validator;

    public function callableValidationProvider(): array
    {
        return [
            [fn (string $a, string $b, string ...$c) => '', true],
            [fn (int $a, float $b) => '', false],
            [fn (int $a, float $b = 3.14) => '', true],
            [fn (int $a, ?float $b) => '', true],
            [fn (int $a, null|float $b) => '', true],
            [fn (int $a, null|float $b) => '', true],
            [fn ($a) => '', true],
        ];
    }

    /**
     * @covers ::__construct
     * @covers ::validate
     * @uses Laucov\Injection\IterableDependency::__construct
     * @uses Laucov\Injection\Repository::hasDependency
     * @uses Laucov\Injection\Repository::setIterable
     * @uses Laucov\Injection\Repository::setValue
     * @uses Laucov\Injection\Validator::validateParameter
     * @uses Laucov\Injection\ValueDependency::__construct
     * @dataProvider callableValidationProvider
     */
    public function testValidatesCallables(callable $fn, bool $expected): void
    {
        $actual = $this->validator->validate($fn);
        if ($expected) {
            $this->assertTrue($actual);
        } else {
            $this->assertFalse($actual);
        }
    }

    protected function setUp(): void
    {
        $repo = new Repository();
        $repo->setIterable('string', ['a', 'b', 'c', 'd']);
        $repo->setValue('int', 123);

        $this->validator = new Validator($repo);
    }
}
