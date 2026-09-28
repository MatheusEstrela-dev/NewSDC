<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Importacao\Etapas;

use App\Modules\Demandas\Importacao\EtapaImportacao;
use App\Modules\Demandas\Importacao\MapaImportacao;
use App\Modules\Demandas\Importacao\MapaLegado;
use App\Modules\Demandas\Importacao\RelatorioEtapa;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

abstract class EtapaBase implements EtapaImportacao
{
    /**
     * Relatorio do lote em processamento, para gravar() poder registrar um
     * aviso (avisar()) sem precisar do RelatorioEtapa como parametro proprio.
     */
    private ?RelatorioEtapa $relatorioAtual = null;

    /**
     * Dry-run do lote em execucao, para uma etapa que grava arquivo fisico
     * fora da transacao do banco (ex.: anexos) saber que nao deve copiar nada.
     */
    protected bool $dryRun = false;

    /**
     * Arquivos gravados fora da transacao durante o lote atual (disco, caminho),
     * para apagar se o lote der rollback por excecao.
     *
     * @var list<array{0: string, 1: string}>
     */
    private array $arquivosDoLote = [];

    public function __construct(protected readonly MapaImportacao $mapa) {}

    /** Tabela de origem no cedec-demanda. */
    abstract protected function tabelaOrigem(): string;

    /** Tabela de destino no NewSDC, gravada no mapa. */
    abstract protected function tabelaDestino(): string;

    /**
     * Grava UMA linha. Devolve o id de destino, ou string com o motivo da
     * rejeicao. Recebe o id ja mapeado quando e atualizacao.
     */
    abstract protected function gravar(object $linha, ?int $destinoExistente): int|string;

    public function executar(bool $dryRun, ?string $desde, int $lote): RelatorioEtapa
    {
        $this->dryRun = $dryRun;
        $relatorio = new RelatorioEtapa($this->nome());

        $this->consulta($desde)->chunkById($lote, function ($linhas) use ($dryRun, $relatorio): void {
            // Um lote = uma transacao no destino. Em dry-run a transacao sempre
            // termina em rollback: o relatorio sai completo e nada fica gravado.
            $this->arquivosDoLote = [];
            DB::beginTransaction();
            try {
                foreach ($linhas as $linha) {
                    $this->processar($linha, $relatorio);
                }
                $dryRun ? DB::rollBack() : DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->apagarArquivosDoLote();
                throw $e;
            }
        });

        return $relatorio;
    }

    /**
     * Aviso que nao rejeita a linha em curso: ela e importada do mesmo jeito,
     * mas o relatorio precisa mostrar que algo faltou.
     */
    protected function avisar(string $motivo): void
    {
        $this->relatorioAtual?->avisar($motivo);
    }

    /**
     * Uma etapa que copia arquivo fisico fora da transacao do banco (anexos)
     * chama isto apos gravar, para o rollback do lote tambem apagar o arquivo.
     */
    protected function registrarArquivoGravado(string $disco, string $caminho): void
    {
        $this->arquivosDoLote[] = [$disco, $caminho];
    }

    private function apagarArquivosDoLote(): void
    {
        foreach ($this->arquivosDoLote as [$disco, $caminho]) {
            Storage::disk($disco)->delete($caminho);
        }
        $this->arquivosDoLote = [];
    }

    protected function processar(object $linha, RelatorioEtapa $relatorio): void
    {
        $this->relatorioAtual = $relatorio;
        $relatorio->lidos++;
        $id = (string) $linha->id;
        $hash = MapaLegado::hash($linha);
        // So pula quando a ultima tentativa teve sucesso: uma linha rejeitada
        // com o mesmo hash precisa ser reprocessada, pois a causa da rejeicao
        // pode ter sido corrigida em outra tabela (ex.: usuario mapeado depois).
        if ($this->mapa->hash($this->tabelaOrigem(), $id) === $hash && $this->mapa->status($this->tabelaOrigem(), $id) === 'importado') {
            $relatorio->ignorados++;

            return;
        }

        $existente = $this->mapa->alvo($this->tabelaOrigem(), $id);
        $resultado = $this->gravar($linha, $existente);
        if (is_string($resultado)) {
            $relatorio->rejeitar($resultado);
            // Mantem o target_id existente (nulo se nunca importou): se a linha
            // ja tinha sido importada antes e agora falha, o alvo continua
            // valido para uma correcao futura atualizar o mesmo registro em
            // vez de criar um duplicado.
            $this->mapa->registrar($this->tabelaOrigem(), $id, $this->tabelaDestino(), $existente, $hash, 'rejeitado', $resultado);

            return;
        }

        $existente === null ? $relatorio->importados++ : $relatorio->atualizados++;
        $this->mapa->registrar($this->tabelaOrigem(), $id, $this->tabelaDestino(), $resultado, $hash, 'importado');
    }

    protected function consulta(?string $desde): Builder
    {
        $q = $this->origem()->table($this->tabelaOrigem());
        if ($desde !== null && $this->temUpdatedAt()) {
            $q->where('updated_at', '>=', $desde);
        }

        return $q;
    }

    protected function temUpdatedAt(): bool
    {
        return true;
    }

    protected function origem(): ConnectionInterface
    {
        return DB::connection((string) config('demandas.importacao.conexao'));
    }
}
