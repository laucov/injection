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
     * Fallback classes.
     * 
     * @var array<string>
     */
    public array $fallbacks = [];

    /**
     * Redirections.
     * 
     * @var array<string, Repository>
     */
    public array $redirects = [];

    /**
     * Return a class when its parents are requested and not found.
     */
    public function fallback(string $name): static
    {
        $this->fallbacks[] = $name;
        return $this;
    }

    /**
     * Get a dependency value.
     */
    public function getValue(string $name): mixed
    {
        return $this->require($name)->get();
    }

    /**
     * Get all values of a dependency.
     */
    public function getValues(string $name): array
    {
        return $this->require($name)->getAll();
    }

    /**
     * Check whether a dependency type is registered.
     */
    public function hasDependency(string $name): bool
    {
        if (array_key_exists($name, $this->redirects)) {
            return $this->redirects[$name]->hasDependency($name);
        }
        return $this->resolve($name) !== null;
    }

    /**
     * Check whether new values can be outputted for a dependency.
     */
    public function hasValue(string $name): bool
    {
        return $this->require($name)->has();
    }

    /**
     * Redirect a dependency name to another repository.
     */
    public function redirect(string $name, Repository $repository): static
    {
        $this->redirects[$name] = $repository;
        return $this;
    }

    /**
     * Remove a dependency.
     */
    public function removeDependency(string $name): static
    {
        unset($this->dependencies[$name]);
        return $this;
    }

    /**
     * Set a custom implementation of `DependencyInterface`.
     */
    public function setCustom(string $name, DependencyInterface $custom): static
    {
        $this->dependencies[$name] = $custom;
        return $this;
    }

    /**
     * Set a factory function to get dependencies from.
     * 
     * @deprecated 2.0.0 Use `setCustom()` with custom dependencies instead.
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

    /**
     * Find a suitable fallback for a dependency name.
     */
    protected function find(string $name): null|string
    {
        if (class_exists($name)) {
            foreach ($this->fallbacks as $fallback) {
                if (is_a($fallback, $name, true)) {
                    return $fallback;
                }
            }
        }
        return null;
    }

    /**
     * Require a dependency to exist.
     */
    protected function require(string $name): DependencyInterface
    {
        if (array_key_exists($name, $this->redirects)) {
            return $this->redirects[$name]->require($name);
        }
        $result = $this->resolve($name);
        if ($result === null) {
            $message = sprintf('Dependency "%s" not found.', $name);
            throw new RuntimeException($message);
        }
        return $this->dependencies[$result];
    }

    /**
     * Resolve a dependency name.
     */
    protected function resolve(string $name): null|string
    {
        return array_key_exists($name, $this->dependencies)
            ? $name
            : $this->find($name);
    }
}
