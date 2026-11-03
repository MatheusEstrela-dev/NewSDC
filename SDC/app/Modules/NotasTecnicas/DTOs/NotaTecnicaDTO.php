<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\DTOs;

/** Dados descritivos de uma nota, vindos do formulario de cadastro ou edicao. */
final readonly class NotaTecnicaDTO
{
    /**
     * @param  list<array{nome: string, rotulo: string}>  $rotasRelacionadas
     */
    public function __construct(
        public string $titulo,
        public int $categoriaId,
        public ?string $resumo,
        public ?string $vigenciaInicio,
        public array $rotasRelacionadas,
    ) {}

    /** @param  array<string, mixed>  $dados  validated() do Request */
    public static function fromRequest(array $dados): self
    {
        $telas = config('notas_tecnicas.telas_relacionadas', []);

        return new self(
            titulo: trim((string) $dados['titulo']),
            categoriaId: (int) $dados['categoria_id'],
            resumo: self::textoOuNulo($dados['resumo'] ?? null),
            vigenciaInicio: self::textoOuNulo($dados['vigencia_inicio'] ?? null),
            // O rotulo vem da config, nao do cliente: a lista e a fonte da verdade.
            rotasRelacionadas: array_values(array_map(
                static fn (string $nome): array => ['nome' => $nome, 'rotulo' => (string) $telas[$nome]],
                array_unique($dados['rotas_relacionadas'] ?? []),
            )),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'titulo' => $this->titulo,
            'categoria_id' => $this->categoriaId,
            'resumo' => $this->resumo,
            'vigencia_inicio' => $this->vigenciaInicio,
            'rotas_relacionadas' => $this->rotasRelacionadas,
        ];
    }

    private static function textoOuNulo(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto === '' ? null : $texto;
    }
}
