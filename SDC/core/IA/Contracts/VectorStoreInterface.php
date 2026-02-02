<?php

namespace App\Core\IA\Contracts;

interface VectorStoreInterface
{
    /**
     * Adiciona um documento ou texto ao banco de vetores (Embedding)
     */
    public function addDocument(string $id, string $content, array $metadata = []): void;

    /**
     * Busca os fragmentos mais relevantes com base em uma consulta semântica
     */
    public function search(string $query, int $limit = 5): array;

    /**
     * Remove um documento do banco
     */
    public function deleteDocument(string $id): void;
}