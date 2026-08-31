<?php

declare(strict_types=1);

namespace App\Services\Search\Fontes;

use App\Modules\Cisterna\Support\PerfilCisterna;
use App\Services\Search\FonteSql;
use Illuminate\Support\Facades\Auth;

/**
 * Beneficiarios do programa de cisternas.
 *
 * A maior base pesquisavel do sistema (8.099 linhas hoje) e a que estava
 * inteiramente fora da busca global.
 *
 * Duas particularidades:
 *
 * 1. CPF. A coluna guarda 11 digitos limpos e a pessoa digita
 *    "156.366.166-78". Sem normalizar, buscar pelo formato que ela LE na tela
 *    nunca casaria. O termo entra nas duas formas: cru (para nome) e so
 *    digitos (para CPF).
 *
 * 2. Escopo territorial. As quatro fontes originais nao filtravam nada, mas
 *    aqui filtrar e obrigatorio: o perfil COMPDEC enxerga apenas o proprio
 *    municipio, e uma caixa de busca sem esse recorte vazaria justamente o que
 *    a listagem protege.
 */
class FonteCisternaBeneficiarios extends FonteSql
{
    public function chave(): string
    {
        return 'cisternas';
    }

    public function permissao(): ?string
    {
        return 'cisternas.beneficiarios.view';
    }

    protected function tabela(): string
    {
        return 'cisterna_beneficiarios';
    }

    /**
     * `cpf::text` e deliberado: a coluna e `character` (bpchar) e gin_trgm_ops
     * nao a aceita nua, entao o indice dela e sobre a expressao com cast. A
     * consulta precisa usar a MESMA expressao, senao o indice existe e o
     * planejador o ignora.
     */
    protected function colunas(): array
    {
        return ['nome', 'cpf::text'];
    }

    protected function selecionar(): array
    {
        return ['id', 'municipio_id', 'situacao_obra'];
    }

    /**
     * O CPF digitado com mascara vira digitos, e o recorte do perfil entra como
     * lista de municipios. `whereIn` com lista vazia nunca acontece: perfil sem
     * municipio nenhum e o da CEDEC, que ve tudo.
     */
    protected function filtroAdicional(array &$bindings): string
    {
        $condicoes = ['deleted_at IS NULL'];

        $perfil = PerfilCisterna::deUsuario(Auth::user());

        if ($perfil->eCompdec() && $perfil->municipioId() !== null) {
            $bindings['municipio'] = $perfil->municipioId();
            $condicoes[] = 'municipio_id = :municipio';
        }

        return implode(' AND ', $condicoes);
    }

    public function buscar(string $termo, int $limite): array
    {
        // Busca pelos digitos quando o termo parece CPF mascarado; do contrario
        // o proprio termo serve para nome e para CPF ja digitado sem mascara.
        $digitos = preg_replace('/\D/', '', $termo) ?? '';

        if (strlen($digitos) >= 6 && $digitos !== $termo) {
            $termo = $digitos;
        }

        return parent::buscar($termo, $limite);
    }

    /**
     * Formatacao de SAIDA fica aqui, e nao no NormalizaEntrada: aquela classe
     * normaliza entrada -- tira mascara -- e o caminho inverso nao pertence a
     * ela.
     */
    private function cpfComMascara(?string $cpf): string
    {
        $digitos = preg_replace('/\D/', '', (string) $cpf) ?? '';

        return strlen($digitos) === 11
            ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digitos)
            : (string) $cpf;
    }

    protected function linha(object $registro): array
    {
        return [
            'id' => $registro->id,
            'title' => $registro->nome,
            'subtitle' => trim(
                $this->cpfComMascara($registro->cpf).' · '.str_replace('_', ' ', (string) $registro->situacao_obra),
                ' ·',
            ),
            'url' => route('cisternas.beneficiarios.show', $registro->id),
            'icon' => 'building',
            'tag' => 'CISTERNA',
        ];
    }
}
