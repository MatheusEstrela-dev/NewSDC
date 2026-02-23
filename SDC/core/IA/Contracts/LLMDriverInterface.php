<?php

namespace App\Core\IA\Contracts;

use Generator;

interface LLMDriverInterface
{
    public function chat(array $messages, array $options = []): string;

    public function chatStream(array $messages, array $options = []): Generator;

    public function chatWithTools(array $tools, array $messages, array $options = []): array;

    public function embedding(string $text): array;

    public function getDriverName(): string;

    public function getModel(): string;
}
