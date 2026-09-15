<?php

declare(strict_types=1);

namespace App\Modules\Cedec\Services;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Relatorios de contato da CEDEC estadual.
 *
 * Substitui rel_email.php, rel_email_ca.php e o botao "Telefones" que no legado era
 * href="#" e nunca foi implementado.
 *
 * O bloco de 50 existe porque 50 e o limite de destinatarios por envio do Outlook da
 * Cidade Administrativa. O legado ja blocava, mas contava ($key + 1) % 50 sobre todas
 * as linhas, inclusive as de e-mail vazio: a parte prometia 50 destinatarios e
 * entregava menos. Aqui o vazio e descartado ANTES de blocar.
 */
final class ContatoRelatorioService
{
    public const TAMANHO_BLOCO_PADRAO = 50;

    /** Colunas de e-mail, na ordem da tabela e do CSV. */
    private const CAMPOS_EMAIL = [
        'email_prefeitura',
        'email_prefeitura_2',
        'email_prefeitura_3',
    ];

    /** Colunas de telefone, na ordem da tabela e do CSV. */
    private const CAMPOS_TELEFONE = [
        'tel_prefeitura',
        'tel_prefeitura_2',
        'fax_prefeitura',
        'prefeito_telefone',
        'prefeito_celular',
    ];

    /**
     * Ordem de concatenacao DENTRO do bloco de telefone, que e diferente da ordem das
     * colunas: no bloco os telefones vem primeiro e o fax por ultimo, porque quem cola
     * isso num discador quer os numeros uteis na frente.
     */
    private const ORDEM_BLOCO_TELEFONE = [
        'tel_prefeitura',
        'tel_prefeitura_2',
        'prefeito_telefone',
        'prefeito_celular',
        'fax_prefeitura',
    ];

    private const SEPARADOR = '; ';

    /**
     * @return Collection<int, array{municipio_id: int, municipio_nome: string,
     *   email_prefeitura: ?string, email_prefeitura_2: ?string, email_prefeitura_3: ?string}>
     */
    public function emails(): Collection
    {
        return $this->linhas(self::CAMPOS_EMAIL, higienizarComoEmail: true);
    }

    /**
     * @return Collection<int, array{municipio_id: int, municipio_nome: string,
     *   tel_prefeitura: ?string, tel_prefeitura_2: ?string, fax_prefeitura: ?string,
     *   prefeito_telefone: ?string, prefeito_celular: ?string}>
     */
    public function telefones(): Collection
    {
        return $this->linhas(self::CAMPOS_TELEFONE, higienizarComoEmail: false);
    }

    /** @return array<int, array{indice: int, total: int, texto: string}> */
    public function blocosDeEmail(int $tamanho = self::TAMANHO_BLOCO_PADRAO): array
    {
        return $this->blocosDeEmailDe($this->emails(), $tamanho);
    }

    /** @return array<int, array{indice: int, total: int, texto: string}> */
    public function blocosDeTelefone(int $tamanho = self::TAMANHO_BLOCO_PADRAO): array
    {
        return $this->blocosDeTelefoneDe($this->telefones(), $tamanho);
    }

    /**
     * Bloca e-mails que o chamador JA leu, sem consultar de novo.
     *
     * Existe porque a pagina de contatos precisa das linhas e dos blocos ao mesmo
     * tempo: sem esta variante ela pagava duas leituras das 853 linhas para obter as
     * duas coisas.
     *
     * @param  Collection<int, array<string, mixed>>  $linhas
     * @return array<int, array{indice: int, total: int, texto: string}>
     */
    public function blocosDeEmailDe(Collection $linhas, int $tamanho = self::TAMANHO_BLOCO_PADRAO): array
    {
        self::garantirTamanho($tamanho);

        return self::blocar($this->contatos($linhas, self::CAMPOS_EMAIL), $tamanho);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $linhas
     * @return array<int, array{indice: int, total: int, texto: string}>
     */
    public function blocosDeTelefoneDe(Collection $linhas, int $tamanho = self::TAMANHO_BLOCO_PADRAO): array
    {
        self::garantirTamanho($tamanho);

        return self::blocar($this->contatos($linhas, self::ORDEM_BLOCO_TELEFONE), $tamanho);
    }

    /**
     * Primeira linha e o cabecalho.
     *
     * @return array<int, array<int, string>>
     */
    public function csvEmails(): array
    {
        $linhas = [['Municipio', 'E-mail institucional', 'E-mail 2', 'E-mail 3']];

        foreach ($this->emails() as $linha) {
            $linhas[] = self::linhaDeCsv($linha, self::CAMPOS_EMAIL);
        }

        return $linhas;
    }

    /** @return array<int, array<int, string>> */
    public function csvTelefones(): array
    {
        $linhas = [['Municipio', 'Telefone', 'Telefone 2', 'Fax', 'Telefone do prefeito', 'Celular do prefeito']];

        foreach ($this->telefones() as $linha) {
            $linhas[] = self::linhaDeCsv($linha, self::CAMPOS_TELEFONE);
        }

        return $linhas;
    }

    /**
     * Municipios com LEFT JOIN em compdec_prefeituras: municipio sem prefeitura
     * tambem aparece, com os contatos em null.
     *
     * A condicao de soft delete vai DENTRO do join. Num where() ela viraria filtro
     * sobre a tabela da direita e transformaria o LEFT JOIN em INNER, sumindo com todo
     * municipio que ainda nao tem linha de prefeitura.
     *
     * @param  array<int, string>  $campos
     * @return Collection<int, array<string, mixed>>
     */
    private function linhas(array $campos, bool $higienizarComoEmail): Collection
    {
        $selects = array_map(
            static fn (string $campo): string => 'compdec_prefeituras.' . $campo,
            $campos,
        );

        return DB::table('municipios')
            ->leftJoin('compdec_prefeituras', function (JoinClause $join): void {
                $join->on('compdec_prefeituras.municipio_id', '=', 'municipios.id')
                    ->whereNull('compdec_prefeituras.deleted_at');
            })
            ->select(array_merge(
                ['municipios.id as municipio_id', 'municipios.nome as municipio_nome'],
                $selects,
            ))
            ->orderBy('municipios.nome')
            ->get()
            ->map(function (object $registro) use ($campos, $higienizarComoEmail): array {
                $linha = [
                    'municipio_id' => (int) $registro->municipio_id,
                    'municipio_nome' => (string) $registro->municipio_nome,
                ];

                foreach ($campos as $campo) {
                    $valor = $registro->{$campo} === null ? null : (string) $registro->{$campo};
                    $linha[$campo] = $higienizarComoEmail
                        ? self::higienizarEmail($valor)
                        : self::higienizar($valor);
                }

                return $linha;
            });
    }

    /**
     * Achata as linhas nos contatos nao vazios, na ordem dos campos pedida. E aqui que
     * o vazio sai, ANTES de blocar -- o defeito do legado.
     *
     * @param  Collection<int, array<string, mixed>>  $linhas
     * @param  array<int, string>  $campos
     * @return array<int, string>
     */
    private function contatos(Collection $linhas, array $campos): array
    {
        $contatos = [];

        foreach ($linhas as $linha) {
            foreach ($campos as $campo) {
                if ($linha[$campo] !== null) {
                    $contatos[] = $linha[$campo];
                }
            }
        }

        return $contatos;
    }

    /**
     * @param  array<int, string>  $contatos
     * @return array<int, array{indice: int, total: int, texto: string}>
     */
    private static function blocar(array $contatos, int $tamanho): array
    {
        $blocos = [];

        foreach (array_chunk($contatos, $tamanho) as $posicao => $parte) {
            $blocos[] = [
                'indice' => $posicao + 1,
                'total' => count($parte),
                'texto' => implode(self::SEPARADOR, $parte),
            ];
        }

        return $blocos;
    }

    /**
     * @param  array<string, mixed>  $linha
     * @param  array<int, string>  $campos
     * @return array<int, string>
     */
    private static function linhaDeCsv(array $linha, array $campos): array
    {
        $valores = [(string) $linha['municipio_nome']];

        foreach ($campos as $campo) {
            $valores[] = $linha[$campo] ?? '';
        }

        return $valores;
    }

    private static function garantirTamanho(int $tamanho): void
    {
        if ($tamanho < 1) {
            throw new InvalidArgumentException('O tamanho do bloco de contatos deve ser maior ou igual a 1.');
        }
    }

    /**
     * O "-" e o sentinela de "sem telefone" que o legado gravava em
     * cedec_prefeitura.tel2. Hifen DENTRO de um numero formatado nao e afetado: o
     * teste compara a string inteira, nao um trecho.
     */
    private static function higienizar(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim($valor);

        return ($texto === '' || $texto === '-') ? null : $texto;
    }

    private static function higienizarEmail(?string $valor): ?string
    {
        $texto = self::higienizar($valor);

        return $texto === null ? null : mb_strtolower($texto);
    }
}
