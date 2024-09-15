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
    public function call(array|callable $callable): mixed
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
        // Check if has a constructor method.
        if (!method_exists($class_name, '__construct')) {
            return new $class_name();
        }

        // Resolve arguments.
        $arguments = $this->resolve([$class_name, '__construct']);

        return new $class_name(...$arguments);
    }

    /**
     * Resolve the given callable or class method parameters.
     */
    public function resolve(array|callable $callable): array
    {
        $reflection = is_array($callable)
            ? new \ReflectionMethod(...$callable)
            : new \ReflectionFunction($callable);
        return $this->getArguments($reflection);
    }

    /**
     * Resolve arguments for the given callable.
     */
    protected function getArguments(
        \ReflectionFunctionAbstract $reflection,
    ): array {
        // Get parameters.
        $parameters = $reflection->getParameters();

        // Parse parameters.
        $arguments = [];
        foreach ($parameters as $param) {
            $this->pushArgument($arguments, $param);
        }

        return $arguments;
    }

    /**
     * Push one or more items to the argument list from a reflection parameter.
     */
    protected function pushArgument(
        array &$arguments,
        \ReflectionParameter $parameter,
    ): void {
        // Get type.
        $type = $parameter->getType();

        // Handle untyped parameter.
        if ($type === null) {
            $this->pushUntypedArgument($arguments, $parameter);
            return;
        }

        // Handle named type parameter.
        if ($type instanceof \ReflectionNamedType) {
            $this->pushNamedTypeArgument($arguments, $type, $parameter);
            return;
        }

        // Fail if there are intersection or union type parameters.
        $message = 'Cannot resolve union or intersection type parameter %s.';
        throw new \RuntimeException(sprintf($message, (string) $type));
    }

    /**
     * Push one or more items to the argument list with a named type.
     */
    protected function pushNamedTypeArgument(
        array &$arguments,
        \ReflectionNamedType $type,
        \ReflectionParameter $parameter,
    ): void {
        // Get type name.
        $name = $type->getName();

        // Handle valid type.
        if ($this->repo->hasDependency($name)) {
            $arguments[] = $this->repo->getValue($name);
            if ($parameter->isVariadic()) {
                while ($this->repo->hasValue($name)) {
                    $arguments[] = $this->repo->getValue($name);
                }
            }
            return;
        }

        // Check for default value.
        if ($parameter->isDefaultValueAvailable()) {
            $arguments[] = $parameter->getDefaultValue();
            return;
        }

        // Check nullability
        if ($type->allowsNull()) {
            $arguments[] = null;
            return;
        }

        // Fail to resolve.
        $message = 'Could not resolve required argument of type %s.';
        throw new \RuntimeException(sprintf($message, $name));
    }

    /**
     * Push one or more items to the argument list without a type.
     */
    protected function pushUntypedArgument(
        array &$arguments,
        \ReflectionParameter $parameter,
    ): void {
        // Check for default value.
        if ($parameter->isDefaultValueAvailable()) {
            $arguments[] = $parameter->getDefaultValue();
            return;
        }

        // Push null.
        $arguments[] = null;
    }
}
