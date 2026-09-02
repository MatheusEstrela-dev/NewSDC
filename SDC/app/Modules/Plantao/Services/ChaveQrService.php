<?php

declare(strict_types=1);

namespace App\Modules\Plantao\Services;

use App\Models\User;
use App\Modules\Plantao\Exceptions\ReservaInvalidaException;
use App\Modules\Plantao\Models\Viatura;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Str;

/**
 * QR Code impresso na etiqueta do chaveiro. O conteudo codificado e o
 * `qr_token` da viatura -- opaco e estavel, seguindo Cisterna\Services\
 * QrCodeService e Treinamento\Services\GeradorQrCodeService.
 *
 * O QR identifica a VIATURA, nunca a reserva: a etiqueta e colada uma vez e
 * dura enquanto o carro existir. Quem tem direito a chave e decidido no
 * momento do scan, contra a agenda -- e nao pelo papel na mao de quem escaneia.
 *
 * RESOLVER NAO ESCREVE. resolver() so responde qual e o proximo ato daquela
 * chave; a escrita acontece quando o formulario de saida ou de retorno e
 * enviado. Sem essa separacao, um scan acidental no bolso abriria movimentacao
 * sem hodometro -- e hodometro perdido nao se recupera depois.
 */
class ChaveQrService
{
    private const TAMANHO = 300;

    private const MARGEM = 10;

    /** Proximo ato possivel para uma chave escaneada. */
    public const ACAO_CHECKIN = 'CHECKIN';

    public const ACAO_CHECKOUT = 'CHECKOUT';

    public function __construct(
        private readonly ReservaViaturaService $reservaService
    ) {
    }

    /**
     * Emite (ou reaproveita) o token da etiqueta.
     *
     * Idempotente de proposito: reimprimir a etiqueta de uma viatura nao pode
     * invalidar as que ja estao coladas. Trocar o token e ato explicito, feito
     * por rotacionarToken().
     */
    public function garantirToken(Viatura $viatura): string
    {
        if ($viatura->qr_token !== null && $viatura->qr_token !== '') {
            return $viatura->qr_token;
        }

        $token = $this->gerarToken();
        $viatura->update(['qr_token' => $token]);

        return $token;
    }

    /**
     * Invalida a etiqueta anterior. Usado quando a etiqueta fisica e perdida
     * com o chaveiro: quem achar o adesivo antigo nao escaneia mais nada.
     *
     * SO COM A VIATURA LIVRE. A etiqueta e um objeto fisico colado no chaveiro,
     * e trocar o token nao troca o adesivo -- so mata o que esta la. Duas
     * situacoes em que isso quebra alguem, e as duas sao recusadas:
     *
     *  - VIATURA NA RUA: o adesivo saiu na mao do condutor. Ele volta, escaneia
     *    um QR que o sistema nao reconhece mais e fica sem caminho para devolver
     *    a chave -- enquanto a etiqueta que funciona esta na tela de quem ficou.
     *
     *  - VIATURA RESERVADA: mesmo modo de falhar, adiado. O agente chega no
     *    horario, pega o chaveiro com o adesivo antigo (porque ninguem
     *    reimprimiu e recolou) e leva "etiqueta nao reconhecida" na viatura que
     *    ele proprio reservou.
     *
     * A saida para etiqueta extraviada com reserva marcada e cancelar a reserva,
     * trocar e reservar de novo -- ato explicito e com autor registrado, o mesmo
     * caminho que libera a saida de uma viatura reservada.
     *
     * @throws ReservaInvalidaException quando a viatura esta em transito ou reservada
     */
    public function rotacionarToken(Viatura $viatura): string
    {
        $movimentacao = $this->reservaService->movimentacaoAberta($viatura->id);

        if ($movimentacao !== null) {
            throw new ReservaInvalidaException(sprintf(
                'A viatura %s esta na rua com %s desde %s. Trocar a etiqueta agora invalidaria o adesivo que saiu com a chave. Faca a troca apos a devolucao.',
                $viatura->placa,
                $movimentacao->condutor_nome,
                $movimentacao->saida_em?->format('d/m H:i') ?? 'horario nao registrado',
            ));
        }

        $reserva = $viatura->reservaAgendada()->first();

        if ($reserva !== null) {
            throw new ReservaInvalidaException(sprintf(
                'A viatura %s esta reservada por %s de %s a %s. Trocar a etiqueta deixaria o adesivo do chaveiro invalido para quem vier retirar. Cancele a reserva antes de trocar.',
                $viatura->placa,
                $reserva->agente_nome,
                $reserva->inicio_previsto->format('d/m H:i'),
                $reserva->fim_previsto->format('d/m H:i'),
            ));
        }

        $token = $this->gerarToken();
        $viatura->update(['qr_token' => $token]);

        return $token;
    }

    /**
     * @return string binario PNG
     */
    public function png(Viatura $viatura): string
    {
        return (new PngWriter)->write($this->construir($viatura))->getString();
    }

    public function svg(Viatura $viatura): string
    {
        return (new SvgWriter)->write($this->construir($viatura))->getString();
    }

    /**
     * Traduz o token lido em "o que fazer agora", para o agente que escaneou.
     *
     * As quatro respostas possiveis:
     *
     *   viatura sem saida aberta + reserva vigente do agente -> CHECKIN
     *   viatura sem saida aberta + sem reserva               -> recusa
     *   saida aberta do proprio agente                       -> CHECKOUT
     *   saida aberta de outra pessoa                         -> recusa
     *
     * @return array{acao: string, viatura: Viatura, reserva: \App\Modules\Plantao\Models\ViaturaReserva|null, movimentacao: \App\Modules\Plantao\Models\ViaturaMovimentacao|null}
     *
     * @throws ReservaInvalidaException com o texto que o agente le na tela
     */
    public function resolver(string $token, User $agente): array
    {
        $viatura = $this->localizarPorToken($token);

        if ($viatura === null) {
            throw new ReservaInvalidaException(
                'Etiqueta nao reconhecida. Confira se o QR Code e de uma viatura cadastrada.'
            );
        }

        $movimentacao = $this->reservaService->movimentacaoAberta($viatura->id);

        if ($movimentacao !== null) {
            if ($movimentacao->condutor_id !== $agente->id) {
                throw new ReservaInvalidaException(sprintf(
                    'Viatura %s esta com %s desde %s.',
                    $viatura->placa,
                    $movimentacao->condutor_nome,
                    $movimentacao->saida_em?->format('d/m H:i') ?? 'horario nao registrado',
                ));
            }

            return [
                'acao' => self::ACAO_CHECKOUT,
                'viatura' => $viatura,
                'reserva' => $this->reservaService->reservaEmUso($viatura->id),
                'movimentacao' => $movimentacao,
            ];
        }

        $reserva = $this->reservaService->reservaVigente($viatura->id, $agente->id);

        if ($reserva === null) {
            throw new ReservaInvalidaException(sprintf(
                'Voce nao tem reserva vigente para a viatura %s. Agende antes de retirar a chave.',
                $viatura->placa,
            ));
        }

        return [
            'acao' => self::ACAO_CHECKIN,
            'viatura' => $viatura,
            'reserva' => $reserva,
            'movimentacao' => null,
        ];
    }

    public function localizarPorToken(string $token): ?Viatura
    {
        $token = trim($token);

        if ($token === '') {
            return null;
        }

        return Viatura::query()
            ->where('qr_token', $token)
            ->where('ativo', true)
            ->first();
    }

    /**
     * 32 caracteres de alfabeto seguro para URL. Nao e id nem sequencial: a
     * etiqueta da viatura 7 nao deve ser deduzivel a partir da viatura 6.
     */
    private function gerarToken(): string
    {
        return Str::lower(Str::random(32));
    }

    private function construir(Viatura $viatura): QrCode
    {
        return new QrCode(
            data: $this->garantirToken($viatura),
            size: self::TAMANHO,
            margin: self::MARGEM,
        );
    }
}
