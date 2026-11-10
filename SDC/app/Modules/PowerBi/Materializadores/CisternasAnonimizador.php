<?php

declare(strict_types=1);

namespace App\Modules\PowerBi\Materializadores;

use App\Modules\Cisterna\Enums\ResponsavelPipa;
use App\Modules\Cisterna\Models\CisternaBeneficiario;
use RuntimeException;

/**
 * Linha anonimizada de bi_cisternas.beneficiarios, por ALLOWLIST de campos.
 *
 * LGPD: sem nome, endereco, latitude/longitude, telefone, nascimento,
 * observacoes e dados de agente/engenheiro. CPF e cadastro unico viram HMAC
 * sha256 com sal (POWERBI_HASH_SAL): so servem para contar distintos.
 */
final class CisternasAnonimizador
{
    private const NUMERICAS = [
        'renda', 'renda_per_capita', 'comprimento_telhado', 'largura_telhado', 'area_telhado',
        'comprimento_testada', 'medida_telhado_area_fogao', 'testada_disp_parte_fogao',
    ];

    private const DIRETAS = [
        'id', 'municipio_id', 'qtd_pessoas', 'tipo_moradia', 'num_caidas_telhado', 'cobertura_telhado',
        'possui_fogao_lenha', 'atendido_por_pipa',
    ];

    /** @return array<string, mixed> */
    public function linha(CisternaBeneficiario $b): array
    {
        $responsaveis = $b->atendimentosPipa->keyBy(fn ($a): string => $a->responsavel->value);

        $row = [];
        foreach (self::DIRETAS as $coluna) {
            $row[$coluna] = $b->getAttribute($coluna);
        }
        foreach (self::NUMERICAS as $coluna) {
            $valor = $b->getAttribute($coluna);
            $row[$coluna] = $valor === null ? null : (float) $valor;
        }

        $row['municipio'] = $b->municipio?->nome;
        $row['comunidade'] = $b->comunidade?->nome;
        $row['situacao_analise'] = $b->situacao_analise->label();
        $row['cpf_hash'] = $this->hash($b->cpf);
        $row['cadastro_unico_hash'] = $this->hash($b->cadastro_unico);
        foreach (ResponsavelPipa::cases() as $r) {
            $row['pipa_'.$r->value] = $responsaveis->has($r->value);
        }

        return $row;
    }

    /**
     * HMAC sha256 dos digitos do identificador, no literal hexadecimal de bytea;
     * null para vazio. Deterministico: o mesmo CPF gera o mesmo hash.
     */
    public function hash(?string $identificador): ?string
    {
        $digitos = preg_replace('/\D+/', '', (string) $identificador);
        if ($digitos === '' || $digitos === null) {
            return null;
        }

        return '\x'.hash_hmac('sha256', $digitos, $this->sal());
    }

    private function sal(): string
    {
        $sal = (string) config('powerbi.hash_sal', '');
        if ($sal === '') {
            throw new RuntimeException('POWERBI_HASH_SAL nao definido: sem sal o hash de CPF seria reversivel por dicionario.');
        }

        return $sal;
    }
}
