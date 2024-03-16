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
use Laucov\Injection\Interfaces\DynamicDependencyInterface;

/**
 * Stores dependency sources.
 */
class Repository
{
    /**
     * Registered dependencies.
     * 
     * @var array<string, DependencyInterface>
     */
    protected array $dependencies = [];

    /**
     * Get a dependency value.
     */
    public function getValue(string $name): mixed
    {
        return $this->dependencies[$name]->get();
    }

    /**
     * Check whether a dependency type is registered.
     */
    public function hasDependency(string $name): bool
    {
        return array_key_exists($name, $this->dependencies);
    }

    /**
     * Check whether new values can be outputted for a dependency.
     */
    public function hasValue(string $name): bool
    {
        $dependency = $this->dependencies[$name];

        if ($dependency instanceof DynamicDependencyInterface) {
            return $dependency->has();
        }

        return false;
    }

    /**
     * Set a factory function to get dependencies from.
     */
    public function setFactory(string $name, callable $factory): static
    {
        $this->dependencies[$name] = new FactoryDependency($factory);
        return $this;
    }

    /**
     * Set an iterable dependency.
     */
    public function setIterable(string $name, iterable $iterable): static
    {
        $this->dependencies[$name] = new IterableDependency($iterable);
        return $this;
    }

    /**
     * Set a value dependency.
     */
    public function setValue(string $name, mixed $value): static
    {
        $this->dependencies[$name] = new ValueDependency($value);
        return $this;
    }
}
