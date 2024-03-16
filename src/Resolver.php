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
     * Call a function or method automatically resolving its dependencies.
     */
    public function call(callable $callable): mixed
    {
        // if (is_string($callable)) {
        //     // @todo Global function or static method...
        // } elseif (is_array($callable)) {
        //     // @todo Instance method or static method...
        // } elseif (is_object($callable)) {
        //     // @todo Closure or invokable instance...
        // }

        // Get parameters.
        $reflection = new \ReflectionFunction($callable);
        $parameters = $reflection->getParameters();

        // Parse parameter types.
        $arguments = [];
        foreach ($parameters as $parameter) {
            $type = $parameter->getType();
            if ($type === null) {
                // Handle untyped parameter.
                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                } else {
                    $arguments[] = null;
                }
            } elseif ($type instanceof \ReflectionNamedType) {
                // Handle typed parameter.
                $name = $type->getName();
                if ($this->repo->hasDependency($name)) {
                    // Handle valid type.
                    $arguments[] = $this->repo->getValue($name);
                    if ($parameter->isVariadic()) {
                        while ($this->repo->hasValue($name)) {
                            $arguments[] = $this->repo->getValue($name);
                        }
                    }
                } elseif ($parameter->isDefaultValueAvailable()) {
                    // Handle invalid type with default value.
                    $arguments[] = $parameter->getDefaultValue();
                } elseif ($type->allowsNull()) {
                    // Handle invalid nullable type.
                    $arguments[] = null;
                }
            }
        }

        return $callable(...$arguments);
    }
}
