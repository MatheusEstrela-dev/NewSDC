<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\IA\Embeddings\DecEmbeddingEntity;
use App\Core\IA\Embeddings\GeminiEmbeddingGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use LLPhant\Embeddings\VectorStores\Doctrine\DoctrineVectorStore;

class IaIndexRats extends Command
{
    protected $signature = 'ia:index-rats
                            {--limit=100 : Quantidade de RATs a indexar por execucao}
                            {--force : Reindexar mesmo que ja exista embedding}';

    protected $description = 'Indexa RATs no pgvector para busca semantica do SDC IA 2.0';

    public function handle(
        GeminiEmbeddingGenerator $embeddingGenerator,
        DoctrineVectorStore $vectorStore
    ): int {
        $limit = (int) $this->option('limit');
        $force = (bool) $this->option('force');

        $this->info("Indexando ate {$limit} RATs...");

        $rats = DB::table('rts')
            ->select(['id', 'protocolo', 'status', 'dados_gerais', 'local', 'created_at'])
            ->when(! $force, function ($q) {
                $indexados = DB::connection('pgsql')
                    ->table('dec_embeddings')
                    ->where('source_type', 'rat')
                    ->pluck('source_name')
                    ->toArray();
                $q->whereNotIn('protocolo', $indexados);
            })
            ->limit($limit)
            ->get();

        if ($rats->isEmpty()) {
            $this->info('Nenhum RAT novo para indexar.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($rats->count());
        $bar->start();

        $errors = 0;

        foreach ($rats as $rat) {
            try {
                $dadosGerais = is_string($rat->dados_gerais)
                    ? json_decode($rat->dados_gerais, true)
                    : (array) $rat->dados_gerais;

                $local = is_string($rat->local)
                    ? json_decode($rat->local, true)
                    : (array) $rat->local;

                $text = implode(' | ', array_filter([
                    'Protocolo: '.$rat->protocolo,
                    'Status: '.$rat->status,
                    'Municipio: '.($dadosGerais['local_municipio'] ?? $local['municipio'] ?? ''),
                    'Tipo: '.($dadosGerais['nat_nome_operacao'] ?? ''),
                    'COBRADE: '.($dadosGerais['nat_cobrade_id'] ?? ''),
                    'Data fato: '.($dadosGerais['data_fato'] ?? ''),
                ]));

                $entity = new DecEmbeddingEntity();
                $entity->content = $text;
                $entity->sourceName = $rat->protocolo;
                $entity->sourceType = 'rat';
                $entity->chunkNumber = 0;

                $embedded = $embeddingGenerator->embedDocument($entity);
                $vectorStore->addDocument($embedded);

                $bar->advance();
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("RAT {$rat->protocolo}: ".$e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Concluido. Indexados: '.($rats->count() - $errors).' | Erros: '.$errors);

        return self::SUCCESS;
    }
}
