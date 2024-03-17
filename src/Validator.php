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
 * Validates dependencies for callables.
 */
class Validator
{
    /**
     * Create the validator instance.
     */
    public function __construct(
        /**
         * Dependency repository.
         */
        protected Repository $repo,
    ) {
    }

    /**
     * Validate a callable.
     */
    public function validate(callable $callable): bool
    {
        // Get reflection.
        $reflection = new \ReflectionFunction($callable);

        // Validate each parameter.
        foreach ($reflection->getParameters() as $parameter) {
            if (!$this->validateParameter($parameter)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate a parameter.
     */
    protected function validateParameter(\ReflectionParameter $parameter): bool
    {
        // Get type.
        $type = $parameter->getType();

        // Always validate untyped parameters.
        if ($type === null) {
            return true;
        }

        // Validate named type.
        if ($type instanceof \ReflectionNamedType) {
            return $this->repo->hasDependency($type->getName())
                || $parameter->isDefaultValueAvailable()
                || $parameter->allowsNull();
        }

        return false;
    }
}
