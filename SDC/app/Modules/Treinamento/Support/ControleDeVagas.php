<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Modules\Treinamento\Enums\StatusInscricao;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Treinamento;
use DomainException;
use LogicException;

/**
 * Vaga so e segura com a linha travada.
 *
 * Contar inscricoes aprovadas e depois inserir, sem lock, deixa duas requests
 * simultaneas passarem pela mesma ultima vaga (a contagem de uma nao ve a
 * insercao da outra). Aqui a linha do curso (que guarda o limite) e travada com
 * SELECT ... FOR UPDATE antes da contagem, entao a segunda request espera e
 * enxerga a inscricao da primeira.
 *
 * Exige transacao aberta na conexao do Treinamento: fora dela o FOR UPDATE
 * solta o lock no fim da propria instrucao e nao protege nada.
 *
 * Sob Swoole o lock vale so para a conexao que o obteve, e a Connection e
 * resolvida a cada uso (ConexaoTreinamento): nada fica em propriedade estatica,
 * para nao vazar entre coroutines.
 */
final class ControleDeVagas
{
    /**
     * Trava o limite de vagas e devolve o que ele diz hoje.
     *
     * @return array{limite: int|null, ocupadas: int, restantes: int|null}
     *         limite/restantes null = ilimitado (numero_vagas NULL)
     */
    public static function contagemComTrava(Treinamento $curso): array
    {
        $conexao = ConexaoTreinamento::conexao();

        if ($conexao->transactionLevel() < 1) {
            throw new LogicException('ControleDeVagas exige transacao aberta na conexao do Treinamento.');
        }

        $linha = $conexao->selectOne(
            'SELECT numero_vagas FROM treinamento.treinamentos WHERE id = ? FOR UPDATE',
            [$curso->getKey()],
            false,
        );

        if ($linha === null) {
            throw new DomainException('Este treinamento nao esta mais disponivel.');
        }

        $ocupadas = Inscricao::query()
            ->porTreinamento((int) $curso->getKey())
            ->where('status', StatusInscricao::APROVADA->value)
            ->count();

        if ($linha->numero_vagas === null) {
            return ['limite' => null, 'ocupadas' => $ocupadas, 'restantes' => null];
        }

        $limite = (int) $linha->numero_vagas;

        return ['limite' => $limite, 'ocupadas' => $ocupadas, 'restantes' => max(0, $limite - $ocupadas)];
    }

    /**
     * Trava e recusa quando nao sobrou vaga. Mensagem unica para inscrever() e
     * aprovar(): o aluno e o gestor leem o mesmo motivo.
     */
    public static function exigirVagaComTrava(Treinamento $curso): void
    {
        $restantes = self::contagemComTrava($curso)['restantes'];

        if ($restantes === null || $restantes > 0) {
            return;
        }

        throw new DomainException('Este treinamento nao tem mais vagas.');
    }
}
