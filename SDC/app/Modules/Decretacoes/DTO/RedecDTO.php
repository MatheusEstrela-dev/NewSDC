<?php

declare(strict_types=1);

namespace App\Modules\Decretacoes\DTO;

use App\Modules\Decretacoes\Models\Redec;

/**
 * RedecDTO
 *
 * Uma Regiao de Defesa Civil como o resto do sistema a consome.
 *
 * FLUXO: dec_redecs (Model Redec) -> RedecDTO -> RedecService -> Resource/props Inertia -> Vue
 *
 * O DTO existe para que Resources, exportacoes e listas suspensas nao dependam
 * de nomes de coluna nem carreguem um Model Eloquent inteiro, e para que os
 * rotulos ("3ª REDEC - Santa Luzia") sejam montados num unico lugar. Substitui
 * os metodos do antigo enum App\Modules\Decretacoes\Enums\Redec, mantendo a
 * mesma assinatura (sigla/sede/regiao/rpm/label) para nao mexer em quem chama.
 */
final class RedecDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $sigla,
        public readonly string $sede,
        public readonly ?string $rpm,
        public readonly string $nome,
        public readonly bool $ativo = true,
    ) {
    }

    /** A partir do Model. */
    public static function fromModel(Redec $redec): self
    {
        return self::fromArray($redec->getAttributes());
    }

    /**
     * A partir de uma linha crua de `dec_redecs` (array ou stdClass do Query Builder).
     *
     * Os rotulos derivados tem fallback calculado a partir do id para que uma
     * linha inserida a mao, sem sigla/rpm/nome, ainda apareca legivel na tela em
     * vez de virar opcao em branco.
     *
     * @param array<string, mixed>|object $linha
     */
    public static function fromArray(array|object $linha): self
    {
        $dados = (array) $linha;
        $id    = (int) ($dados['id'] ?? 0);
        $sede  = (string) ($dados['sede'] ?? '');

        return new self(
            id:    $id,
            sigla: self::texto($dados['sigla'] ?? null) ?? $id . 'ª REDEC',
            sede:  $sede,
            rpm:   self::texto($dados['rpm'] ?? null),
            nome:  self::texto($dados['nome'] ?? null) ?? ('Região de Defesa Civil de ' . $sede),
            ativo: (bool) ($dados['ativo'] ?? true),
        );
    }

    /** Regiao atendida, identificada pela cidade sede. */
    public function regiao(): string
    {
        return $this->sede;
    }

    /** Rotulo exibido nas listas suspensas (ex: "3ª REDEC - Santa Luzia"). */
    public function label(): string
    {
        return $this->sede !== ''
            ? $this->sigla . ' - ' . $this->sede
            : $this->sigla;
    }

    /**
     * Contrato id/label consumido pelos FormSelect do front.
     *
     * As chaves sao exatamente as que o front ja le (ProcessoForm.vue,
     * ProcessoFilters.vue, ExportCsvModal.vue) - nao renomear sem ajustar lá.
     *
     * @return array{id: int, label: string, sigla: string, sede: string, rpm: string}
     */
    public function toSelectOption(): array
    {
        return [
            'id'    => $this->id,
            'label' => $this->label(),
            'sigla' => $this->sigla,
            'sede'  => $this->sede,
            'rpm'   => (string) $this->rpm,
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id'     => $this->id,
            'sigla'  => $this->sigla,
            'sede'   => $this->sede,
            'rpm'    => $this->rpm,
            'nome'   => $this->nome,
            'ativo'  => $this->ativo,
            'label'  => $this->label(),
        ];
    }

    /** Texto util ou null - coluna vazia nao vira rotulo em branco. */
    private static function texto(mixed $valor): ?string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto !== '' ? $texto : null;
    }
}
