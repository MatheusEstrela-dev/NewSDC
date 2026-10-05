<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Services;

use App\Modules\Acessos\DTOs\ContaDiretorio;
use App\Modules\Acessos\Enums\CodigoErroDiretorio;
use App\Modules\Acessos\Enums\StatusAd;
use App\Modules\Acessos\Exceptions\DiretorioRecusou;

/**
 * Defesa em profundidade alem da delegacao no AD (spec D7): nenhuma escrita em
 * conta fora da SearchBase, com adminCount=1, na lista de protegidas ou que
 * seja a propria conta de servico (que a configuracao poe sempre na lista).
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
        if (! $this->dentroDaSearchBase($conta->dn)) {
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

    /** DN descendente da SearchBase, comparando RDN a RDN (caixa e espacos normalizados). */
    private function dentroDaSearchBase(string $dn): bool
    {
        $base = self::rdns((string) config('acessos.diretorio.search_base', ''));
        $alvo = self::rdns($dn);
        if ($base === null || $alvo === null || count($alvo) <= count($base)) {
            return false;
        }

        return array_slice($alvo, -count($base)) === $base;
    }

    /**
     * Separa o DN nas virgulas que nao estao escapadas nem entre aspas, para
     * que um CN com virgula nao se passe por um RDN da SearchBase.
     *
     * @return list<string>|null null quando o DN esta vazio ou malformado
     */
    private static function rdns(string $dn): ?array
    {
        $partes = [];
        $atual = '';
        $escapado = false;
        $entreAspas = false;

        foreach (mb_str_split($dn) as $caractere) {
            if ($escapado) {
                $atual .= $caractere;
                $escapado = false;
            } elseif ($caractere === '\\') {
                $atual .= $caractere;
                $escapado = true;
            } elseif ($caractere === '"') {
                $atual .= $caractere;
                $entreAspas = ! $entreAspas;
            } elseif ($caractere === ',' && ! $entreAspas) {
                $partes[] = $atual;
                $atual = '';
            } else {
                $atual .= $caractere;
            }
        }
        if ($escapado || $entreAspas) {
            return null;
        }
        $partes[] = $atual;

        $normalizados = [];
        foreach ($partes as $rdn) {
            $rdn = (string) preg_replace('/^([^=]+?)\s*=\s*/u', '$1=', mb_strtolower(trim($rdn, ' ')));
            if (! str_contains($rdn, '=') || str_starts_with($rdn, '=')) {
                return null;
            }
            $normalizados[] = $rdn;
        }

        return $normalizados;
    }
}
