<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\AjudaHumanitaria\Enums\StatusPedidoAh;
use App\Modules\AjudaHumanitaria\Models\PedidoAh;
use App\Modules\AjudaHumanitaria\Models\PrestacaoConta;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaEntrega;
use App\Modules\AjudaHumanitaria\Models\PrestacaoContaItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Um pedido de ajuda humanitaria com prestacao de contas e entregas lancadas.
 *
 * POR QUE EXISTE. A aba de prestacao de contas mostra as entregas numa tabela
 * de seis colunas mais a acao de remover -- convertida para bloco no mobile
 * (regra 9). So que `prestacoes_conta` e `prestacao_conta_entregas` estao com
 * ZERO linhas no banco de desenvolvimento, entao a tela nao renderiza nada e a
 * conversao nao pode ser conferida em 375px.
 *
 * A prestacao fica em `em_lancamento` de proposito: e o unico status em que o
 * botao "Remover" de cada entrega aparece, e e justamente a coluna de acao que
 * flutuava sobre conteudo cortado no telefone.
 *
 * IDEMPOTENTE: identifica o pedido por numero+ano e nao duplica. Rodar de novo
 * nao cria um segundo pedido nem repete as entregas.
 */
class PrestacaoContaAhDemoSeeder extends Seeder
{
    private const NUMERO = 9901;
    private const ANO = 2026;

    public function run(): void
    {
        $municipioId = DB::table('municipios')->value('id');

        if ($municipioId === null) {
            $this->command?->warn('Sem municipios cadastrados: nada a semear.');

            return;
        }

        $pedido = PedidoAh::firstOrCreate(
            ['numero' => self::NUMERO, 'ano' => self::ANO],
            [
                'municipio_id' => $municipioId,
                'pop_atendida' => 320,
                'decreto_se_ecp_vig' => false,
                'esforcos_realizados' => 'Registro de demonstracao para conferir a tela de prestacao de contas.',
                'status' => StatusPedidoAh::Atendido,
                'data_entrada_sistema' => Carbon::now()->subDays(30),
            ]
        );

        $prestacao = PrestacaoConta::firstOrCreate(
            ['pedido_ah_id' => $pedido->id],
            [
                // `em_lancamento` e o que faz aparecer a acao de remover entrega.
                'status' => 'em_lancamento',
                'data_limite' => Carbon::now()->addDays(15)->toDateString(),
            ]
        );

        $item = PrestacaoContaItem::firstOrCreate(
            ['prestacao_conta_id' => $prestacao->id, 'nome_material' => 'Cesta basica'],
            [
                'codigo_material' => 'CB-001',
                'qtd' => 40,
                'total_familia_atendida' => 40,
            ]
        );

        // Nomes longos de proposito: o card no mobile precisa aguentar o pior
        // caso do titulo, nao a media.
        $entregas = [
            ['Maria Aparecida do Nascimento Silva', 'MG-12.345.678', 'Comunidade Sao Jose do Rio Claro', 3],
            ['Joao Batista de Oliveira', 'MG-23.456.789', 'Zona Rural - Corrego Fundo', 2],
            ['Antonia Ferreira dos Santos', 'MG-34.567.890', 'Bairro Nova Esperanca', 4],
            ['Sebastiao Pereira Lima', null, 'Assentamento Boa Vista', 1],
            ['Rosa Maria Conceicao', 'MG-45.678.901', null, 5],
        ];

        foreach ($entregas as $i => [$nome, $rg, $comunidade, $qtd]) {
            PrestacaoContaEntrega::firstOrCreate(
                ['prestacao_conta_item_id' => $item->id, 'nome_beneficiario' => $nome],
                [
                    'rg' => $rg,
                    'comunidade' => $comunidade,
                    'qtd' => $qtd,
                    'data_entrega' => Carbon::now()->subDays(10 - $i)->toDateString(),
                ]
            );
        }

        $this->command?->info(sprintf(
            'Pedido AH %d/%d com %d entregas na prestacao de contas.',
            self::NUMERO,
            self::ANO,
            count($entregas)
        ));
    }
}
