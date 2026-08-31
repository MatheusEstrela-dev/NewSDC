<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Database\QueryBudgetGuard;
use App\Modules\Cisterna\Enums\ResponsavelPipa;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export dos beneficiarios, com as mesmas 39 colunas do
 * App\Exports\ExportCisterna do legado.
 *
 * O legado usava maatwebsite/excel com ShouldQueue e chunk de 1000, gerando
 * .xlsx. A dependencia nao existe no NewSDC; o padrao daqui e CSV streamado
 * (8 metodos `export(): StreamedResponse` em AjudaHumanitaria, Decretacoes e
 * Demandas). O chunk de 1000 e preservado via lazy(), entao a memoria fica
 * constante mesmo com dezenas de milhares de linhas.
 */
class BeneficiarioExportService
{
    /**
     * Cabecalhos na ordem exata do legado (ExportCisterna::headings).
     *
     * @var array<int, string>
     */
    public const COLUNAS = [
        'Identificacao',
        'Municipio',
        'Status',
        'Comunidade',
        'Nome',
        'Endereco',
        'Latitude',
        'Longitude',
        'Cpf',
        'Data de nascimento',
        'Cadastro Unico',
        'Quantidade de pessoas',
        'Renda',
        'Renda Per Capita',
        'Moradia',
        'Outra Moradia',
        'Comprimento do telhado',
        'Largura do telhado',
        'Area do telhado',
        'Comprimento da testada',
        'Numero de caidas do telhado',
        'Tipo de cobertura',
        'Outra cobertura',
        'Existe fogao a lenha',
        'Medida do telhado sem fogao',
        'Testada sem fogao',
        'Atendimento por caminhao pipa',
        'Defesa Civil',
        'Exercito',
        'Particular',
        'Prefeitura',
        'Outros',
        'Descricao do outros',
        'Observacoes',
        'Observacao da ressalva',
        'Nome do agente',
        'CPF do agente',
        'Nome do engenheiro',
        'Crea do engenheiro',
    ];

    private const TAMANHO_DO_LOTE = 1000;

    public function __construct(
        private readonly BeneficiarioService $beneficiarios,
        private readonly QueryBudgetGuard $orcamentoDeQueries,
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function streamCsv(PerfilCisterna $perfil, array $filtros = []): StreamedResponse
    {
        $nomeArquivo = 'cisterna-beneficiarios-'.now()->format('Y-m-d_His').'.csv';

        // O lazy() abaixo faz ceil(N/1000) rodadas de 4 queries, o que passa do
        // warn_at do guard por tamanho de base e nao por N+1. Isentar aqui evita
        // um aviso falso em toda exportacao.
        $this->orcamentoDeQueries->isentar();

        return response()->streamDownload(function () use ($perfil, $filtros): void {
            $saida = fopen('php://output', 'wb');

            // BOM UTF-8: sem ele o Excel em pt-BR quebra os acentos.
            fwrite($saida, "\xEF\xBB\xBF");

            fputcsv($saida, self::COLUNAS, ';');

            foreach ($this->consultar($perfil, $filtros) as $beneficiario) {
                fputcsv($saida, $this->mapear($beneficiario), ';');
            }

            fclose($saida);
        }, $nomeArquivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return \Illuminate\Support\LazyCollection<int, CisternaBeneficiario>
     */
    private function consultar(PerfilCisterna $perfil, array $filtros): \Illuminate\Support\LazyCollection
    {
        // Reaproveita o escopo por perfil e os filtros da listagem: o export
        // do legado tambem passava pelo mesmo aplicarFiltros().
        return $this->beneficiarios
            ->consultaParaExport($perfil, $filtros)
            ->with(['municipio:id,nome', 'comunidade:id,nome', 'atendimentosPipa'])
            ->orderBy('cpf')
            ->lazy(self::TAMANHO_DO_LOTE);
    }

    /**
     * @return array<int, string|int|float|null>
     */
    private function mapear(CisternaBeneficiario $b): array
    {
        $responsaveis = $b->atendimentosPipa->keyBy(fn ($a): string => $a->responsavel->value);

        $temResponsavel = fn (ResponsavelPipa $r): string => $responsaveis->has($r->value) ? 'Sim' : 'Nao';

        return [
            $b->id,
            $b->municipio?->nome,
            $b->situacao_analise->label(),
            $b->comunidade?->nome,
            $b->nome,
            $b->endereco,
            $b->latitude,
            $b->longitude,
            $b->cpf,
            $b->data_nascimento?->format('d/m/Y'),
            $b->cadastro_unico,
            $b->qtd_pessoas,
            $b->renda,
            $b->renda_per_capita,
            $b->tipo_moradia,
            $b->tipo_moradia_outro,
            $b->comprimento_telhado,
            $b->largura_telhado,
            $b->area_telhado,
            $b->comprimento_testada,
            $b->num_caidas_telhado,
            $b->cobertura_telhado,
            $b->cobertura_outro,
            $this->simNao($b->possui_fogao_lenha),
            $b->medida_telhado_area_fogao,
            $b->testada_disp_parte_fogao,
            $this->simNao($b->atendido_por_pipa),
            $temResponsavel(ResponsavelPipa::DEFESA_CIVIL),
            $temResponsavel(ResponsavelPipa::EXERCITO),
            $temResponsavel(ResponsavelPipa::PARTICULAR),
            $temResponsavel(ResponsavelPipa::PREFEITURA),
            $temResponsavel(ResponsavelPipa::OUTROS),
            $responsaveis->get(ResponsavelPipa::OUTROS->value)?->descricao,
            $b->observacoes,
            $b->situacao_analise_obs,
            $b->agente_nome,
            $b->agente_cpf,
            $b->engenheiro_nome,
            $b->engenheiro_crea,
        ];
    }

    private function simNao(?bool $valor): string
    {
        return $valor === true ? 'Sim' : 'Nao';
    }
}
