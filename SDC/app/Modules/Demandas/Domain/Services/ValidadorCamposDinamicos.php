<?php

declare(strict_types=1);

namespace App\Modules\Demandas\Domain\Services;

use App\Modules\Demandas\Models\DemandaAssunto;
use Illuminate\Validation\ValidationException;

/**
 * Confere os valores da demanda contra os campos declarados no assunto.
 *
 * Formato herdado do cedec-demanda: [{label, tipo: text|checkbox}], com o valor
 * guardado pela label. Texto e obrigatorio (o legado marcava com asterisco);
 * checkbox ausente vale false. Chave que o assunto nao declara e descartada, para
 * um assunto trocado nao carregar resposta de pergunta que ninguem fez.
 */
final class ValidadorCamposDinamicos
{
    public function normalizar(?DemandaAssunto $assunto, array $valores): array
    {
        $campos = $assunto?->campos_dinamicos ?? [];
        $normalizados = [];
        $erros = [];

        foreach ($campos as $campo) {
            $label = (string) ($campo['label'] ?? '');
            if ($label === '') {
                continue;
            }
            $valor = $valores[$label] ?? null;

            if (($campo['tipo'] ?? 'text') === 'checkbox') {
                $normalizados[$label] = filter_var($valor, FILTER_VALIDATE_BOOLEAN);
                continue;
            }

            $texto = is_scalar($valor) ? trim((string) $valor) : '';
            if ($texto === '') {
                $erros['campos_customizados.'.$label] = "Preencha o campo \"{$label}\".";
                continue;
            }
            $normalizados[$label] = mb_substr($texto, 0, 500);
        }

        if ($erros !== []) {
            throw ValidationException::withMessages($erros);
        }

        return $normalizados;
    }
}
