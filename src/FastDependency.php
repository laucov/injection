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
 * Register callbacks as a dependency.
 */
class FastDependency implements DependencyInterface
{
    /**
     * Create the dependency instance.
     */
    public function __construct(
        protected mixed $get,
        protected mixed $getAll = null,
        protected mixed $has = null,
    ) {
    }

    /**
     * Get the next value.
     */
    public function get(): mixed
    {
        return call_user_func($this->get);
    }

    /**
     * Get all available values.
     */
    public function getAll(): array
    {
        return $this->getAll === null
            ? [$this->get()]
            : call_user_func($this->getAll);
    }

    /**
     * Check whether a value is currently available.
     */
    public function has(): bool
    {
        return $this->has === null ? true : call_user_func($this->has);
    }
}
