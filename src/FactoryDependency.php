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

use Laucov\Injection\Interfaces\DynamicDependencyInterface;

/**
 * Stores a factory function or method as a dependency.
 */
class FactoryDependency implements DynamicDependencyInterface
{
    /**
     * Function/method to get values.
     * 
     * @var callable
     */
    protected mixed $getter;

    /**
     * Function/method to check if there are remanining values.
     * 
     * @var null|callable
     */
    protected mixed $tester;

    /**
     * Create the dependency instance.
     */
    public function __construct(array|callable $get, null|array|callable $has = null)
    {
        $this->getter = $get;
        $this->tester = $has;
    }

    /**
     * Get the next value.
     */
    public function get(): mixed
    {
        return call_user_func($this->getter);
    }

    /**
     * Check if there are new values to come.
     */
    public function has(): bool
    {
        return $this->tester === null ? true : call_user_func($this->tester);
    }
}
