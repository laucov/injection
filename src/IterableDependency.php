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
use RuntimeException;

/**
 * Stores an iterable as a dependency.
 */
class IterableDependency implements DependencyInterface
{
    /**
     * Registered iterable.
     */
    protected iterable $iterable;

    /**
     * Create the dependency instance.
     */
    public function __construct(mixed $source)
    {
        $this->iterable = $source;
    }

    /**
     * Get the next value.
     */
    public function get(): mixed
    {
        if (key($this->iterable) === null) {
            $message = 'No remaining values in iterable dependency.';
            throw new RuntimeException($message);
        }
        $value = current($this->iterable);
        next($this->iterable);
        return $value;
    }

    /**
     * Check if there are new values to come.
     */
    public function has(): bool
    {
        return key($this->iterable) !== null;
    }
}
