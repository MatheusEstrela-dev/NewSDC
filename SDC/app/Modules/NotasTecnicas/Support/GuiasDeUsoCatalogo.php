<?php

declare(strict_types=1);

namespace App\Modules\NotasTecnicas\Support;

use Illuminate\Support\Str;

/**
 * Catalogo dos guias de uso do SDC MG (pasta Doc/ na raiz do repositorio) e da
 * pasta de Notas Tecnicas onde cada um fica: a pasta do proprio modulo, lido do
 * nome do PDF.
 *
 * A pasta e achada pelo slug do nome (o mesmo que a tela gera), entao as que ja
 * existem (RAT, PAE, TDAP / PMDA) sao reaproveitadas e as que faltarem nascem no
 * fim da arvore. O resumo e o paragrafo de abertura do proprio guia.
 */
final class GuiasDeUsoCatalogo
{
    public const CHANGELOG = 'Primeira versão do guia, com as telas do sistema capturadas em novembro de 2026.';

    private const DIR_MODULOS = 'Guias por módulo/PDF/';

    /** Primeiros passos abre a arvore, antes de RAT (ordem 10). */
    private const ORDEM_PRIMEIROS_PASSOS = 5;

    /**
     * @return list<array{slug: string, nome: string, ordem: ?int, guias: list<array{arquivo: string, titulo: string, resumo: string, rota: ?string}>}>
     */
    public static function pastas(): array
    {
        return [
            self::pasta('Primeiros passos', [
                [
                    'arquivo' => 'Guia de Uso - SDC MG v2.pdf',
                    'titulo' => 'Guia de uso – SDC MG completo',
                    'resumo' => 'Todos os guias de uso do SDC MG reunidos em um único documento, para consulta.',
                    'rota' => null,
                ],
                [
                    'arquivo' => self::DIR_MODULOS . '01 - Primeiros passos.pdf',
                    'titulo' => 'Guia de uso – Primeiros passos',
                    'resumo' => 'Apresentação do SDC MG, perfis de acesso, como entrar no sistema e os padrões de tela usados em todos os módulos. Leia antes dos guias de cada módulo.',
                    'rota' => null,
                ],
            ], self::ORDEM_PRIMEIROS_PASSOS),
            self::modulo('02 - RAT.pdf', 'RAT', 'rat.index',
                'O RAT registra os atendimentos técnicos realizados pela Defesa Civil: data e horário do fato, natureza do evento segundo a COBRADE, recursos empregados, pessoas envolvidas e anexos.'),
            self::modulo('03 - Demandas.pdf', 'Demandas', 'demandas.index',
                'O módulo Demandas é o canal para abrir chamados para a equipe da CEDEC e acompanhar o atendimento até a conclusão.'),
            self::modulo('04 - PAE.pdf', 'PAE', 'pae.index',
                'O módulo PAE controla os protocolos de análise de Plano de Ação de Emergência. Cada protocolo acompanha a situação das etapas DCO, Evacuação e Simulado, o analista responsável e os prazos de entrada e limite.'),
            self::modulo('05 - Plantão Diário.pdf', 'Plantão Diário', 'plantao.index',
                'Gestão dos turnos de plantão e dos lançamentos operacionais: abertura e encerramento do turno, passagem de serviço, viaturas, chaves, escala e relatório do turno.'),
            self::modulo('06 - Decretações.pdf', 'Decretações', 'decretacoes.index',
                'Gerencia os processos de decretação de Situação de Emergência (SE) e Estado de Calamidade Pública (ECP) e o seu reconhecimento.'),
            self::modulo('07 - Ajuda Humanitária.pdf', 'Ajuda Humanitária', 'ajuda-humanitaria.pedidos.index',
                'Controla os pedidos de material de ajuda humanitária feitos pelos municípios à CEDEC, desde o cadastro até a prestação de contas, além do estoque e da movimentação dos materiais.'),
            self::modulo('08 - Órgãos.pdf', 'Órgãos', 'compdec.index',
                'Cadastro das COMPDECs, REDECs e da CEDEC, com os atos legais de criação e os quantitativos de cada órgão.'),
            self::modulo('09 - Prefeituras.pdf', 'Prefeituras', 'cedec.prefeituras.index',
                'Contatos institucionais das prefeituras de Minas Gerais mantidos pela CEDEC: prefeito, REDEC, e-mail institucional e telefone.'),
            self::modulo('10 - Notas Técnicas.pdf', 'Notas Técnicas', null,
                'Repositório oficial das notas técnicas e dos procedimentos operacionais (POPs) da CEDEC, em PDF. Cada nota tem código próprio, passa por versões, só entra em vigor depois de publicada e registra quem já leu cada versão.'),
            self::pasta('TDAP / PMDA', [
                self::guia('11 - TDAP.pdf', 'TDAP', 'tdap.dashboard',
                    'O TDAP gerencia o abastecimento emergencial de água por caminhões-pipa: empresas contratadas, frota e vistorias, atas de registro de preços, lotes, cronogramas de fornecimento e a validação de cada viagem realizada.'),
                self::guia('12 - PMDA.pdf', 'PMDA', 'pmda.index',
                    'Gestão dos planos municipais para o fornecimento emergencial de água potável. O PMDA aprovado define, entre outras informações, os pontos de captação usados nos cronogramas do TDAP.'),
            ]),
            self::modulo('13 - Estoque.pdf', 'Estoque', 'estoque.index',
                'Controle de donativos e insumos por almoxarifado, lote e validade.'),
            self::modulo('14 - Cisternas.pdf', 'Cisternas', 'cisternas.index',
                'Cadastro dos beneficiários do programa de cisternas e fiscalização da instalação em três etapas: aprovação, obra e fiscalização.'),
            self::modulo('15 - Treinamentos.pdf', 'Treinamentos', 'treinamentos.index',
                'Gestão dos treinamentos e cursos da Diretoria de Educação: cadastro, inscrições, presença e certificação.'),
            self::modulo('16 - Ranking.pdf', 'Ranking', null,
                'Mostra o placar do município: cada entrega gera pontos. A conformidade municipal é avaliada separadamente pelo IPCM.'),
            self::modulo('17 - Plano de Contingência.pdf', 'Plano de Contingência', 'plancon.index',
                'Gestão dos Planos de Contingência dos municípios mineiros: quem tem plano, quem não tem e a situação dos planos enviados.'),
            self::modulo('18 - Meteorologia.pdf', 'Meteorologia', null,
                'Mapa com as estações de chuva do INMET e do CEMADEN em Minas Gerais e os níveis de precipitação das últimas 24 horas.'),
            self::modulo('19 - Camadas de Risco.pdf', 'Camadas de Risco', null,
                'Áreas de alerta enviadas em KML/KMZ e cruzadas com os municípios e com as estações de chuva monitoradas pelo sistema.'),
            self::modulo('20 - Sismos.pdf', 'Sismos', null,
                'Mapa dos eventos sísmicos registrados no quadrante de Minas Gerais, com estatísticas e legenda por magnitude.'),
            self::modulo('21 - Administração.pdf', 'Administração', null,
                'Funções restritas aos administradores do sistema: usuários, cargos, permissões e integridade do controle de acesso.'),
        ];
    }

    /**
     * Pasta de um modulo so, com o nome do modulo e o guia dele.
     *
     * @return array{slug: string, nome: string, ordem: ?int, guias: list<array{arquivo: string, titulo: string, resumo: string, rota: ?string}>}
     */
    private static function modulo(string $arquivo, string $modulo, ?string $rota, string $resumo): array
    {
        return self::pasta($modulo, [self::guia($arquivo, $modulo, $rota, $resumo)]);
    }

    /**
     * @param  list<array{arquivo: string, titulo: string, resumo: string, rota: ?string}>  $guias
     * @return array{slug: string, nome: string, ordem: ?int, guias: list<array{arquivo: string, titulo: string, resumo: string, rota: ?string}>}
     */
    private static function pasta(string $nome, array $guias, ?int $ordem = null): array
    {
        return ['slug' => Str::slug($nome), 'nome' => $nome, 'ordem' => $ordem, 'guias' => $guias];
    }

    /**
     * @return array{arquivo: string, titulo: string, resumo: string, rota: ?string}
     */
    private static function guia(string $arquivo, string $modulo, ?string $rota, string $resumo): array
    {
        return [
            'arquivo' => self::DIR_MODULOS . $arquivo,
            'titulo' => "Guia de uso – Módulo {$modulo}",
            'resumo' => $resumo,
            'rota' => $rota,
        ];
    }
}
