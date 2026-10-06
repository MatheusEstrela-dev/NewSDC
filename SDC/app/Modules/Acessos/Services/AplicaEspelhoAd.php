<?php

declare(strict_types=1);

namespace App\Modules\Acessos\Services;

use App\Modules\Acessos\DTOs\ContaDiretorio;
use App\Modules\Acessos\Enums\StatusAd;
use App\Modules\Acessos\Models\CadastroAcesso;

/**
 * Grava no cadastro o que o AD mostrou (colunas-espelho). Nunca toca o
 * `status` local de aprovacao; o object_guid so e gravado quando ainda nao
 * existe e nenhum outro cadastro o tem.
 */
final class AplicaEspelhoAd
{
    private const TAMANHO_DN = 500;

    public function aplicar(CadastroAcesso $cadastro, ContaDiretorio $conta, StatusAd $status): void
    {
        $atributos = [
            'dn_ad' => mb_substr($conta->dn, 0, self::TAMANHO_DN),
            'conta_ativa_ad' => $conta->ativa,
            'bloqueada_ad' => $conta->bloqueada,
            'troca_senha_pendente_ad' => $conta->trocaSenhaPendente,
            'status_ad' => $status,
            'ad_sincronizado_em' => now(),
        ];
        if ($cadastro->object_guid === null && ! $this->guidDeOutroCadastro($cadastro, $conta->objectGuid)) {
            $atributos['object_guid'] = $conta->objectGuid;
        }

        $cadastro->forceFill($atributos)->save();
    }

    /** Sem conta na SearchBase pelo GUID/login: so o status_ad e a hora da leitura. */
    public function naoEncontrada(CadastroAcesso $cadastro): void
    {
        $cadastro->forceFill([
            'status_ad' => StatusAd::NAO_ENCONTRADA,
            'ad_sincronizado_em' => now(),
        ])->save();
    }

    private function guidDeOutroCadastro(CadastroAcesso $cadastro, string $objectGuid): bool
    {
        return CadastroAcesso::query()
            ->where('object_guid', $objectGuid)
            ->whereKeyNot($cadastro->getKey())
            ->exists();
    }
}
