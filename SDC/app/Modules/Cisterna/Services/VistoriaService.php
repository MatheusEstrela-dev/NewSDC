<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\VistoriaDTO;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
use App\Modules\Cisterna\Support\EscopoPerfil;
use App\Modules\Cisterna\Support\PerfilCisterna;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A cadeia fornecedor -> COMPDEC -> CEDEC.
 *
 * No legado eram seis metodos de controller com validacao e upload
 * duplicados, e a criacao da linha vazia do COMPDEC acontecia como efeito
 * colateral dentro do store do fornecedor
 * (CisternaController.php:1682). Aqui cada etapa e aberta
 * explicitamente, e a ordem e verificada.
 */
class VistoriaService
{
    /**
     * Mesmo teto do beneficiario. Sem ele, `per_page=100000` puxa as 2.136
     * vistorias com os 27.684 itens conferidos num unico request.
     */
    public const PORTE_MAXIMO_PAGINA = 100;

    public const PORTE_PADRAO_PAGINA = 25;

    public function __construct(
        private readonly NumeracaoInstalacaoService $numeracao,
    ) {}

    /**
     * Listagem paginada das vistorias, recortada pelo perfil.
     *
     * Nao existe equivalente na interface web: a tela lista as vistorias de um
     * beneficiario so, direto da relacao. Este metodo nasce para a API.
     *
     * @param  array<string, mixed>  $filtros
     */
    public function listar(
        PerfilCisterna $perfil,
        array $filtros = [],
        int $porPagina = self::PORTE_PADRAO_PAGINA,
    ): LengthAwarePaginator {
        $porPagina = max(1, min($porPagina, self::PORTE_MAXIMO_PAGINA));

        $query = CisternaVistoria::query()
            ->with([
                // Colunas restritas: a vistoria e o beneficiario juntos passam
                // de 60 colunas, e a listagem precisa de meia duzia.
                'beneficiario:id,nome,cpf,municipio_id,comunidade_id',
                'beneficiario.municipio:id,nome,uf',
                'itensConferidos',
            ]);

        EscopoPerfil::aplicarViaBeneficiario($query, $perfil);
        $this->aplicarFiltros($query, $filtros);

        return $query
            // `nulls last` explicito: data_relatorio e anulavel, e no Postgres
            // DESC coloca NULL primeiro -- a primeira pagina viria com as
            // vistorias sem data em vez das mais recentes.
            ->orderByRaw('data_relatorio desc nulls last')
            // Desempate por id mantem a paginacao estavel: sem ele, linhas com
            // a mesma data trocam de pagina entre requests e o consumidor le a
            // mesma vistoria duas vezes (ou nenhuma).
            ->orderByDesc('id')
            ->paginate($porPagina)
            ->withQueryString();
    }

    /**
     * @param  Builder<CisternaVistoria>  $query
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltros(Builder $query, array $filtros): void
    {
        $query
            ->when($filtros['etapa'] ?? null, function (Builder $q, $valor): void {
                $etapa = EtapaVistoria::tryFrom((string) $valor);

                if ($etapa !== null) {
                    $q->daEtapa($etapa);
                }
            })
            ->when($filtros['beneficiario_id'] ?? null, function (Builder $q, $id): void {
                $q->where('beneficiario_id', (int) $id);
            })
            ->when($filtros['numero_instalacao'] ?? null, function (Builder $q, $numero): void {
                $q->where('numero_instalacao', (int) $numero);
            })
            ->when($filtros['municipio_id'] ?? null, function (Builder $q, $id): void {
                $q->whereHas('beneficiario', fn (Builder $b) => $b->where('municipio_id', (int) $id));
            })
            ->when($filtros['comunidade_id'] ?? null, function (Builder $q, $id): void {
                $q->whereHas('beneficiario', fn (Builder $b) => $b->where('comunidade_id', (int) $id));
            })
            ->when($filtros['data_relatorio_inicio'] ?? null, function (Builder $q, $inicio): void {
                $q->whereDate('data_relatorio', '>=', $inicio);
            })
            ->when($filtros['data_relatorio_fim'] ?? null, function (Builder $q, $fim): void {
                $q->whereDate('data_relatorio', '<=', $fim);
            });

        // `concluida` e booleano e precisa de isset, nao de when(): when() nao
        // dispara com false, entao `concluida=false` seria ignorado e a API
        // devolveria tudo -- errado sem erro nenhum aparecer.
        if (isset($filtros['concluida'])) {
            if ((bool) $filtros['concluida']) {
                $query->concluidas();
            } else {
                $query->whereNull('concluida_em');
            }
        }
    }

    /**
     * Qual etapa pode ser trabalhada agora. Null quando a cadeia terminou.
     */
    public function etapaDisponivel(CisternaBeneficiario $beneficiario): ?EtapaVistoria
    {
        $vistorias = $beneficiario->vistorias()->get(['id', 'etapa', 'concluida_em']);

        foreach (EtapaVistoria::cases() as $etapa) {
            $vistoria = $vistorias->firstWhere('etapa', $etapa);

            // Etapa ainda nao aberta, ou aberta e nao concluida: e a atual.
            if ($vistoria === null || ! $vistoria->estaConcluida()) {
                return $etapa;
            }
        }

        return null;
    }

    public function abrir(VistoriaDTO $dto): CisternaVistoria
    {
        $beneficiario = CisternaBeneficiario::findOrFail($dto->beneficiarioId);

        $this->garantirOrdemDaCadeia($beneficiario, $dto->etapa);

        return DB::transaction(function () use ($dto, $beneficiario): CisternaVistoria {
            $atributos = $dto->toArray();

            // So a etapa do fornecedor aloca numero de QR Code.
            $atributos['numero_instalacao'] = $dto->etapa->alocaNumeroInstalacao()
                ? $this->numeracao->reservar($dto->numeroInstalacao)
                : null;

            // Snapshot do endereco: o cadastro pode mudar depois.
            $atributos['endereco'] ??= $beneficiario->endereco;
            $atributos['latitude'] ??= $beneficiario->latitude === null ? null : (float) $beneficiario->latitude;
            $atributos['longitude'] ??= $beneficiario->longitude === null ? null : (float) $beneficiario->longitude;

            $vistoria = CisternaVistoria::create($atributos);

            $this->sincronizarItens($vistoria, $dto->itens());

            return $vistoria->load('itensConferidos');
        });
    }

    public function atualizar(CisternaVistoria $vistoria, VistoriaDTO $dto): CisternaVistoria
    {
        return DB::transaction(function () use ($vistoria, $dto): CisternaVistoria {
            $atributos = $dto->toArray();

            // A etapa e o beneficiario nao mudam na edicao.
            unset($atributos['etapa'], $atributos['beneficiario_id'], $atributos['legacy_id']);

            if ($vistoria->etapa->alocaNumeroInstalacao() && $dto->numeroInstalacao !== null) {
                $atributos['numero_instalacao'] = $this->numeracao->reservar(
                    $dto->numeroInstalacao,
                    $vistoria->id,
                );
            } else {
                unset($atributos['numero_instalacao']);
            }

            $vistoria->update($atributos);

            $this->sincronizarItens($vistoria, $dto->itens());

            return $vistoria->fresh(['itensConferidos', 'beneficiario']);
        });
    }

    /**
     * Marca a etapa como concluida. O legado inferia isso de `crea_mg`
     * preenchido e diferente de vazio, verificado com whereHas aninhado.
     *
     * @throws ValidationException quando faltam dados obrigatorios da etapa
     */
    public function concluir(CisternaVistoria $vistoria): CisternaVistoria
    {
        if ($vistoria->estaConcluida()) {
            return $vistoria;
        }

        $faltando = [];

        foreach (['engenheiro_nome' => 'nome do engenheiro', 'engenheiro_crea' => 'CREA do engenheiro'] as $campo => $rotulo) {
            if (blank($vistoria->{$campo})) {
                $faltando[$campo] = "Informe o {$rotulo} antes de concluir.";
            }
        }

        if ($vistoria->etapa->exigeDadosAdministrativos()) {
            $obrigatorios = [
                'processo_sei' => 'processo SEI',
                'contrato' => 'contrato',
                'empenho' => 'empenho',
                'engenheiro_art' => 'ART',
            ];

            foreach ($obrigatorios as $campo => $rotulo) {
                if (blank($vistoria->{$campo})) {
                    $faltando[$campo] = "Informe o {$rotulo} antes de concluir a fiscalizacao da CEDEC.";
                }
            }
        }

        if ($faltando !== []) {
            throw ValidationException::withMessages($faltando);
        }

        $vistoria->update(['concluida_em' => now()]);

        return $vistoria->fresh();
    }

    /**
     * Substitui o conjunto de itens, nao acumula: o formulario envia sempre o
     * checklist completo.
     *
     * @param  array<int, \App\Modules\Cisterna\DTOs\ItemConferidoDTO>  $itens
     */
    public function sincronizarItens(CisternaVistoria $vistoria, array $itens): void
    {
        if ($itens === []) {
            return;
        }

        $vistoria->itensConferidos()->delete();

        foreach ($itens as $item) {
            $vistoria->itensConferidos()->create($item->toArray());
        }
    }

    /**
     * @throws ValidationException
     */
    private function garantirOrdemDaCadeia(CisternaBeneficiario $beneficiario, EtapaVistoria $etapa): void
    {
        if ($beneficiario->vistoriaDaEtapa($etapa) !== null) {
            throw ValidationException::withMessages([
                'etapa' => "A etapa \"{$etapa->label()}\" ja foi aberta para este beneficiario.",
            ]);
        }

        $disponivel = $this->etapaDisponivel($beneficiario);

        if ($disponivel !== $etapa) {
            $esperada = $disponivel?->label() ?? 'nenhuma etapa pendente';

            throw ValidationException::withMessages([
                'etapa' => "Etapa fora de ordem. A proxima etapa e: {$esperada}.",
            ]);
        }
    }
}
