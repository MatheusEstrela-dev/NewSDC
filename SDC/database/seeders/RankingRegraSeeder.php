<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Ranking\Models\Regra;
use Illuminate\Database\Seeder;

final class RankingRegraSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::catalogo() as $regra) {
            // Reexecucao preserva versoes e ativacoes aprovadas anteriormente.
            Regra::query()->firstOrCreate(
                ['rule_key' => $regra['rule_key'], 'versao' => $regra['versao']],
                $regra,
            );
        }
    }

    /**
     * Contratos propostos, sem prova suficiente para ativacao automatica.
     * Planos recebidos via Compdec usam as regras PlanCon e a mesma chave
     * canonica plano/ciclo. Nao existe segunda regra de premio para o plano.
     * Familias compartilhadas exigem a mesma identidade canonica na ingestao.
     *
     * @return list<array<string, mixed>>
     */
    public static function catalogo(): array
    {
        $marcos = [
            ['Rat', 'rat.registro_completo', 'rat_registro_completo', 5],
            ['Rat', 'rat.relatorio_finalizado', 'rat_relatorio_finalizado', 20],
            ['Rat', 'rat.vistoria_validada', 'rat_vistoria_validada', 15],
            ['Pae', 'pae.protocolo_enviado', 'pae_protocolo_enviado', 10],
            ['Pae', 'pae.formulario_validado', 'pae_formulario_validado', 20],
            ['Pae', 'pae.revisao_aceita', 'pae_revisao_aceita', 20],
            ['Pae', 'pae.parecer_concluido', 'pae_parecer_concluido', 15],
            ['PlanCon', 'plancon.plano_enviado', 'plano_municipal_envio', 10],
            ['PlanCon', 'plancon.revisao_aceita', 'plano_municipal_revisao', 20],
            ['Compdec', 'compdec.cadastro_revalidado', 'cadastro_institucional_validacao', 20],
            ['Compdec', 'compdec.equipe_revalidada', 'compdec_equipe_revalidada', 10],
            ['Decretacoes', 'decretacoes.processo_enviado', 'decretacoes_processo_enviado', 10],
            ['Decretacoes', 'decretacoes.instrucao_validada', 'decretacoes_instrucao_validada', 20],
            ['AjudaHumanitaria', 'ajuda_humanitaria.pedido_completo', 'ajuda_humanitaria_pedido_completo', 5],
            ['AjudaHumanitaria', 'ajuda_humanitaria.entrega_comprovada', 'entrega_humanitaria', 20],
            ['AjudaHumanitaria', 'ajuda_humanitaria.contas_aceitas', 'ajuda_humanitaria_contas_aceitas', 30],
            ['Pmda', 'pmda.plano_enviado', 'pmda_plano_enviado', 10],
            ['Pmda', 'pmda.execucao_comprovada', 'pmda_execucao_comprovada', 20],
            ['Pmda', 'pmda.contas_aceitas', 'pmda_contas_aceitas', 30],
            ['Tdap', 'tdap.cronograma_ativado', 'tdap_cronograma_ativado', 10],
            ['Tdap', 'tdap.viagem_validada', 'tdap_viagem_validada', 10],
            ['Tdap', 'tdap.vistoria_validada', 'tdap_vistoria_validada', 15],
            ['Cisterna', 'cisterna.os_emitida', 'cisterna_os_emitida', 5],
            ['Cisterna', 'cisterna.vistoria_validada', 'cisterna_vistoria_validada', 15],
            ['Cisterna', 'cisterna.entrega_validada', 'cisterna_entrega_validada', 20],
            ['Estoque', 'estoque.movimento_confirmado', 'entrega_humanitaria', 5],
            ['Estoque', 'estoque.inventario_conciliado', 'estoque_inventario_conciliado', 20],
            ['Inventario', 'inventario.bem_validado', 'inventario_bem_validado', 5],
            ['Inventario', 'inventario.ciclo_conciliado', 'inventario_ciclo_conciliado', 20],
            ['Plantao', 'plantao.turno_assumido', 'plantao_turno_assumido', 5],
            ['Plantao', 'plantao.passagem_validada', 'plantao_passagem_validada', 15],
            ['Plantao', 'plantao.missao_concluida', 'plantao_missao_concluida', 20],
            ['Demandas', 'demandas.entrega_aceita', 'demandas_entrega_aceita', 10],
            ['Geoespacial', 'geoespacial.camada_validada', 'geoespacial_camada_validada', 15],
            ['Geoespacial', 'geoespacial.revisao_aceita', 'geoespacial_revisao_aceita', 10],
            ['Treinamento', 'treinamento.curso_concluido', 'treinamento_curso_concluido', 20],
            ['Treinamento', 'treinamento.turma_encerrada', 'treinamento_turma_encerrada', 15],
            ['Cedec', 'cedec.cadastro_institucional_validado', 'cadastro_institucional_validacao', 10],
            ['Suporte', 'suporte.solucao_aceita', 'suporte_solucao_aceita', 5],
        ];

        return array_map(
            static fn (array $marco): array => [
                'modulo' => $marco[0],
                'rule_key' => $marco[1],
                'familia' => $marco[2],
                'pontos_base' => $marco[3],
                'versao' => 1,
                'bonus_percentual' => 20,
                'aceita_bonus' => false,
                'habilitada' => false,
                'motivo_desabilitada' => 'source_evidence_missing',
                'vigente_de' => '2026-09-21 00:00:00+00:00',
                'vigente_ate' => null,
            ],
            $marcos,
        );
    }
}

