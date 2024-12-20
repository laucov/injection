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
 * 
 * @todo `redirect(string|callable $condition, string|Repository $destination)`
 * @todo `array<string, string|Repository> $aliases`
 * @todo `array<{callable, string|Repository}> $rules`
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
     * Registered fallback classes.
     * 
     * @var array<string>
     */
    protected array $fallbacks = [];

    /**
     * Registered custom rules.
     * 
     * @var array<int, {callable, string}>
     */
    protected array $rules = [];

    /**
     * Add a custom rule to resolve dependency names.
     * 
     * @param string|(callable(string $name): DependencyInterface) $resolve
     */
    public function addRule(callable $test, string|callable $resolve): static
    {
        if (is_string($resolve)) {
            $name = $resolve;
        } else {
            $name = uniqid();
            $dependency = $this->createDependency($resolve);
            $this->dependencies[$name] = $dependency;
            $test = function (string $name) use ($test, $dependency) {
                $dependency->name = $name;
                return $test($name);
            };
        }
        $this->rules[] = [$test, $name];
        return $this;
    }

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
        $dependency = $this->require($name);
        if ($dependency->has($name)) {
            return $dependency->get($name);
        } else {
            $message = sprintf('No more values in dependency "%s".', $name);
            throw new RuntimeException($message);
        }
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
        $result = $this->resolve($name);
        return $result !== null;
    }

    /**
     * Check whether new values can be outputted for a dependency.
     */
    public function hasValue(string $name): bool
    {
        return $this->require($name)->has();
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
     * Create a dependency from a resolution callback.
     */
    protected function createDependency(callable $resolve): DependencyInterface
    {
        return new class ($resolve) implements DependencyInterface {
            public string $name;
            public function __construct(protected mixed $callback)
            {
            }
            public function get(): mixed
            {
                return $this->getDependency()->get();
            }
            public function getAll(): array
            {
                return $this->getDependency()->getAll();
            }
            public function has(): bool
            {
                return $this->getDependency()->has();
            }
            protected function getDependency(): DependencyInterface
            {
                return call_user_func($this->callback, $this->name);
            }
        };
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
        if (array_key_exists($name, $this->dependencies)) {
            return $name;
        } elseif ($found = $this->find($name)) {
            return $found;
        } else {
            foreach ($this->rules as [$test, $result]) {
                if ($test($name)) {
                    return $this->resolve($result);
                }
            }
            return null;
        }
    }
}
