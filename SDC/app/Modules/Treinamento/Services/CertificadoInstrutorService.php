<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services;

use App\Models\User;
use App\Modules\Treinamento\Enums\StatusTreinamento;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Models\CertificadoInstrutor;
use App\Modules\Treinamento\Models\Treinamento;
use App\Modules\Treinamento\Support\CodigoVerificacao;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;

/**
 * Emissao do certificado de instrutor. Emitir de novo devolve o certificado
 * que ja existe (mesmo codigo e data): reimprimir nunca gera outro.
 */
class CertificadoInstrutorService
{
    private const TENTATIVAS_DE_CODIGO = 10;

    /** Curso que ja terminou: concluido, ou com a data de termino passada, e nao cancelado. */
    public function cursoEncerrado(Treinamento $treinamento): bool
    {
        if ($treinamento->status === StatusTreinamento::CANCELADO) {
            return false;
        }

        return $treinamento->status === StatusTreinamento::CONCLUIDO
            || ($treinamento->data_fim !== null && $treinamento->data_fim->lt(today()));
    }

    public function ehProfessor(Treinamento $treinamento, User $usuario): bool
    {
        return $treinamento->professores()->where('user_id', $usuario->getKey())->exists();
    }

    /** Professor designado no curso (EAD), pelo proprio professor ou por um administrador. */
    public function emitirParaProfessor(Treinamento $treinamento, User $professor, User $autor): CertificadoInstrutor
    {
        $this->exigirEncerrado($treinamento);

        if (! $this->ehProfessor($treinamento, $professor)) {
            throw new DomainException('Esta pessoa não é professora deste curso.');
        }

        $existente = fn (): ?CertificadoInstrutor => $this->doCursoQuery($treinamento)
            ->where('user_id', $professor->getKey())
            ->first();

        return $existente() ?? $this->criar($treinamento, (int) $professor->getKey(), (string) $professor->name, $autor, $existente);
    }

    /** Instrutor sem usuario no sistema (presencial): so o nome, informado por um administrador. */
    public function emitirPorNome(Treinamento $treinamento, string $nome, User $autor): CertificadoInstrutor
    {
        $this->exigirEncerrado($treinamento);

        $nome = trim((string) preg_replace('/\s+/u', ' ', $nome));
        if ($nome === '') {
            throw new DomainException('Informe o nome do instrutor.');
        }

        $existente = fn (): ?CertificadoInstrutor => $this->doCursoQuery($treinamento)
            ->whereNull('user_id')
            ->whereRaw('lower(nome) = lower(?)', [$nome])
            ->first();

        return $existente() ?? $this->criar($treinamento, null, $nome, $autor, $existente);
    }

    /** @return Collection<int, CertificadoInstrutor> */
    public function doCurso(Treinamento $treinamento): Collection
    {
        return $this->doCursoQuery($treinamento)->orderBy('nome')->get();
    }

    private function doCursoQuery(Treinamento $treinamento): Builder
    {
        return CertificadoInstrutor::query()->where('treinamento_id', $treinamento->getKey());
    }

    private function exigirEncerrado(Treinamento $treinamento): void
    {
        if (! $this->cursoEncerrado($treinamento)) {
            throw new DomainException('O certificado de instrutor sai quando o curso termina.');
        }
    }

    /**
     * Sorteia um codigo que nao exista em nenhum dos dois tipos de certificado.
     * Violacao de unicidade = outra requisicao emitiu o mesmo certificado antes
     * (devolve o dela) ou, raro, o codigo colidiu (sorteia outro).
     *
     * @param  callable(): ?CertificadoInstrutor  $existente
     */
    private function criar(Treinamento $treinamento, ?int $userId, string $nome, User $autor, callable $existente): CertificadoInstrutor
    {
        for ($i = 0; $i < self::TENTATIVAS_DE_CODIGO; $i++) {
            $codigo = CodigoVerificacao::gerar();
            if (Certificado::query()->where('codigo_verificacao', $codigo)->exists()) {
                continue;
            }

            try {
                return CertificadoInstrutor::query()->create([
                    'treinamento_id' => $treinamento->getKey(),
                    'user_id' => $userId,
                    'nome' => $nome,
                    'codigo_verificacao' => $codigo,
                    'emitido_em' => now(),
                    'emitido_por' => $autor->getKey(),
                ]);
            } catch (UniqueConstraintViolationException) {
                if (($ja = $existente()) !== null) {
                    return $ja;
                }
            }
        }

        throw new DomainException('Não foi possível gerar um código de verificação único.');
    }
}
