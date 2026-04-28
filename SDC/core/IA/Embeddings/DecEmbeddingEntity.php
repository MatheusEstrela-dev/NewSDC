<?php

declare(strict_types=1);

namespace App\Core\IA\Embeddings;

use Doctrine\ORM\Mapping as ORM;
use LLPhant\Embeddings\VectorStores\Doctrine\DoctrineEmbeddingEntityBase;
use LLPhant\Embeddings\VectorStores\Doctrine\VectorType;

#[ORM\Entity]
#[ORM\Table(name: 'dec_embeddings')]
class DecEmbeddingEntity extends DoctrineEmbeddingEntityBase
{
    #[ORM\Column(type: VectorType::VECTOR, length: 768)]
    public ?array $embedding = null;
}
