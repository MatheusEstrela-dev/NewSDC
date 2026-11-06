<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Aplicadores\Estoque;

use App\Modules\Estoque\DTOs\ProdutoDTO;
use App\Modules\Estoque\Models\Produto;
use App\Modules\Estoque\Services\CadastroEstoqueService;
use App\Modules\Estoque\Support\RegrasEstoque;
use App\Modules\Integracao\Aplicadores\AplicadorBase;
use App\Modules\Integracao\Exceptions\EntradaJaAplicadaException;
use App\Modules\Integracao\Support\MensagemDeEntrada;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Unique;

/** Espelha POST v1/inventario (Api\V1\Estoque\InventarioApiController@store). */
final class CriarProdutoAplicador extends AplicadorBase
{
    public function __construct(private readonly CadastroEstoqueService $cadastro)
    {
    }

    public function regras(Request $request): array
    {
        $regras = RegrasEstoque::produto();

        // Na reaplicacao o SKU repetido e "ja aplicado" (aplicar()), nao rejeicao.
        if ($request->attributes->get(MensagemDeEntrada::ATRIBUTO_REAPLICACAO) === true) {
            $regras['sku'] = array_values(array_filter(
                $regras['sku'],
                static fn (mixed $regra): bool => ! $regra instanceof Unique,
            ));
        }

        return $regras;
    }

    public function recursoChave(array $payload, array $parametrosRota): string
    {
        return 'estoque:produto:' . self::sku($payload);
    }

    public function aplicar(MensagemDeEntrada $mensagem): array
    {
        // O DTO grava o SKU em maiusculas; a busca segue a mesma forma.
        $sku = self::sku($mensagem->payload);
        $existente = Produto::query()->where('sku', $sku)->first(['id', 'sku']);

        if ($existente !== null) {
            throw new EntradaJaAplicadaException("Produto com SKU {$sku} ja cadastrado.", self::resultado($existente));
        }

        return self::resultado($this->cadastro->criarProduto(ProdutoDTO::fromValidated($mensagem->payload)));
    }

    /** @param array<string, mixed> $payload */
    private static function sku(array $payload): string
    {
        return mb_strtoupper((string) ($payload['sku'] ?? ''));
    }

    /** @return array{id: int, sku: string} */
    private static function resultado(Produto $produto): array
    {
        return ['id' => (int) $produto->id, 'sku' => (string) $produto->sku];
    }
}
