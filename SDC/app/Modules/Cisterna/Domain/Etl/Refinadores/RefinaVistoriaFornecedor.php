<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Domain\Etl\Refinadores;

use App\Modules\Cisterna\Domain\Etl\Coordenada;
use App\Modules\Cisterna\Domain\Etl\DeduplicaVistorias;
use App\Modules\Cisterna\Domain\Etl\MapaItensLegado;
use App\Modules\Cisterna\Domain\Etl\RegistroEtl;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Support\NormalizaEntrada;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class RefinaVistoriaFornecedor implements Refinador
{
    public function __construct(
        private readonly DeduplicaVistorias $dedup,
    ) {}

    public function recurso(): string
    {
        return 'vistorias';
    }

    public function tabelaLegado(): string
    {
        return 'sinc_cisterna_rel_fornecedor';
    }

    public function refinar(array $doc, int $legacyId, bool $dryRun): void
    {
        // Reenvio do mesmo formulario. O legado nao prevenia double-submit, e o
        // dominio tem UNIQUE (beneficiario_id, etapa): sem descartar a copia
        // aqui, ela chega no insert e vira violacao de constraint. Medido na
        // carga real: 856 linhas para 791 beneficiarios.
        if (! in_array($legacyId, $this->vencedores(), true)) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Reenvio do mesmo formulario: outra linha do mesmo beneficiario e mais completa.');

            return;
        }
        $beneficiarioId = CisternaBeneficiario::where('legacy_id', (int) ($doc['cisterna_id'] ?? 0))->value('id');

        if ($beneficiarioId === null) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Beneficiario de origem '.($doc['cisterna_id'] ?? 'null').' nao encontrado. '
                .'Refinar beneficiarios antes.', $doc);

            return;
        }

        $numero = $this->inteiroOuNulo($doc['num_instalacao'] ?? null);

        if ($dryRun) {
            RegistroEtl::ignorado($this->recurso(), $this->tabelaLegado(), $legacyId,
                "dry-run: criaria vistoria de fornecedor, numero {$numero}.");

            return;
        }

        // Numero repetido no legado (nao havia UNIQUE): a primeira linha
        // vence, a segunda entra como erro com o payload preservado.
        if ($numero !== null) {
            $conflito = CisternaVistoria::where('numero_instalacao', $numero)
                ->where(fn ($q) => $q->where('etapa', '!=', EtapaVistoria::FORNECEDOR->value)
                    ->orWhere('legacy_id', '!=', $legacyId))
                ->first();

            if ($conflito !== null) {
                RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                    "Numero de instalacao {$numero} ja usado pela vistoria #{$conflito->id}. "
                    .'Duplicata no legado.', $doc);

                return;
            }
        }

        $atributos = [
            'beneficiario_id' => (int) $beneficiarioId,
            'etapa' => EtapaVistoria::FORNECEDOR->value,
            'numero_instalacao' => $numero,
            'engenheiro_nome' => $this->texto($doc['nome_eng_relatorio'] ?? null, 150),
            'engenheiro_crea' => $this->texto($doc['crea_mg_eng'] ?? null, 30),
            'data_relatorio' => $this->data($doc['data_relatorio'] ?? null),
            'local_relatorio' => $this->texto($doc['municipio'] ?? null, 255),
            'endereco' => $this->texto($doc['endereco'] ?? null, 150),
            'bairro' => $this->texto($doc['bairro'] ?? null, 100),
            // Parser proprio, e nao NormalizaEntrada::decimal: a coluna do
            // legado e texto livre. Aqui sao 11 linhas gravadas sem separador
            // decimal, que estouram numeric(10,7) e derrubariam a vistoria
            // inteira, e 90 com sufixo de mascara (-14.707095_), que o
            // is_numeric() descartaria em silencio.
            'latitude' => Coordenada::latitude($doc['latitude'] ?? null),
            'longitude' => Coordenada::longitude($doc['longitude'] ?? null),
            'observacoes' => $this->texto($doc['obs_instal_relatorio'] ?? null, 1000),
            // No legado a conclusao era inferida de crea_mg preenchido e
            // diferente de vazio.
            'concluida_em' => $this->concluidaEm($doc['crea_mg_eng'] ?? null, $doc['data_relatorio'] ?? null),
            'legacy_id' => $legacyId,
        ];

        try {
            DB::transaction(function () use ($atributos, $doc, $legacyId): void {
                $existente = CisternaVistoria::where('etapa', EtapaVistoria::FORNECEDOR->value)
                    ->where('legacy_id', $legacyId)
                    ->first();

                if ($existente !== null) {
                    $existente->update($atributos);
                    $this->sincronizarItens($existente, $doc);
                    RegistroEtl::atualizado($this->recurso(), $this->tabelaLegado(), $legacyId, $existente->id);

                    return;
                }

                // O observer marca situacao_obra como instalado.
                $criada = CisternaVistoria::create($atributos);
                $this->sincronizarItens($criada, $doc);
                RegistroEtl::inserido($this->recurso(), $this->tabelaLegado(), $legacyId, $criada->id);
            });
        } catch (Throwable $e) {
            RegistroEtl::erro($this->recurso(), $this->tabelaLegado(), $legacyId,
                'Falha ao gravar vistoria: '.$e->getMessage(), $doc);
        }
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    private function sincronizarItens(CisternaVistoria $vistoria, array $doc): void
    {
        $vistoria->itensConferidos()->delete();

        foreach (MapaItensLegado::paraEtapa(EtapaVistoria::FORNECEDOR, $doc) as $linha) {
            $vistoria->itensConferidos()->create($linha);
        }
    }

    private function concluidaEm(mixed $crea, mixed $data): ?string
    {
        if (trim((string) ($crea ?? '')) === '') {
            return null;
        }

        return $this->data($data) ?? now()->toDateTimeString();
    }

    private function texto(mixed $valor, int $limite): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : mb_substr($texto, 0, $limite);
    }

    private function inteiroOuNulo(mixed $valor): ?int
    {
        $digitos = preg_replace('/\D/', '', (string) ($valor ?? '')) ?? '';

        return $digitos === '' ? null : (int) $digitos;
    }

    private function data(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        if ($texto === '' || str_starts_with($texto, '0000')) {
            return null;
        }

        try {
            return CarbonImmutable::parse($texto)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * legacy_ids que sobrevivem a deduplicacao, memoizados: a lista sai de uma
     * varredura da tabela inteira e nao muda no meio da carga.
     *
     * @var array<int, int>|null
     */
    private ?array $vencedores = null;

    /**
     * @return array<int, int>
     */
    private function vencedores(): array
    {
        return $this->vencedores ??= $this->dedup->vencedores($this->tabelaLegado(), 'cisterna_id');
    }
}
