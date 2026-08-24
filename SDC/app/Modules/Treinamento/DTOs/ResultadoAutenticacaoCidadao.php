<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\DTOs;

use App\Modules\Treinamento\Enums\StatusAutenticacaoCidadao;
use App\Modules\Treinamento\Models\Cidadao;

/**
 * Resultado de CidadaoAuthService::attempt().
 *
 * Carrega o Cidadao junto do status para o caso EMAIL_NAO_VERIFICADO, onde a
 * tela de login precisa da conta para abrir o fluxo de confirmacao sem uma
 * segunda consulta por CPF.
 *
 * O Cidadao SO vem preenchido quando a senha conferiu - assim nao ha caminho em
 * que o controller receba a conta de alguem sem ter provado a credencial.
 */
final readonly class ResultadoAutenticacaoCidadao
{
    private function __construct(
        public StatusAutenticacaoCidadao $status,
        public ?Cidadao $cidadao = null,
    ) {
    }

    public static function autenticado(Cidadao $cidadao): self
    {
        return new self(StatusAutenticacaoCidadao::AUTENTICADO, $cidadao);
    }

    public static function credencialInvalida(): self
    {
        return new self(StatusAutenticacaoCidadao::CREDENCIAL_INVALIDA);
    }

    public static function emailNaoVerificado(Cidadao $cidadao): self
    {
        return new self(StatusAutenticacaoCidadao::EMAIL_NAO_VERIFICADO, $cidadao);
    }

    public static function contaInativa(): self
    {
        return new self(StatusAutenticacaoCidadao::CONTA_INATIVA);
    }

    public function autenticou(): bool
    {
        return $this->status === StatusAutenticacaoCidadao::AUTENTICADO;
    }
}
