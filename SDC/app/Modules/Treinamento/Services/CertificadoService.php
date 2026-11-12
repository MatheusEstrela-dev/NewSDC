<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Notificacoes\Enums\AcaoTrilha;
use App\Modules\Notificacoes\Services\RegistroDeAcao;
use App\Modules\Treinamento\Enums\StatusCertificado;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Models\Inscricao;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Support\CodigoVerificacao;
use App\Modules\Treinamento\Support\ConexaoTreinamento;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;

class CertificadoService
{
    private const TENTATIVAS_DE_CODIGO = 10;

    public function __construct(private readonly ConclusaoService $conclusao) {}

    /**
     * Emite o certificado quando a inscricao cumpre o criterio de conclusao do
     * tipo do treinamento (frequencia nos eventos, trilha no EAD). Chamada
     * apos cada presenca, ao finalizar o treinamento e ao concluir o ultimo
     * item da trilha; a ordem em que as condicoes se completam nao importa.
     *
     * Idempotente: um certificado ja emitido nao e refeito, e um CANCELADO
     * nunca volta a ser emitido por aqui.
     */
    public function emitirSeElegivel(Inscricao $inscricao): ?Certificado
    {
        $resultado = $this->conclusao->registrar($inscricao);

        if ($resultado === null) {
            return null;
        }

        return ConexaoTreinamento::transacao(function () use ($inscricao, $resultado) {
            $certificado = Certificado::firstOrCreate(
                ['inscricao_id' => $inscricao->id],
                ['treinamento_id' => $inscricao->treinamento_id]
            );

            if ($certificado->estaCancelado()) {
                return $certificado;
            }

            if ($certificado->status !== StatusCertificado::GERADO) {
                $certificado->aproveitamento = $resultado->aproveitamento;
                $certificado->marcarComoGerado();
            }

            return $this->garantirCodigo($certificado);
        });
    }

    /**
     * Reemite o certificado: mantem o codigo de verificacao e a data original,
     * e registra a reemissao para a validacao publica distinguir.
     */
    public function reemitir(Certificado $certificado): Certificado
    {
        if ($certificado->estaCancelado()) {
            throw new DomainException('Certificado cancelado nao pode ser reemitido.');
        }

        $certificado->forceFill([
            'status' => StatusCertificado::GERADO,
            'emitido_em' => $certificado->emitido_em ?? now(),
            'reemitido_em' => now(),
        ])->save();

        $this->garantirCodigo($certificado);

        // So a reemissao entra na trilha. A emissao automatica (emitirSeElegivel)
        // roda na fila, sem usuario autenticado: RegistroDeAcao::autor() volta
        // null e a chamada seria letra morta. Alem disso ela ja e consequencia
        // direta da conclusao, que abre o proprio card de situacao.
        $this->registrarNaTrilha($certificado, 'certificado reemitido');

        return $certificado;
    }

    /**
     * Cancela o certificado: passa a constar como cancelado na validacao
     * publica e nunca mais e reemitido ou reativado.
     */
    public function cancelar(Certificado $certificado, User $autor, string $motivo): Certificado
    {
        if ($certificado->estaCancelado()) {
            throw new DomainException('Certificado ja esta cancelado.');
        }

        $certificado->forceFill([
            'status' => StatusCertificado::CANCELADO,
            'cancelado_em' => now(),
            'cancelado_por' => $autor->id,
            'cancelamento_motivo' => $motivo,
        ])->save();

        $this->garantirCodigo($certificado);
        $this->registrarNaTrilha($certificado, 'certificado cancelado');

        return $certificado;
    }

    /**
     * Atribui o codigo de verificacao se ainda nao houver (e so entao: o codigo
     * de um certificado nunca muda). Sorteio com nova tentativa quando o banco
     * recusa por colisao no UNIQUE, cada tentativa num savepoint.
     */
    public function garantirCodigo(Certificado $certificado): Certificado
    {
        if ($certificado->codigo_verificacao !== null) {
            return $certificado;
        }

        for ($i = 0; $i < self::TENTATIVAS_DE_CODIGO; $i++) {
            $codigo = CodigoVerificacao::gerar();

            try {
                ConexaoTreinamento::transacao(
                    fn () => $certificado->forceFill(['codigo_verificacao' => $codigo])->save()
                );

                return $certificado;
            } catch (UniqueConstraintViolationException) {
                $certificado->codigo_verificacao = null;
            }
        }

        throw new DomainException('Nao foi possivel gerar um codigo de verificacao unico.');
    }

    private function registrarNaTrilha(Certificado $certificado, string $descricao): void
    {
        app(RegistroDeAcao::class)->registrarNoProtocolo(
            Treinamento::class,
            $certificado->treinamento_id,
            AcaoTrilha::Relacionado,
            $descricao,
        );
    }
}
