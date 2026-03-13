<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\DTO;

use Illuminate\Http\Request;

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
 * - DesastreData: Dados de desastre com itens (entradaCategoriaDesastreId e mutavel)
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
    public function __construct(
        public readonly array $allData,
        public readonly array $municipios = [],
        public readonly ?string $informacoesDecreto = null,
    ) {
    }

    /**
     * Cria DTO a partir do Request HTTP.
     *
     * Mapeia os campos do frontend (ProcessoForm.vue) para as colunas do modelo Processo.
     *
     * @param Request $request Request vindo do Controller
     * @return self DTO pronto para ser processado pelo Service
     */
    public static function fromRequest(Request $request): self
    {
        $municipioId = $request->input('municipio_id');

        $mapped = [
            'data_entrada'             => $request->input('data_entrada') ?: null,
            'processo'                 => strtoupper($request->input('origem', '')),
            'tipo_desastre_id'         => $request->input('cobrade_id') ?: ($request->input('tipo_desastre_id') ?: null),
            'tipo_desastre'            => $request->input('situacao_anormalidade') ?: null,
            'data_ocorrencia_desastre' => $request->input('data_ocorrencia') ?: null,
            'analista'                 => $request->input('analista_id') ?: null,
            'n_protocolo_fide'         => $request->input('n_protocolo_fide') ?: null,
            'decreto_municipal'        => $request->input('n_decreto_municipal') ?: null,
            'data_decreto_municipal'   => $request->input('data_decreto_municipal') ?: null,
            'data_publicacao_mg'       => $request->input('data_publicacao_domg') ?: null,
            'prazo_vigencia'           => $request->input('prazo_vigencia_decreto') ?: null,
            'portaria_reconhecimento_fed' => $request->input('n_portaria_federal') ?: null,
            'processo_inserido_sei'    => $request->input('n_processo_sei') ?: null,
            'observacoes'              => $request->input('observacoes') ?: null,
        ];

        return new self(
            allData: array_filter($mapped, fn($v) => $v !== null),
            municipios: $municipioId ? [(int) $municipioId] : [],
            informacoesDecreto: $request->input('informacoes_decreto'),
        );
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
 * Nota: entradaCategoriaDesastreId e mutavel (nao readonly) pois e preenchido apos criacao.
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
        public ?int $entradaCategoriaDesastreId = null,
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
