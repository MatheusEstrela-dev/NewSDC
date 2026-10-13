<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\DTO;

use App\Modules\Decretacoes\Enums\StatusProcesso;
use App\Modules\Decretacoes\Support\Vigencia;
use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

/**
 * DTOs consolidados para o modulo de Decretacoes.
 *
 * FLUXO DE DADOS:
 *   Request (Frontend) -> DTO -> Service -> Model -> Banco de Dados
 *
 * Classes disponiveis:
 * - ProcessoRequestDTO: Dados principais do processo vindos do Request
 * - CampoData: Dados de campo com valor e tipo (inclui logica de formatacao)
 * - ItemData: Item contendo multiplos campos
 * - DesastreData: Dados de desastre com itens
 * - CategoriaData: Categoria contendo desastres
 * - MunicipioData: Municipio com protocolo e categorias
 * - DesastreSubmissionDTO: Submissao de nivel superior contendo municipios
 */

/**
 * ProcessoRequestDTO
 *
 * Dados principais do processo extraidos do Request.
 *
 * FLUXO: Request -> ProcessoRequestDTO -> EntradaProcessoService -> Processo (Model)
 */
class ProcessoRequestDTO
{
    use SafeCastingTrait;

    /** Campo do formulario (ProcessoForm.vue) => coluna do Processo. */
    private const MAPA = [
        'data_entrada'                      => 'data_entrada',
        'situacao_anormalidade'             => 'tipo_desastre',
        'data_ocorrencia'                   => 'data_ocorrencia_desastre',
        'redec_id'                          => 'redec_id',
        'status'                            => 'status',
        'analista_id'                       => 'analista',
        'n_protocolo_fide'                  => 'n_protocolo_fide',
        'n_decreto_municipal'               => 'decreto_municipal',
        'data_decreto_municipal'            => 'data_decreto_municipal',
        'data_publicacao_decreto_municipal' => 'data_publicacao_mg',
        'n_decreto_estadual'                => 'n_decreto_estadual',
        'data_decreto_estadual'             => 'data_decreto_estadual',
        'n_edicao_domg'                     => 'n_edicao_domg',
        'data_publicacao_domg'              => 'data_publicacao_domg',
        'n_portaria_federal'                => 'portaria_reconhecimento_fed',
        'data_portaria_federal'             => 'data_portaria_federal',
        'n_edicao_dou'                      => 'n_edicao_dou',
        'data_publicacao_dou'               => 'data_publicacao_dou',
        'n_processo_sei'                    => 'processo_inserido_sei',
        'observacoes'                       => 'observacoes',
    ];

    /**
     * @param  array<string, mixed>  $allData  colunas do Processo a gravar
     * @param  array<int, int>|null  $municipios  null = nao enviado (nao sincroniza)
     */
    public function __construct(
        public readonly array $allData,
        public readonly ?array $municipios = null,
        public readonly ?string $informacoesDecreto = null,
    ) {
    }

    /**
     * Cria o DTO a partir de FormRequest::validated() (Store/UpdateProcessoRequest).
     *
     * $parcial (update): so as chaves ENVIADAS entram em allData, e campo enviado
     * vazio limpa a coluna. Antes o DTO lia Request::input() -- inclusive
     * `status`, sem regra no update -- e descartava vazios, entao nenhum campo
     * podia ser limpo. Create continua sem nulos, e o prazo em branco vira o
     * padrao legal de 180 dias.
     *
     * @param  array<string, mixed>  $dados
     */
    public static function fromValidated(array $dados, bool $parcial = false): self
    {
        $mapped = [];

        foreach (self::MAPA as $campo => $coluna) {
            if (array_key_exists($campo, $dados)) {
                $mapped[$coluna] = self::vazioParaNull($dados[$campo]);
            }
        }

        if (array_key_exists('origem', $dados)) {
            $mapped['processo'] = strtoupper(self::asString($dados['origem']));
        } elseif (! $parcial) {
            $mapped['processo'] = '';
        }

        if (array_key_exists('cobrade_id', $dados) || array_key_exists('tipo_desastre_id', $dados)) {
            $mapped['tipo_desastre_id'] = self::asIntOrNull($dados['cobrade_id'] ?? null)
                ?? self::asIntOrNull($dados['tipo_desastre_id'] ?? null);
        }

        // Prazo em branco grava o padrao legal de 180 dias (SE/ECP), para que
        // vencimento/dias restantes fiquem calculaveis tambem em SQL.
        if (array_key_exists('prazo_vigencia_decreto', $dados) || ! $parcial) {
            $mapped['prazo_vigencia'] = Vigencia::prazo($dados['prazo_vigencia_decreto'] ?? null);
        }

        // Todo processo nasce em Registro (primeiro passo do fluxo e o unico em
        // que pode ser excluido). O formulario mandava 'pendente', fora do enum,
        // e o processo novo ja contava como decretacao nos cards.
        if (! $parcial && ($mapped['status'] ?? null) === null) {
            $mapped['status'] = StatusProcesso::REGISTRO->value;
        }

        $municipioId = self::asIntOrNull($dados['municipio_id'] ?? null);

        return new self(
            allData: $parcial ? $mapped : array_filter($mapped, fn ($v) => $v !== null),
            // Ausente no update = nao mexer: [] faria syncMunicipalities remover
            // todos os municipios do processo e os desastres deles.
            municipios: array_key_exists('municipio_id', $dados)
                ? ($municipioId !== null ? [$municipioId] : [])
                : ($parcial ? null : []),
            informacoesDecreto: self::asStringOrNull($dados['informacoes_decreto'] ?? null),
        );
    }

    private static function vazioParaNull(mixed $valor): mixed
    {
        return $valor === '' ? null : $valor;
    }
}

/**
 * CampoData
 *
 * Dados de campo com valor e tipo.
 * Inclui logica de formatacao de valores (anteriormente em ValueFormatter).
 *
 * FLUXO: Request (JSON) -> CampoData -> DesastreDataService -> EntradaDesastre (Model)
 */
class CampoData
{
    public function __construct(
        public readonly int $id,
        public readonly string $titulo,
        public readonly mixed $valor,
        public readonly string $tipo,
        public readonly ?int $entradaDesastreId = null,
    ) {
    }

    /**
     * Cria CampoData a partir de array (vindo do JSON do Request).
     *
     * @param array $data Dados do campo vindos do frontend
     * @return self CampoData pronto para processamento
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            titulo: $data['titulo'],
            valor: $data['valor'] ?? null,
            tipo: $data['tipo'],
            entradaDesastreId: isset($data['entrada_desastre_id']) ? (int) $data['entrada_desastre_id'] : null,
        );
    }

    /**
     * Formata o valor baseado no tipo.
     * Substitui a classe ValueFormatter standalone.
     *
     * @return mixed Valor formatado para persistencia no banco
     */
    public function getFormattedValue(): mixed
    {
        return self::formatValue($this->valor, $this->tipo);
    }

    /**
     * Metodo estatico para formatar qualquer valor baseado no tipo.
     *
     * Tipos suportados:
     * - 'number': Remove pontos e converte para inteiro
     * - 'currency': Remove formatacao BR e converte para float
     * - default: Retorna valor original
     *
     * @param mixed $value Valor original do frontend
     * @param string $type Tipo do campo
     * @return mixed Valor formatado para o banco de dados
     */
    public static function formatValue(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            'number'   => (int) str_replace('.', '', (string) $value),
            'currency' => (float) str_replace(',', '.', str_replace('.', '', (string) $value)),
            default    => $value,
        };
    }
}

/**
 * ItemData
 *
 * Item contendo multiplos campos.
 *
 * FLUXO: Request (JSON) -> ItemData -> DesastreDataService -> Processamento de campos
 */
class ItemData
{
    /**
     * @param CampoData[] $campos Lista de campos do item
     */
    public function __construct(
        public readonly int $id,
        public readonly array $campos,
    ) {
    }

    /**
     * Cria ItemData a partir de array (vindo do JSON do Request).
     *
     * @param array $data Dados do item vindos do frontend
     * @return self ItemData com campos convertidos
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            campos: array_map(fn(array $campo) => CampoData::fromArray($campo), $data['campos'] ?? []),
        );
    }
}

/**
 * DesastreData
 *
 * Dados de desastre com itens.
 * O id da categoria gravada segue por parametro no DesastreDataService; o DTO e imutavel.
 *
 * FLUXO: Request (JSON) -> DesastreData -> DesastreDataService -> EntradaCategoriaDesastre (Model)
 */
class DesastreData
{
    /**
     * @param ItemData[] $items Lista de itens do desastre
     */
    public function __construct(
        public readonly int $id,
        public readonly ?string $descricao,
        public readonly array $items,
        public readonly ?int $entradaCategoriaDesastreId = null,
    ) {
    }

    /**
     * Cria DesastreData a partir de array (vindo do JSON do Request).
     *
     * @param array $data Dados do desastre vindos do frontend
     * @return self DesastreData com itens convertidos
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            descricao: $data['descricao'] ?? null,
            items: array_map(fn(array $item) => ItemData::fromArray($item), $data['items'] ?? []),
            entradaCategoriaDesastreId: isset($data['entrada_categoria_desastre_id']) ? (int) $data['entrada_categoria_desastre_id'] : null,
        );
    }
}

/**
 * CategoriaData
 *
 * Categoria contendo desastres.
 *
 * FLUXO: Request (JSON) -> CategoriaData -> DesastreDataService -> Iteracao sobre desastres
 */
class CategoriaData
{
    /**
     * @param DesastreData[] $desastres Lista de desastres da categoria
     */
    public function __construct(
        public readonly int $id,
        public readonly array $desastres,
    ) {
    }

    /**
     * Cria CategoriaData a partir de array (vindo do JSON do Request).
     *
     * @param array $data Dados da categoria vindos do frontend
     * @return self CategoriaData com desastres convertidos
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            desastres: array_map(fn(array $desastre) => DesastreData::fromArray($desastre), $data['desastres'] ?? []),
        );
    }
}

/**
 * MunicipioData
 *
 * Municipio com protocolo FIDE e categorias de desastre.
 *
 * FLUXO: Request (JSON) -> MunicipioData -> DesastreDataService -> DecretoMunicipio (Model)
 */
class MunicipioData
{
    /**
     * @param CategoriaData[] $categorias Lista de categorias do municipio
     */
    public function __construct(
        public readonly int $id,
        public readonly ?string $nProtocoloFide,
        public readonly array $categorias,
    ) {
    }

    /**
     * Cria MunicipioData a partir de array (vindo do JSON do Request).
     *
     * @param array $data Dados do municipio vindos do frontend
     * @return self MunicipioData com categorias convertidas
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            nProtocoloFide: $data['n_protocolo_fide'] ?? null,
            categorias: array_map(fn(array $categoria) => CategoriaData::fromArray($categoria), $data['categorias'] ?? []),
        );
    }
}

/**
 * DesastreSubmissionDTO
 *
 * DTO de nivel superior para submissao de dados de desastre.
 * Contem a lista completa de municipios com seus respectivos desastres.
 *
 * FLUXO: Request (JSON) -> DesastreSubmissionDTO -> DesastreDataService -> Multiplos Models
 */
class DesastreSubmissionDTO
{
    /**
     * @param MunicipioData[] $municipios Lista de municipios com dados de desastre
     */
    public function __construct(
        public readonly array $municipios,
    ) {
    }

    /**
     * Cria DesastreSubmissionDTO a partir de array (vindo do JSON do Request).
     *
     * @param array $data Dados completos da submissao
     * @return self DTO pronto para processamento pelo DesastreDataService
     */
    public static function fromArray(array $data): self
    {
        return new self(
            municipios: array_map(fn(array $municipio) => MunicipioData::fromArray($municipio), $data['municipios'] ?? []),
        );
    }
}
