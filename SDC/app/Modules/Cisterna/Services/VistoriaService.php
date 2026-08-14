<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\VistoriaDTO;
use App\Modules\Cisterna\Enums\EtapaVistoria;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use App\Modules\Cisterna\Models\CisternaVistoria;
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
    public function __construct(
        private readonly NumeracaoInstalacaoService $numeracao,
    ) {}

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
