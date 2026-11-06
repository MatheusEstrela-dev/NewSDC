<?php

declare(strict_types=1);

namespace App\Modules\Integracao\Aplicadores\Estoque;

use App\Modules\Estoque\DTOs\EntradaEstoqueDTO;
use App\Modules\Estoque\DTOs\SaidaEstoqueDTO;
use App\Modules\Estoque\Services\EstoqueConsultaService;
use App\Modules\Estoque\Services\MovimentacaoEstoqueService;
use App\Modules\Estoque\Support\RegrasEstoque;
use App\Modules\Integracao\Aplicadores\AplicadorBase;
use App\Modules\Integracao\Support\MensagemDeEntrada;
use Illuminate\Http\Request;

/**
 * Espelha POST v1/estoque/movimentacoes (Api\V1\Estoque\MovimentacaoApiController@store).
 *
 * Nao ha dedupe por documento: a operacao nao e idempotente por natureza e a
 * caixa ja garante exactly-once por mensagem (integracao_aplicacoes) e exige
 * Idempotency-Key no modo assincrono. DomainException (saldo insuficiente)
 * propaga para o job marcar a mensagem como rejeitada.
 */
final class RegistrarMovimentacaoAplicador extends AplicadorBase
{
    private const TIPOS = ['entrada', 'saida'];

    public function __construct(
        private readonly MovimentacaoEstoqueService $movimentacao,
        private readonly EstoqueConsultaService $consulta,
    ) {
    }

    public function regras(Request $request): array
    {
        $tipo = $request->input('tipo');

        $regrasDoTipo = match ($tipo) {
            'entrada' => RegrasEstoque::entrada(),
            'saida' => RegrasEstoque::saida(),
            default => [],
        };

        return ['tipo' => ['required', 'in:' . implode(',', self::TIPOS)]] + $regrasDoTipo;
    }

    public function recursoChave(array $payload, array $parametrosRota): string
    {
        // Saldo, FEFO e romaneio sao serializados por almoxarifado.
        return 'estoque:almox:' . (int) ($payload['almoxarifado_id'] ?? 0);
    }

    public function aplicar(MensagemDeEntrada $mensagem): array
    {
        $userId = $this->usuario()->id;

        return $mensagem->payload['tipo'] === 'entrada'
            ? $this->entrada($mensagem->payload, $userId)
            : $this->saida($mensagem->payload, $userId);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function entrada(array $payload, ?int $userId): array
    {
        $mov = $this->movimentacao->entrada(EntradaEstoqueDTO::fromValidated($payload), $userId);

        return $this->consulta->linhaDeMovimentacao($mov->load(['produto', 'lote', 'almoxarifado']));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function saida(array $payload, ?int $userId): array
    {
        $saida = $this->movimentacao->saida(SaidaEstoqueDTO::fromValidated($payload), $userId);

        return [
            'tipo' => 'saida',
            'romaneio' => $saida['romaneio'],
            'itens' => $saida['movimentacoes']
                ->map(fn ($m): array => $this->consulta->linhaDeMovimentacao(
                    $m->load(['produto', 'lote', 'almoxarifado', 'destinoMunicipio'])
                ))
                ->values()
                ->all(),
        ];
    }
}
