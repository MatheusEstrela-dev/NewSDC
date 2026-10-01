<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Resgate\Enums\AcaoProposta;
use App\Modules\Resgate\Services\CatalogoResgate;
use App\Modules\Resgate\Support\Rastro;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Catalogo de DEMONSTRACAO do resgate (plano 2026-09-25, Fase 2).
 *
 * Passa pelo fluxo oficial - uma pessoa propoe, OUTRA aprova - para que o
 * proprio seeder exercite os quatro olhos. Todo item nasce com
 * demonstracao = true: aparece marcado na vitrine e nunca pode ser resgatado.
 * Custos e itens sao ilustrativos ate o normativo da CEDEC.
 *
 * Reexecutar nao duplica: codigo com item vigente e pulado.
 * Nunca integra o DatabaseSeeder. Exclusivo da homologacao local.
 */
final class RankingResgateCatalogoDemoSeeder extends Seeder
{
    private const NORMATIVO = 'Demonstração - aguardando normativo da CEDEC';

    public function run(): void
    {
        if (! in_array(parse_url((string) config('app.url'), PHP_URL_HOST), ['localhost', '127.0.0.1'], true)
            || DB::connection('ranking')->getDatabaseName() !== 'sdc_ranking') {
            throw new RuntimeException('Demonstracao permitida apenas no ranking da homologacao local.');
        }

        $proponente = User::query()->findOrFail(1);
        $aprovador = User::query()->where('id', '<>', $proponente->id)->orderBy('id')->firstOrFail();
        $catalogo = app(CatalogoResgate::class);
        $rastro = Rastro::doSistema('RankingResgateCatalogoDemoSeeder');

        foreach ($this->itens() as $codigo => $item) {
            $vigente = DB::connection('ranking')->selectOne('SELECT 1 FROM resgate.catalogo_itens WHERE codigo = ? AND vigente_ate IS NULL', [$codigo]);
            if ($vigente !== null) {
                continue;
            }
            $unidades = $item['_unidades'] ?? [];
            unset($item['_unidades']);

            $proposta = $catalogo->propor(AcaoProposta::Criar, $codigo, $item + ['demonstracao' => true], 'Item de demonstração para homologação do catálogo.', (int) $proponente->id, $rastro);
            $catalogo->decidir($proposta, true, 'Aprovado para demonstração em homologação.', (int) $aprovador->id, $rastro);

            foreach ($unidades as $unidade) {
                $catalogo->cadastrarUnidade($codigo, $unidade, (int) $proponente->id, $rastro);
            }
        }

        $this->command?->info("Catalogo demo: proposto por #{$proponente->id}, aprovado por #{$aprovador->id}.");
    }

    /** @return array<string, array<string, mixed>> */
    private function itens(): array
    {
        $base = ['beneficiario' => 'municipio', 'base_normativa' => self::NORMATIVO, 'prazo_reserva_dias' => 30];

        return [
            'SERV-PLANCON' => $base + [
                'tipo' => 'servico', 'titulo' => 'Apoio técnico na revisão do PlanCon',
                'descricao' => 'Equipe da CEDEC apoia a COMPDEC na revisão do Plano de Contingência municipal.',
                'faixa_minima' => 'bronze', 'custo_pontos' => 150, 'quantidade' => 40, 'limite_por_ente_temporada' => 1,
                'instrumento' => 'ordem_servico', 'unidade_responsavel' => 'CEDEC - Coordenadoria de Planejamento',
                'documentos_exigidos' => ['Ofício da COMPDEC', 'PlanCon vigente'],
            ],
            'SERV-CAPACITA' => $base + [
                'tipo' => 'servico', 'titulo' => 'Capacitação presencial em gestão de risco',
                'descricao' => 'Turma de capacitação no município para a equipe da COMPDEC e voluntários.',
                'faixa_minima' => 'prata', 'custo_pontos' => 300, 'quantidade' => 20, 'limite_por_ente_temporada' => 1,
                'instrumento' => 'ordem_servico', 'unidade_responsavel' => 'CEDEC - Escola de Defesa Civil',
                'documentos_exigidos' => ['Ofício da COMPDEC', 'Local e data propostos'],
            ],
            'CONS-EPI' => $base + [
                'tipo' => 'bem_consumo', 'titulo' => 'Lote de EPIs para a COMPDEC',
                'descricao' => 'Capacetes, luvas, coletes refletivos e botas para a equipe de campo.',
                'faixa_minima' => 'prata', 'custo_pontos' => 500, 'quantidade' => 50, 'limite_por_ente_temporada' => 1,
                'instrumento' => 'termo_entrega', 'unidade_responsavel' => 'CEDEC - Logística',
                'documentos_exigidos' => ['Ofício da COMPDEC', 'Responsável pelo recebimento'],
            ],
            'ADES-KITS' => $base + [
                'tipo' => 'adesao', 'titulo' => 'Adesão ao programa estadual de kits humanitários',
                'descricao' => 'Cota do município no programa de kits de ajuda humanitária pré-posicionados.',
                'faixa_minima' => 'ouro', 'custo_pontos' => 800, 'quantidade' => 30, 'limite_por_ente_temporada' => 1,
                'instrumento' => 'termo_adesao', 'unidade_responsavel' => 'CEDEC - Ajuda Humanitária',
                'documentos_exigidos' => ['Termo de adesão assinado', 'Local de armazenamento'],
            ],
            'PERM-DRONE' => $base + [
                'tipo' => 'bem_permanente', 'titulo' => 'Drone de mapeamento',
                'descricao' => 'Drone com câmera para mapeamento de áreas de risco e avaliação de danos.',
                'faixa_minima' => 'ouro', 'custo_pontos' => 2000, 'quantidade' => null, 'limite_por_ente_temporada' => 1,
                'instrumento' => 'termo_cessao_uso', 'unidade_responsavel' => 'CEDEC - Patrimônio',
                'documentos_exigidos' => ['Ofício da COMPDEC', 'Operador habilitado (ANAC)', 'Termo de cessão de uso'],
                '_unidades' => [
                    ['patrimonio' => 'DEMO-DRONE-001', 'descricao' => 'Drone de mapeamento - unidade 1', 'numero_serie' => 'DEMO-SN-0001'],
                    ['patrimonio' => 'DEMO-DRONE-002', 'descricao' => 'Drone de mapeamento - unidade 2', 'numero_serie' => 'DEMO-SN-0002'],
                    ['patrimonio' => 'DEMO-DRONE-003', 'descricao' => 'Drone de mapeamento - unidade 3', 'numero_serie' => 'DEMO-SN-0003'],
                ],
            ],
            'PERM-VIATURA' => $base + [
                'tipo' => 'bem_permanente', 'titulo' => 'Viatura 4x4 de resposta',
                'descricao' => 'Caminhonete 4x4 caracterizada para resposta a desastres.',
                'faixa_minima' => 'diamante', 'custo_pontos' => 7000, 'quantidade' => null, 'limite_por_ente_temporada' => 1,
                'instrumento' => 'termo_doacao', 'unidade_responsavel' => 'CEDEC - Patrimônio',
                'documentos_exigidos' => ['Ofício do prefeito', 'Parecer jurídico', 'Termo de doação', 'Motorista habilitado'],
                '_unidades' => [
                    ['patrimonio' => 'DEMO-VTR-001', 'descricao' => 'Viatura 4x4 - unidade 1', 'placa' => 'DEM0A01', 'chassi' => '9BWZZZDEMO0000001'],
                    ['patrimonio' => 'DEMO-VTR-002', 'descricao' => 'Viatura 4x4 - unidade 2', 'placa' => 'DEM0A02', 'chassi' => '9BWZZZDEMO0000002'],
                ],
            ],
        ];
    }
}
