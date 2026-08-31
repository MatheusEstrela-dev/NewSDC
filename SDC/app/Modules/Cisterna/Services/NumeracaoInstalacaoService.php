<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\Models\CisternaVistoria;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Alocacao do numero de instalacao, que e o numero impresso no QR Code
 * colado na cisterna.
 *
 * O legado montava range(1, 1800) e fazia array_diff contra todos os
 * numeros ja usados a cada abertura do formulario, deixando a escolha para o
 * navegador. Dois fornecedores simultaneos recebiam a mesma lista de
 * disponiveis, e havia dois tetos diferentes no mesmo sistema: 100 em
 * relatorio_instalacao() e 1800 em editFormFornecedor().
 *
 * Aqui a alocacao e atomica via sequence do Postgres, com o UNIQUE da coluna
 * como rede de seguranca, e sem teto.
 */
class NumeracaoInstalacaoService
{
    private const SEQUENCE = 'cisterna_numero_instalacao_seq';

    /**
     * Teto de tentativas ao consumir a sequence. Protecao contra loop caso a
     * sequence esteja muito atras do maximo em uso. 1000 tentativas resolvem
     * qualquer defasagem realista; acima disso o correto e sincronizar.
     */
    private const MAX_TENTATIVAS = 1000;

    /**
     * Proximo numero da sequence. Atomico: nextval nao volta atras nem sob
     * concorrencia, e nao participa de rollback de transacao.
     */
    public function proximoNumero(): int
    {
        $linha = DB::selectOne('SELECT nextval(?) AS numero', [self::SEQUENCE]);

        return (int) $linha->numero;
    }

    /**
     * O legado expunha isso como endpoint (cisterna/check_duplicated_qrcode).
     * Aqui e uma checagem interna: a constraint UNIQUE e quem decide.
     */
    public function numeroEstaLivre(int $numero, ?int $ignorarVistoriaId = null): bool
    {
        return ! CisternaVistoria::query()
            ->where('numero_instalacao', $numero)
            ->when($ignorarVistoriaId !== null, fn ($q) => $q->whereKeyNot($ignorarVistoriaId))
            ->exists();
    }

    /**
     * Reserva um numero. Sem numero desejado, consome a sequence e pula os
     * que ja estiverem em uso (pode acontecer depois de um ETL que importou
     * numeros acima do valor corrente da sequence).
     *
     * @throws ValidationException quando o numero desejado ja esta em uso
     */
    public function reservar(?int $numeroDesejado = null, ?int $ignorarVistoriaId = null): int
    {
        if ($numeroDesejado !== null) {
            if (! $this->numeroEstaLivre($numeroDesejado, $ignorarVistoriaId)) {
                throw ValidationException::withMessages([
                    'numero_instalacao' => 'Este QR Code ja esta vinculado a outra cisterna.',
                ]);
            }

            return $numeroDesejado;
        }

        for ($tentativa = 0; $tentativa < self::MAX_TENTATIVAS; $tentativa++) {
            $numero = $this->proximoNumero();

            if ($this->numeroEstaLivre($numero, $ignorarVistoriaId)) {
                return $numero;
            }
        }

        $this->sincronizarSequenceComOMaximo();

        return $this->proximoNumero();
    }

    /**
     * Alinha a sequence ao maior numero em uso. Chamar ao final do refino do
     * ETL: sem isso, a sequence comeca em 1 e colide com tudo o que foi
     * importado.
     */
    public function sincronizarSequenceComOMaximo(): int
    {
        $maximo = (int) (CisternaVistoria::query()->max('numero_instalacao') ?? 0);

        // is_called = true faz o proximo nextval devolver maximo + 1.
        DB::statement('SELECT setval(?, ?, true)', [self::SEQUENCE, max($maximo, 1)]);

        return $maximo;
    }
}
