<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Modules\Treinamento\DTOs\EstadoTrilha;
use App\Modules\Treinamento\DTOs\ResultadoConclusao;
use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Models\AvaliacaoCurso;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use App\Modules\Treinamento\Support\CriteriosDeConclusao;

/**
 * Avalia o criterio do tipo do treinamento e congela o resultado na inscricao
 * (concluido_em + aproveitamento, spec 8.7). Gravado uma unica vez, sob lock
 * da linha: eventos repetidos ou concorrentes nao regravam nem divergem.
 *
 * Concluir = requisitos do criterio (trilha no EAD, frequencia nos eventos)
 * E a avaliacao do curso enviada (Treinamento::exigeAvaliacaoParaConcluir).
 * O gate da avaliacao fica so aqui: listener do ultimo item, Job de emissao,
 * treinamento:avaliar-conclusoes e CertificadoService::emitirSeElegivel
 * passam por registrar() e herdam a regra. Quem ja tem concluido_em gravado
 * (legado, anterior a regra) segue concluido, com ou sem avaliacao.
 */
class ConclusaoService
{
    public function __construct(private readonly CriteriosDeConclusao $criterios) {}

    /**
     * Resultado congelado, ou null enquanto a inscricao nao cumpre o criterio
     * ou ainda nao avaliou o curso.
     */
    public function registrar(Inscricao $inscricao): ?ResultadoConclusao
    {
        return ConexaoTreinamento::transacao(function () use ($inscricao) {
            $travada = Inscricao::query()->with('treinamento')->lockForUpdate()->findOrFail($inscricao->id);

            // Curso pausado ou desativado nao conclui ninguem novo; quem ja concluiu segue com o resultado congelado e o certificado.
            if ($travada->concluido_em === null && ! $travada->treinamento->situacaoEfetiva()->permiteConclusao()) {
                return null;
            }

            $resultado = $this->criterios->para($travada->treinamento)->avaliar($travada);
            if ($resultado === null) {
                return null;
            }

            // Etapa final: requisitos cumpridos, mas sem a avaliacao do curso
            // a conclusao nao e gravada. Nunca reabre quem ja concluiu.
            if ($travada->concluido_em === null && $this->aguardaAvaliacao($travada)) {
                return null;
            }

            $gravadaAgora = false;
            if ($travada->concluido_em === null) {
                // Update direto: nao dispara a trilha de acoes do model nem passa
                // pelo $fillable (a conclusao so se escreve aqui).
                $gravadaAgora = Inscricao::query()->whereKey($travada->id)->whereNull('concluido_em')->update([
                    'concluido_em' => $resultado->concluidoEm,
                    'aproveitamento' => $resultado->aproveitamento,
                ]) === 1;
                $travada->refresh();
            }

            $inscricao->forceFill([
                'concluido_em' => $travada->concluido_em,
                'aproveitamento' => $travada->aproveitamento,
            ])->syncOriginalAttributes(['concluido_em', 'aproveitamento']);

            return new ResultadoConclusao($travada->concluido_em, $travada->aproveitamento, $gravadaAgora);
        });
    }

    /**
     * Tudo o que a conclusao exige menos a avaliacao: e o que libera a etapa
     * final (a avaliacao so abre no fim do curso). Leitura, sem lock e sem
     * gravar. Inscricao ja concluida (inclusive legado) cumpre por definicao.
     *
     * $trilha: estado da trilha ja calculado por quem chama (sala, listas),
     * para o EAD nao percorrer a trilha de novo. "Trilha completa" no
     * EstadoTrilha e a mesma condicao do ConclusaoPorTrilha.
     */
    public function requisitosCumpridos(Inscricao $inscricao, ?EstadoTrilha $trilha = null): bool
    {
        if ($inscricao->status !== StatusInscricao::APROVADA) {
            return false;
        }
        if ($inscricao->concluido_em !== null) {
            return true;
        }

        $inscricao->loadMissing('treinamento');
        if (! $inscricao->treinamento->situacaoEfetiva()->permiteConclusao()) {
            return false;
        }

        if ($trilha !== null && $inscricao->treinamento->isEad()) {
            return $trilha->completa();
        }

        return $this->criterios->para($inscricao->treinamento)->avaliar($inscricao) !== null;
    }

    private function aguardaAvaliacao(Inscricao $inscricao): bool
    {
        return $inscricao->treinamento->exigeAvaliacaoParaConcluir()
            && ! AvaliacaoCurso::query()->where('inscricao_id', $inscricao->id)->exists();
    }
}
