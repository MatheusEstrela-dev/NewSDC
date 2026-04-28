<?php

declare(strict_types=1);

namespace App\Core\IA\Embeddings;

use Gemini\Client as GeminiClient;
use Gemini\Data\Content;
use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\EmbeddingGenerator\EmbeddingGeneratorInterface;

class GeminiEmbeddingGenerator implements EmbeddingGeneratorInterface
{
    private GeminiClient $client;

    private const MODEL = 'text-embedding-004';

    private const EMBEDDING_LENGTH = 768;

    public function __construct()
    {
        $this->client = \Gemini::client(config('services.google.ai_key'));
    }

    public function embedText(string $text): array
    {
        $response = $this->client
            ->embeddingModel(self::MODEL)
            ->embedContent(Content::parse($text));

        return $response->embedding->values;
    }

    public function embedDocument(Document $document): Document
    {
        $document->embedding = $this->embedText($document->content);

        return $document;
    }

    public function embedDocuments(array $documents): array
    {
        return array_map(fn (Document $doc) => $this->embedDocument($doc), $documents);
    }

    public function getEmbeddingLength(): int
    {
        return self::EMBEDDING_LENGTH;
    }
}
