<?php

declare(strict_types=1);

namespace App\Modules\Tdap\Support;

use App\Modules\Tdap\Contracts\PontosCaptacaoDoPmda;
use App\Modules\Tdap\Models\PontoCaptacao;
use Illuminate\Support\Collection;

/**
 * Quais pontos de captacao um cronograma pode usar.
 *
 * Fonte unica da regra: o formulario (opcoes), a validacao (recusas) e a
 * gravacao (origens) perguntam aqui, para a tela nunca oferecer um ponto que o
 * backend recusa.
 *
 * REGRA (config tdap.ponto_captacao.exige_pmda_aprovado, ligada por padrao):
 * o ponto tem que constar, com situacao ATIVO, no PMDA vigente do municipio do
 * cronograma (ver PontosCaptacaoDoPmda). A excecao e o ponto que o cronograma
 * JA TEM: o acervo veio do legado sem vinculo nenhum com PMDA, e sem a excecao
 * nenhum rascunho antigo poderia ser salvo de novo.
 *
 * Com a flag desligada vale a regra anterior: qualquer ponto ativo do municipio.
 */
final class PoliticaPontoCaptacao
{
    public function __construct(
        private readonly PontosCaptacaoDoPmda $pmda,
    ) {}

    public function exigePmda(): bool
    {
        return (bool) config('tdap.ponto_captacao.exige_pmda_aprovado', true);
    }

    /**
     * Pontos selecionaveis no formulario, agrupados por municipio.
     *
     * @param  list<int>  $municipioIds
     * @param  list<int>  $vinculadosIds  pontos que o cronograma em edicao ja tem
     * @return array<int, list<array<string, mixed>>>
     */
    public function opcoesPorMunicipio(array $municipioIds, array $vinculadosIds = []): array
    {
        if ($municipioIds === []) {
            return [];
        }

        $vinculos = $this->pmda->vinculosVigentes($municipioIds);

        return PontoCaptacao::query()
            ->whereIn('municipio_id', $municipioIds)
            ->where(function ($q) use ($vinculos, $vinculadosIds): void {
                $this->exigePmda()
                    ? $q->whereIn('id', array_keys($vinculos))
                    : $q->where('ativo', true);
                $q->orWhereIn('id', $vinculadosIds);
            })
            ->orderBy('nome')
            ->get(['id', 'municipio_id', 'nome', 'tipo', 'capacidade', 'latitude', 'longitude'])
            ->map(fn (PontoCaptacao $p) => self::apresentar(
                $p,
                ($vinculos[$p->id] ?? null)?->pmdaPlanoId,
                ($vinculos[$p->id] ?? null)?->protocolo,
            ))
            ->groupBy('municipio_id')
            ->map(fn ($grupo) => $grupo->values()->all())
            ->all();
    }

    /**
     * Pontos recusados para o municipio, com o motivo.
     *
     * @param  list<int>  $pontoIds
     * @param  list<int>  $jaVinculados
     * @return array<int, string> posicao em $pontoIds => mensagem
     */
    public function recusas(int $municipioId, array $pontoIds, array $jaVinculados = []): array
    {
        $pontos = PontoCaptacao::query()->whereKey($pontoIds)->get(['id', 'municipio_id', 'nome'])->keyBy('id');
        $vinculos = $this->exigePmda() ? $this->pmda->vinculosVigentes([$municipioId]) : [];

        $recusas = [];
        foreach ($pontoIds as $posicao => $pontoId) {
            $ponto = $pontos->get($pontoId);
            if ($ponto === null) {
                continue; // inexistente ou excluido: a regra `exists` ja responde
            }

            if ((int) $ponto->municipio_id !== $municipioId) {
                $recusas[$posicao] = "O ponto {$ponto->nome} nao pertence ao Municipio do cronograma.";

                continue;
            }

            if (! $this->exigePmda() || isset($vinculos[$pontoId]) || in_array($pontoId, $jaVinculados, true)) {
                continue;
            }

            $recusas[$posicao] = $vinculos === []
                ? 'O Municipio nao tem PMDA aprovado com ponto de captacao ATIVO. Cadastre os pontos no PMDA.'
                : "O ponto {$ponto->nome} nao consta no PMDA aprovado do Municipio (ou esta SECO).";
        }

        return $recusas;
    }

    /**
     * De que PMDA cada ponto veio, para gravar no vinculo. Null = fora do PMDA
     * vigente (acervo legado ou flag desligada).
     *
     * @param  list<int>  $pontoIds
     * @return array<int, ?int> id do ponto => id do plano
     */
    public function origens(int $municipioId, array $pontoIds): array
    {
        $vinculos = $this->pmda->vinculosVigentes([$municipioId]);

        $origens = [];
        foreach ($pontoIds as $pontoId) {
            $origens[$pontoId] = ($vinculos[$pontoId] ?? null)?->pmdaPlanoId;
        }

        return $origens;
    }

    /**
     * Pontos ja vinculados a um cronograma (relacao pontosCaptacao, com pivot),
     * com o PMDA gravado no vinculo -- a origem que justificou o ponto, nao o
     * PMDA vigente hoje. E o formato do snapshot `stored_pmda_ponto` e da ficha.
     *
     * @param  Collection<int, PontoCaptacao>  $pontos
     * @return list<array<string, mixed>>
     */
    public function apresentarVinculados(Collection $pontos): array
    {
        $protocolos = $this->pmda->protocolos(
            $pontos->pluck('pivot.pmda_plano_id')->filter()->map(fn ($id) => (int) $id)->values()->all(),
        );

        return $pontos
            ->map(function (PontoCaptacao $ponto) use ($protocolos): array {
                $planoId = $ponto->pivot?->pmda_plano_id !== null ? (int) $ponto->pivot->pmda_plano_id : null;

                return self::apresentar($ponto, $planoId, $planoId !== null ? ($protocolos[$planoId] ?? null) : null);
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function apresentar(PontoCaptacao $ponto, ?int $pmdaPlanoId, ?string $protocolo): array
    {
        return [
            'id'            => $ponto->id,
            'municipio_id'  => $ponto->municipio_id,
            'nome'          => $ponto->nome,
            'tipo'          => $ponto->tipo,
            'tipo_nome'     => $ponto->tipo_nome,
            'capacidade'    => (float) $ponto->capacidade,
            'latitude'      => $ponto->latitude,
            'longitude'     => $ponto->longitude,
            'pmda_plano_id' => $pmdaPlanoId,
            'protocolo'     => $protocolo,
            'no_pmda'       => $pmdaPlanoId !== null,
        ];
    }
}
