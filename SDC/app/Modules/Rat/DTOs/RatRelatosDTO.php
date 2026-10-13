<?php

declare(strict_types=1);

namespace App\Modules\Rat\DTOs;

use App\Modules\Shared\DTOs\Traits\SafeCastingTrait;

/**
 * Payload composto do RAT (formulario, rascunho, API e app mobile): um DTO por
 * relato, montado UMA vez na fronteira HTTP.
 *
 * Antes cada Service (createWithData, saveDraft, manageRelatos) e o update()
 * do controller reconstruiam os DTOs a partir do array da requisicao, com
 * regras de presenca repetidas e ligeiramente diferentes. Agora o Controller
 * entrega este objeto e o Service so persiste (plano_dtos.md, P0 do RAT).
 *
 * null em um relato significa "nao enviado": o Service nao toca nele. Se uma
 * lista (recursos/envolvidos) enviada substitui a gravada ou e mesclada por id
 * depende do fluxo -- decisao do Service, nao do DTO.
 */
final readonly class RatRelatosDTO
{
    use SafeCastingTrait;

    /**
     * @param  list<RatRecursoDTO>|null  $recursos
     * @param  list<RatEnvolvidoDTO>|null  $envolvidos
     */
    public function __construct(
        public ?RatDadosGeraisDTO $dadosGerais = null,
        public ?array $recursos = null,
        public ?array $envolvidos = null,
        public ?RatVistoriaDTO $vistoria = null,
        public ?RatHistoricoDTO $historico = null,
        public bool $finalizar = false,
        // Ha conteudo de registro (nao so o esqueleto vazio)? Decide o evento
        // RegistroCompletoV1 na criacao.
        public bool $temConteudo = false,
    ) {}

    /** @param array<string, mixed> $dados validated() do formulario/API */
    public static function fromArray(array $dados): self
    {
        $secoesDadosGerais = ['dadosGerais', 'comunicacao', 'local', 'endereco'];

        $temDadosGerais = array_filter(
            $secoesDadosGerais,
            fn (string $secao) => isset($dados[$secao]),
        ) !== [];

        return new self(
            dadosGerais: $temDadosGerais ? RatDadosGeraisDTO::fromArray($dados) : null,
            recursos:    self::lista($dados, 'recursos', RatRecursoDTO::fromArray(...)),
            envolvidos:  self::lista($dados, 'envolvidos', RatEnvolvidoDTO::fromArray(...)),
            vistoria:    ! empty($dados['vistoria']) && is_array($dados['vistoria'])
                             ? RatVistoriaDTO::fromArray($dados['vistoria'])
                             : null,
            historico:   isset($dados['historico'])
                             ? RatHistoricoDTO::fromArray(['historico' => $dados['historico']])
                             : null,
            finalizar:   self::asBool($dados['finalize'] ?? null),
            temConteudo: array_filter(
                [...$secoesDadosGerais, 'recursos', 'envolvidos', 'vistoria', 'historico'],
                fn (string $bloco) => ! empty($dados[$bloco]),
            ) !== [],
        );
    }

    /**
     * Itens de recursos/envolvidos. O seq, quando ausente, segue a ordem do
     * payload. O id do item e mantido: na API de update ele indica qual item
     * atualizar; quando o Service substitui a lista, os antigos sao removidos
     * antes e o item e recriado.
     *
     * @param  callable(array): object  $fabrica
     */
    private static function lista(array $dados, string $chave, callable $fabrica): ?array
    {
        if (! isset($dados[$chave]) || ! is_array($dados[$chave])) {
            return null;
        }

        $itens = [];

        foreach (array_values($dados[$chave]) as $indice => $item) {
            if (! is_array($item)) {
                continue;
            }

            $item['seq'] ??= $indice + 1;
            $itens[] = $fabrica($item);
        }

        return $itens;
    }
}
