<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Support;

use App\Models\User;
use App\Modules\Treinamento\Models\Certificado;
use App\Modules\Treinamento\Models\Cidadao;

/**
 * Dados do certificado expostos na validacao publica (spec 8.6): nome
 * completo e CPF mascarado, nada alem disso de dado pessoal.
 */
final class ValidacaoDeCertificado
{
    public const VALIDO = 'VALIDO';

    public const REEMITIDO = 'REEMITIDO';

    public const CANCELADO = 'CANCELADO';

    /** @return array<string, mixed>|null null = codigo desconhecido ou certificado nao emitido */
    public function buscar(string $codigo): ?array
    {
        $certificado = Certificado::query()
            ->with(['inscricao.inscrito', 'treinamento'])
            ->where('codigo_verificacao', $codigo)
            ->first();

        if ($certificado === null || $certificado->emitido_em === null) {
            return null;
        }

        $inscricao = $certificado->inscricao;
        $treinamento = $certificado->treinamento;
        $inscrito = $inscricao->inscrito;
        $conclusao = $treinamento->isEad() ? ($inscricao->concluido_em ?? $certificado->emitido_em) : $certificado->emitido_em;

        return [
            'situacao' => $this->situacao($certificado),
            'nome' => $inscrito?->name,
            'cpf_mascarado' => $this->mascararCpf($inscrito instanceof User || $inscrito instanceof Cidadao ? $inscrito->cpf : null),
            'curso' => $treinamento->titulo,
            'carga_horaria' => $treinamento->carga_horaria,
            'modalidade' => $treinamento->tipo->getLabel(),
            'concluido_em' => $conclusao->toDateString(),
            'aproveitamento' => $certificado->aproveitamento,
        ];
    }

    private function situacao(Certificado $certificado): string
    {
        return match (true) {
            $certificado->estaCancelado() => self::CANCELADO,
            $certificado->reemitido_em !== null => self::REEMITIDO,
            default => self::VALIDO,
        };
    }

    /** 12345678901 vira ***.456.789-** */
    private function mascararCpf(?string $cpf): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $cpf);

        return strlen($digitos) === 11
            ? '***.'.substr($digitos, 3, 3).'.'.substr($digitos, 6, 3).'-**'
            : null;
    }
}
