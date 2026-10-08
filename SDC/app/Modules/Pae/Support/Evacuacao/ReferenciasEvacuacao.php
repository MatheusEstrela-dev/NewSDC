<?php

declare(strict_types=1);

namespace App\Modules\Pae\Support\Evacuacao;

/**
 * Coerencia entre as listas da conferencia: rotas citam setores existentes,
 * todo setor pertence a alguma rota e cada rota chega por no maximo um acesso.
 */
final class ReferenciasEvacuacao
{
    /** @return array<string, string> campo => mensagem */
    public static function erros(array $entrada): array
    {
        $erros = [];
        $idsSetores = array_column($entrada['setores'], 'id');
        $idsRotas = array_column($entrada['rotas'], 'id');
        $usados = [];

        foreach ($entrada['rotas'] as $i => $rota) {
            if (count($rota['setores']) !== count(array_unique($rota['setores']))) {
                $erros["rotas.$i.setores"] = 'A rota repete um setor.';
            }
            foreach ($rota['setores'] as $setorId) {
                $usados[$setorId] = true;
                if (! in_array($setorId, $idsSetores, true)) {
                    $erros["rotas.$i.setores"] = "O setor {$setorId} não existe.";
                }
            }
        }

        foreach ($entrada['setores'] as $i => $setor) {
            if (! isset($usados[$setor['id']])) {
                $erros["setores.$i.id"] = "O setor {$setor['id']} não pertence a nenhuma rota.";
            }
        }

        $acessoDaRota = [];
        foreach ($entrada['acessos'] as $i => $acesso) {
            foreach ($acesso['rotas'] as $rotaId) {
                if (! in_array($rotaId, $idsRotas, true)) {
                    $erros["acessos.$i.rotas"] = "A rota {$rotaId} não existe.";
                } elseif (isset($acessoDaRota[$rotaId])) {
                    $erros["acessos.$i.rotas"] = "A rota {$rotaId} já está ligada ao acesso {$acessoDaRota[$rotaId]}.";
                } else {
                    $acessoDaRota[$rotaId] = $acesso['id'];
                }
            }
        }

        return $erros;
    }
}
