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

use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use RuntimeException;

/**
 * Resolves dependencies for instances and callables.
 */
class Resolver
{
    /**
     * Create the resolver instance.
     */
    public function __construct(
        /**
         * Dependency repository.
         */
        protected Repository $repo,
    ) {
    }

    /**
     * Call a function or method resolving its dependencies.
     */
    public function call(callable $callable): mixed
    {
        return $callable(...$this->resolve($callable));
    }

    /**
     * Create a new instance resolving its class constructor dependencies.
     * 
     * @template T
     * @param class-string<T> $class_name
     * @return T
     */
    public function instantiate(string $class_name): mixed
    {
        if (!method_exists($class_name, '__construct')) {
            return new $class_name();
        }
        $arguments = $this->resolve([$class_name, '__construct']);
        return new $class_name(...$arguments);
    }

    /**
     * Get resolved arguments for the given function or method.
     */
    public function resolve(array|callable $callable): array
    {
        $reflection = is_array($callable)
            ? new ReflectionMethod(...$callable)
            : new ReflectionFunction($callable);
        return array_merge(...array_map(
            [$this, 'resolveParameter'],
            $reflection->getParameters(),
        ));
    }

    /**
     * Resolve a parameter of a named type.
     */
    protected function resolveNamedType(ReflectionParameter $parameter, ReflectionNamedType $type): array
    {
        $name = $type->getName();
        if ($this->repo->hasDependency($name)) {
            $arguments[] = $this->repo->getValue($name);
            if ($parameter->isVariadic()) {
                while ($this->repo->hasValue($name)) {
                    $arguments[] = $this->repo->getValue($name);
                }
            }
            return $arguments;
            // return $parameter->isVariadic()
            //     ? $this->repo->getValues($name)
            //     : $this->repo->getValue($name);
        } elseif ($parameter->isDefaultValueAvailable()) {
            return [$parameter->getDefaultValue()];
        } elseif ($parameter->allowsNull()) {
            return [null];
        } else {
            $message = 'Could not resolve parameter of type %s.';
            throw new \RuntimeException(sprintf($message, (string) $type));
        }
    }

    /**
     * Resolve a single parameter.
     */
    protected function resolveParameter(ReflectionParameter $parameter): array
    {
        $type = $parameter->getType();
        if ($type === null) {
            return $this->resolveUnknownType($parameter);
        } elseif ($type instanceof ReflectionNamedType) {
            return $this->resolveNamedType($parameter, $type);
        } elseif ($type instanceof ReflectionUnionType) {
            throw new RuntimeException('Cannot resolve union types.');
        } elseif ($type instanceof ReflectionIntersectionType) {
            throw new RuntimeException('Cannot resolve intersection types.');
        } else {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException('Unhandled parameter type.');
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Resolve a parameter of an unspecified type.
     */
    protected function resolveUnknownType(ReflectionParameter $parameter): array
    {
        if ($parameter->isVariadic()) {
            return [];
        } elseif ($parameter->isDefaultValueAvailable()) {
            return [$parameter->getDefaultValue()];
        } else {
            return [null];
        }
    }
}
