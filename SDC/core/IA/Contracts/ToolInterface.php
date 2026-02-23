<?php

namespace App\Core\IA\Contracts;

interface ToolInterface
{
    public function getName(): string;

    public function getDescription(): string;

    public function getParametersSchema(): array;

    public function execute(array $parameters): mixed;

    public function validateParameters(array $parameters): bool;

    public function toFunctionDefinition(): array;
}
