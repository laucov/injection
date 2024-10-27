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
 * 
 * @deprecated 2.0.0 Resolve arguments with `Resolver` and store them.
 */
class Validator
{
    /**
     * Manually allowed or forbidden types.
     * 
     * @var array<string, bool>
     */
    protected array $allowed = [];

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
     * Allow a type.
     * 
     * Allows the type even if the repository doesn't have it.
     */
    public function allow(string $type): void
    {
        $this->allowed[$type] = true;
    }

    /**
     * Remove a previously manually allowed type.
     */
    public function disallow(string $type): void
    {
        unset($this->allowed[$type]);
    }

    /**
     * Forbid a type.
     * 
     * Disallow the type even if the repository has it.
     */
    public function forbid(string $type): void
    {
        $this->allowed[$type] = false;
    }

    /**
     * Validate a callable.
     */
    public function validate(array|callable $callable): bool
    {
        // Get reflection.
        $reflection = is_array($callable)
            ? new \ReflectionMethod(...$callable)
            : new \ReflectionFunction($callable);

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
            $name = $type->getName();
            $is_allowed = $this->allowed[$name] ?? null;
            return $is_allowed === true
                || ($this->repo->hasDependency($name) && $is_allowed !== false)
                || $parameter->isDefaultValueAvailable()
                || $parameter->allowsNull();
        }

        return false;
    }
}
