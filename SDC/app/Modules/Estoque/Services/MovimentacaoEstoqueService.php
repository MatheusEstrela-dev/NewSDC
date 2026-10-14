<?php

declare(strict_types=1);

namespace App\Modules\Estoque\Services;

use App\Modules\Estoque\DTOs\EntradaEstoqueDTO;
use App\Modules\Estoque\DTOs\SaidaEstoqueDTO;
use App\Modules\Estoque\Models\Lote;
use App\Modules\Estoque\Models\MovimentacaoEstoque;
use App\Modules\Estoque\Models\Produto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Unica porta de escrita do saldo do estoque (#32/#33).
 *
 * Entrada soma no lote; saida baixa em FEFO (vence primeiro, sai primeiro),
 * pulando lote vencido e lote em quarentena. Tudo em transacao, com o lote
 * travado (FOR UPDATE): duas saidas simultaneas nao vendem o mesmo saldo.
 */
class MovimentacaoEstoqueService
{
    public function entrada(EntradaEstoqueDTO $dto, ?int $userId): MovimentacaoEstoque
    {
        return DB::transaction(function () use ($dto, $userId): MovimentacaoEstoque {
            $produto = Produto::query()->findOrFail($dto->produtoId);

            if ($produto->controla_validade && $dto->validade === null) {
                throw new \DomainException("O produto {$produto->nome} controla validade: informe a data de validade do lote.");
            }

            $lote = Lote::query()
                ->where('produto_id', $produto->id)
                ->where('almoxarifado_id', $dto->almoxarifadoId)
                ->where('codigo', $dto->lote)
                ->lockForUpdate()
                ->first();

            if ($lote !== null && $dto->validade !== null && $lote->validade !== null
                && ! $lote->validade->isSameDay(Carbon::parse($dto->validade))) {
                throw new \DomainException("O lote {$dto->lote} ja existe com validade {$lote->validade->format('d/m/Y')}. Confira o codigo do lote.");
            }

            $lote ??= new Lote([
                'produto_id'      => $produto->id,
                'almoxarifado_id' => $dto->almoxarifadoId,
                'codigo'          => $dto->lote,
                'saldo'           => 0,
            ]);

            $lote->validade ??= $dto->validade;
            $lote->fornecedor ??= $dto->fornecedor;
            $lote->endereco = $dto->endereco ?? $lote->endereco;
            $lote->saldo += $dto->quantidade;
            $lote->save();

            return MovimentacaoEstoque::create([
                'tipo'            => MovimentacaoEstoque::ENTRADA,
                'produto_id'      => $produto->id,
                'lote_id'         => $lote->id,
                'almoxarifado_id' => $dto->almoxarifadoId,
                'quantidade'      => $dto->quantidade,
                'saldo_apos'      => $lote->saldo,
                'destino_descricao' => $dto->fornecedor,
                'documento'       => $dto->documento,
                'observacao'      => $dto->observacao,
                'user_id'         => $userId,
            ]);
        });
    }

    /**
     * Saida de um romaneio. Ou sai tudo, ou nada: falta de saldo em qualquer
     * item recusa o romaneio inteiro, com a lista do que faltou.
     *
     * @return array{romaneio: string, movimentacoes: Collection<int, MovimentacaoEstoque>}
     */
    public function saida(SaidaEstoqueDTO $dto, ?int $userId): array
    {
        return DB::transaction(function () use ($dto, $userId): array {
            $lotesPorProduto = [];
            $faltas = [];

            foreach ($dto->itens as $item) {
                $lotes = Lote::query()
                    ->disponivel()
                    ->where('produto_id', $item['produto_id'])
                    ->where('almoxarifado_id', $dto->almoxarifadoId)
                    ->orderByRaw('validade IS NULL, validade')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $disponivel = (int) $lotes->sum('saldo');
                if ($disponivel < $item['quantidade']) {
                    $nome = Produto::withTrashed()->whereKey($item['produto_id'])->value('nome');
                    $faltas[] = "{$nome}: pedido {$item['quantidade']}, disponivel {$disponivel}";
                }
                $lotesPorProduto[$item['produto_id']] = $lotes;
            }

            if ($faltas !== []) {
                throw new \DomainException('Saldo insuficiente no almoxarifado. ' . implode('; ', $faltas) . '.');
            }

            $romaneio = $this->proximoRomaneio();
            $movimentacoes = collect();

            foreach ($dto->itens as $item) {
                $restante = $item['quantidade'];

                foreach ($lotesPorProduto[$item['produto_id']] as $lote) {
                    if ($restante === 0) {
                        break;
                    }

                    $baixa = min($restante, $lote->saldo);
                    $lote->saldo -= $baixa;
                    $lote->save();
                    $restante -= $baixa;

                    $movimentacoes->push(MovimentacaoEstoque::create([
                        'tipo'                 => MovimentacaoEstoque::SAIDA,
                        'produto_id'           => $item['produto_id'],
                        'lote_id'              => $lote->id,
                        'almoxarifado_id'      => $dto->almoxarifadoId,
                        'quantidade'           => $baixa,
                        'saldo_apos'           => $lote->saldo,
                        'romaneio'             => $romaneio,
                        'destino_tipo'         => $dto->destinoTipo,
                        'destino_municipio_id' => $dto->destinoMunicipioId,
                        'destino_descricao'    => $dto->destinoDescricao,
                        'documento'            => $dto->documento,
                        'observacao'           => $dto->observacao,
                        'user_id'              => $userId,
                    ]));
                }
            }

            return ['romaneio' => $romaneio, 'movimentacoes' => $movimentacoes];
        });
    }

    /**
     * ROM-2026-00001. O advisory lock da transacao serializa a numeracao:
     * dois romaneios simultaneos nao recebem o mesmo numero.
     */
    private function proximoRomaneio(): string
    {
        DB::select('SELECT pg_advisory_xact_lock(?)', [crc32('estoque_romaneio')]);

        $ano = now()->year;
        $ultimo = MovimentacaoEstoque::query()
            ->where('romaneio', 'like', "ROM-{$ano}-%")
            ->max('romaneio');

        $sequencia = $ultimo ? ((int) substr($ultimo, -5)) + 1 : 1;

        return sprintf('ROM-%d-%05d', $ano, $sequencia);
    }
}
