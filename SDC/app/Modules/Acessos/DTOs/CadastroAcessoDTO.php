<?php

declare(strict_types=1);

namespace App\Modules\Acessos\DTOs;

use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

/**
 * Dados do cadastro de acesso vindos do formulario (CadastroAcessoRequest).
 *
 * Os quatro DTOs ja existentes no modulo (ChecagemDiretorio, ContaDiretorio,
 * ReferenciaConta, ResultadoOperacao) sao da integracao com o AD; este e o
 * contrato de ESCRITA do cadastro HTTP (plano_dtos.md, P1 de Acessos). O
 * espelho do AD (object_guid, conta_ativa_ad...) nunca passa por aqui.
 */
final readonly class CadastroAcessoDTO
{
    use SafeCastingTrait;

    public function __construct(
        public string $nome,
        public string $tipoDocumento,
        public string $documento,
        public string $cpf,
        public ?string $loginAd = null,
        public ?string $emailCorporativo = null,
        public ?string $emailPessoal = null,
        public ?string $telefoneMesa = null,
        public ?string $telefoneWhatsapp = null,
        public ?string $setor = null,
        public ?string $posto = null,
        public ?string $cargo = null,
        public ?string $observacoesTi = null,
    ) {}

    /** @param array<string, mixed> $dados validated() */
    public static function fromValidated(array $dados): self
    {
        return new self(
            nome: self::asString($dados['nome'] ?? null),
            tipoDocumento: self::asString($dados['tipo_documento'] ?? null),
            documento: self::asString($dados['documento'] ?? null),
            cpf: self::asString($dados['cpf'] ?? null),
            loginAd: self::asStringOrNull($dados['login_ad'] ?? null),
            emailCorporativo: self::asStringOrNull($dados['email_corporativo'] ?? null),
            emailPessoal: self::asStringOrNull($dados['email_pessoal'] ?? null),
            telefoneMesa: self::asStringOrNull($dados['telefone_mesa'] ?? null),
            telefoneWhatsapp: self::asStringOrNull($dados['telefone_whatsapp'] ?? null),
            setor: self::asStringOrNull($dados['setor'] ?? null),
            posto: self::asStringOrNull($dados['posto'] ?? null),
            cargo: self::asStringOrNull($dados['cargo'] ?? null),
            observacoesTi: self::asStringOrNull($dados['observacoes_ti'] ?? null),
        );
    }

    /**
     * O formulario envia o cadastro inteiro (create e edit), entao null aqui e
     * campo apagado pelo usuario e deve ser gravado.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'nome' => $this->nome,
            'tipo_documento' => $this->tipoDocumento,
            'documento' => $this->documento,
            'cpf' => $this->cpf,
            'login_ad' => $this->loginAd,
            'email_corporativo' => $this->emailCorporativo,
            'email_pessoal' => $this->emailPessoal,
            'telefone_mesa' => $this->telefoneMesa,
            'telefone_whatsapp' => $this->telefoneWhatsapp,
            'setor' => $this->setor,
            'posto' => $this->posto,
            'cargo' => $this->cargo,
            'observacoes_ti' => $this->observacoesTi,
        ];
    }
}
