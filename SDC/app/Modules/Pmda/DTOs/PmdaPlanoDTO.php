<?php

declare(strict_types=1);

namespace App\Modules\Pmda\DTOs;

/**
 * Payload de formulario do PMDA: as 28 colunas que a tela preenche.
 *
 * Cobre a lista INTEIRA de proposito. A versao anterior tinha 7 campos e nao era
 * usada em lugar nenhum; roteada no update, teria descartado 21 campos em silencio.
 * Se um campo novo entrar no Request e nao entrar aqui, o teste
 * PmdaPlanoDTOTest::test_dto_cobre_todos_os_campos_dos_requests quebra.
 *
 * O ponto delicado e a diferenca entre "nao enviado" e "enviado vazio":
 *
 *   - aba ISS submetida sozinha  -> nome_prefeito ausente  -> NAO mexer na coluna
 *   - usuario apagou o conteudo  -> nome_prefeito = null    -> GRAVAR null
 *
 * Um `array_filter` de nulls (como havia antes) trata os dois casos igual e torna
 * impossivel limpar um campo pela tela. Por isso o DTO carrega, junto dos valores,
 * a lista de chaves que estavam PRESENTES na entrada -- calculada com
 * array_key_exists, nao isset, que devolve false para null.
 */
readonly class PmdaPlanoDTO
{
    /**
     * Coluna => cast. Fonte unica: alimenta fromArray(), toArray() e o teste de
     * paridade com os Requests.
     */
    private const CAMPOS = [
        'motivo'              => 'string',
        'acoes'               => 'string',
        'qtd_caminhao'        => 'int',
        'pop_at_municipio'    => 'int',
        'acao_decreto_se'     => 'bool',
        'acao_caminhao_pipa'  => 'bool',
        'acao_cestas_basicas' => 'bool',
        'justificativa_apoio' => 'string',
        'cobra_iss'           => 'bool',
        'num_lei_iss'         => 'string',
        'aliquota_iss'        => 'float',
        'resp_cob_iss'        => 'string',
        'nome_prefeito'       => 'string',
        'tel_prefeitura'      => 'string',
        'tel_prefeito'        => 'string',
        'cel_prefeito'        => 'string',
        'endereco'            => 'string',
        'bairro'              => 'string',
        'cep'                 => 'string',
        'email_prefeitura'    => 'string',
        'populacao'           => 'int',
        'pop_rural'           => 'int',
        'area'                => 'float',
        'compdec_coordenador' => 'string',
        'compdec_decreto'     => 'string',
        'compdec_lei'         => 'string',
        'compdec_tel'         => 'string',
        'compdec_email'       => 'string',
    ];

    /**
     * @param  array<string, mixed>  $valores    coluna => valor ja convertido
     * @param  list<string>          $presentes  colunas que vieram na entrada
     */
    private function __construct(
        private array $valores,
        private array $presentes,
    ) {}

    /** @return list<string> */
    public static function colunas(): array
    {
        return array_keys(self::CAMPOS);
    }

    /**
     * @param  array<string, mixed>  $data  saida de $request->validated()
     */
    public static function deFormulario(array $data): self
    {
        $valores = [];
        $presentes = [];

        foreach (self::CAMPOS as $coluna => $cast) {
            // array_key_exists e nao isset: `['cep' => null]` significa "limpar o
            // CEP", e isset() devolveria false justamente nesse caso.
            if (! array_key_exists($coluna, $data)) {
                continue;
            }

            $presentes[] = $coluna;
            $valores[$coluna] = self::converter($data[$coluna], $cast);
        }

        return new self($valores, $presentes);
    }

    private static function converter(mixed $valor, string $cast): mixed
    {
        // String vazia chega como null pelo ConvertEmptyStringsToNull, mas o DTO
        // nao depende disso: trata os dois como ausencia de valor e preserva o
        // null, que e o que limpa a coluna.
        if ($valor === null || $valor === '') {
            return null;
        }

        return match ($cast) {
            'int'   => (int) $valor,
            'float' => (float) $valor,
            'bool'  => (bool) $valor,
            default => (string) $valor,
        };
    }

    /** Uma coluna especifica veio na entrada (mesmo que vazia). */
    public function tem(string $coluna): bool
    {
        return in_array($coluna, $this->presentes, true);
    }

    public function valor(string $coluna): mixed
    {
        return $this->valores[$coluna] ?? null;
    }

    /**
     * So as colunas presentes na entrada. Chave ausente = coluna intocada no
     * update; chave com null = coluna limpa.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->valores;
    }
}
