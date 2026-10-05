<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Services;

use App\Modules\Acessos\DTOs\ContaDiretorio;
use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Enums\StatusAd;
use App\Modules\Acessos\Exceptions\DiretorioRecusou;
use App\Modules\Acessos\Support\DnDiretorio;

/**
 * Defesa em profundidade alem da delegacao no AD (spec D7): nenhuma escrita em
 * conta fora da SearchBase, com adminCount=1, na lista de protegidas ou que
 * seja a propria conta de servico (que a configuracao poe sempre na lista).
 * O escopo e comparado RDN a RDN por DnDiretorio, a mesma regra do adaptador.
 */
final class GuardaDeAlvo
{
    /** Lanca DiretorioRecusou se a conta nao pode receber escrita. */
    public function assegurar(ContaDiretorio $conta): void
    {
        match ($this->status($conta)) {
            StatusAd::FORA_DO_ESCOPO => throw new DiretorioRecusou(CodigoErroDiretorio::CONTA_FORA_DO_ESCOPO),
            StatusAd::PROTEGIDA => throw new DiretorioRecusou(CodigoErroDiretorio::CONTA_PROTEGIDA),
            default => null,
        };
    }

    /** Situacao da conta vista pelo SDC (coluna-espelho status_ad). */
    public function status(ContaDiretorio $conta): StatusAd
    {
        if (! DnDiretorio::estaDentroDe($conta->dn, (string) config('acessos.diretorio.search_base', ''))) {
            return StatusAd::FORA_DO_ESCOPO;
        }
        if ($conta->protegida || $this->loginProtegido($conta->login)) {
            return StatusAd::PROTEGIDA;
        }

        return $conta->ativa ? StatusAd::ATIVA : StatusAd::DESABILITADA;
    }

    /** Lista de protegidas (sem diferenca de caixa), inclusive a conta de servico. */
    public function loginProtegido(string $login): bool
    {
        return in_array(
            mb_strtolower(trim($login)),
            (array) config('acessos.diretorio.contas_protegidas', []),
            true,
        );
    }
}
