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

namespace Laucov\Injection;

use Laucov\Injection\Interfaces\DependencyInterface;

/**
 * Stores a value as a dependency.
 */
class ValueDependency implements DependencyInterface
{
    /**
     * Registered value.
     */
    protected mixed $value;

    /**
     * Create the dependency instance.
     */
    public function __construct(mixed $source)
    {
        $this->value = $source;
    }

    /**
     * Get the next value.
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * Get all available values.
     */
    public function getAll(): array
    {
        return [$this->value];
    }

    /**
     * Check whether a value is currently available.
     */
    public function has(): bool
    {
        return true;
    }
}
