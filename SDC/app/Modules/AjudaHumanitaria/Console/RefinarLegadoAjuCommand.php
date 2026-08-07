<?php

declare(strict_types=1);

namespace App\Modules\AjudaHumanitaria\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Normaliza a area de pouso para as tabelas destino.
 *
 * Todo o trabalho e SQL dentro do proprio Postgres: nenhuma conexao com o
 * MySQL legado. Isso torna o refino reexecutavel e testavel sem depender da
 * base procedural estar de pe.
 *
 * Todas as etapas sao idempotentes por codigo_legado, entao a carga pode rodar
 * em ciclos ate o corte, convergindo para o mesmo estado.
 *
 * REGRA CENTRAL DO LEDGER. Somente a etapa "saldos" escreve em
 * ajuda_h_estoque_movimentos, e escreve um unico movimento de ABERTURA por par
 * material/deposito, com o saldo que o legado tem hoje. As etapas "entradas" e
 * "transferencias" carregam o historico como registro, sem lancar movimento:
 * a abertura ja embute o efeito acumulado delas. Lancar as duas coisas dobraria
 * o saldo e quebraria a invariante saldo = soma dos movimentos.
 */
final class RefinarLegadoAjuCommand extends Command
{
    protected $signature = 'legado:aju:refinar {--etapa=* : materiais, depositos, fornecedores, fontes, saldos, entradas, transferencias, liberacoes}';

    protected $description = 'Refina ajuda_h_legado_raw para as tabelas destino do estoque';

    /**
     * Ordem de dependencia: cadastros primeiro, depois o que os referencia.
     */
    private const ETAPAS = [
        'materiais',
        'depositos',
        'fornecedores',
        'fontes',
        'saldos',
        'entradas',
        'transferencias',
        'liberacoes',
    ];

    public function handle(): int
    {
        $pedidas = (array) $this->option('etapa');
        $etapas  = $pedidas === [] ? self::ETAPAS : array_values(array_intersect(self::ETAPAS, $pedidas));

        if ($etapas === []) {
            $this->error('Nenhuma etapa valida. Use: '.implode(', ', self::ETAPAS));

            return self::FAILURE;
        }

        foreach ($etapas as $etapa) {
            $afetadas = match ($etapa) {
                'materiais'       => $this->refinarMateriais(),
                'depositos'       => $this->refinarDepositos(),
                'fornecedores'    => $this->refinarFornecedores(),
                'fontes'          => $this->refinarFontes(),
                'saldos'          => $this->refinarSaldos(),
                'entradas'        => $this->refinarEntradas(),
                'transferencias'  => $this->refinarTransferencias(),
                'liberacoes'      => $this->refinarLiberacoes(),
            };

            $this->line(sprintf('%-16s %6d linhas', $etapa, $afetadas));
        }

        return self::SUCCESS;
    }

    private function refinarMateriais(): int
    {
        return DB::affectingStatement(
            "INSERT INTO materiais_ah
                 (nome, descricao, unidade_medida, disponivel_para_pedido, codigo_legado, created_at, updated_at)
             SELECT
                 coalesce(nullif(trim(doc->>'nome'), ''), 'SEM NOME'),
                 nullif(trim(doc->>'descricao'), ''),
                 coalesce(nullif(trim(doc->>'uni_medida'), ''), 'UN'),
                 true,
                 doc->>'id_unidade',
                 now(), now()
             FROM ajuda_h_legado_raw
             WHERE tabela = 'aju_unidade'
               AND doc->>'id_unidade' IS NOT NULL
             ON CONFLICT (codigo_legado) DO UPDATE
                 SET nome           = EXCLUDED.nome,
                     descricao      = EXCLUDED.descricao,
                     unidade_medida = EXCLUDED.unidade_medida,
                     updated_at     = now()"
        );
    }

    private function refinarDepositos(): int
    {
        return DB::affectingStatement(
            "INSERT INTO ajuda_h_depositos
                 (nome, abreviacao, endereco, ativo, codigo_legado, created_at, updated_at)
             SELECT
                 coalesce(nullif(trim(doc->>'nome'), ''), 'SEM NOME'),
                 coalesce(
                     nullif(trim(doc->>'abreviacao'), ''),
                     'L' || coalesce(doc->>'id_deposito', doc->>'id')
                 ),
                 nullif(trim(doc->>'endereco'), ''),
                 true,
                 coalesce(doc->>'id_deposito', doc->>'id'),
                 now(), now()
             FROM ajuda_h_legado_raw
             WHERE tabela = 'aju_deposito'
               AND coalesce(doc->>'id_deposito', doc->>'id') IS NOT NULL
             ON CONFLICT (codigo_legado) DO UPDATE
                 SET nome       = EXCLUDED.nome,
                     endereco   = EXCLUDED.endereco,
                     updated_at = now()"
        );
    }

    /**
     * O legado guarda CNPJ de preenchimento (00.000.000-0000-00, repetido em
     * dois fornecedores) e valor truncado (00.000.0). Documento invalido nao e
     * identidade: vira NULL, que e o que "desconhecido" significa, e o Postgres
     * aceita varios NULL sob UNIQUE. Preferivel a afrouxar a constraint.
     *
     * A regra e estrutural, nao um julgamento caso a caso: cai fora o que nao
     * tem digito algum diferente de zero ou tem menos digitos que um CPF.
     * O texto original permanece em ajuda_h_legado_raw.
     */
    private function refinarFornecedores(): int
    {
        return DB::affectingStatement(
            "INSERT INTO ajuda_h_fornecedores
                 (nome, cpf_cnpj, endereco, telefone, codigo_legado, created_at, updated_at)
             SELECT
                 coalesce(nullif(trim(doc->>'nome'), ''), 'SEM NOME'),
                 CASE
                     WHEN length(regexp_replace(coalesce(doc->>'cpfcnpj', ''), '\\D', '', 'g')) >= 11
                      AND regexp_replace(coalesce(doc->>'cpfcnpj', ''), '\\D', '', 'g') ~ '[1-9]'
                     THEN trim(doc->>'cpfcnpj')
                 END,
                 nullif(trim(doc->>'endereco'), ''),
                 nullif(trim(doc->>'tel'), ''),
                 doc->>'id',
                 now(), now()
             FROM ajuda_h_legado_raw
             WHERE tabela IN ('aju_fornecedores', 'aju_cfornecedor')
               AND doc->>'id' IS NOT NULL
             ON CONFLICT (codigo_legado) DO UPDATE
                 SET nome       = EXCLUDED.nome,
                     cpf_cnpj   = EXCLUDED.cpf_cnpj,
                     endereco   = EXCLUDED.endereco,
                     telefone   = EXCLUDED.telefone,
                     updated_at = now()"
        );
    }

    private function refinarFontes(): int
    {
        return DB::affectingStatement(
            "INSERT INTO ajuda_h_fontes_recurso (nome, codigo_legado, created_at, updated_at)
             SELECT
                 coalesce(nullif(trim(doc->>'nome'), ''), 'SEM NOME'),
                 doc->>'id',
                 now(), now()
             FROM ajuda_h_legado_raw
             WHERE tabela = 'aju_fonte'
               AND doc->>'id' IS NOT NULL
             ON CONFLICT (codigo_legado) DO UPDATE
                 SET nome       = EXCLUDED.nome,
                     updated_at = now()"
        );
    }

    private function refinarSaldos(): int
    {
        // Unico ponto que escreve no ledger. Um movimento de ABERTURA por par
        // material/deposito, com o documento de origem preso em payload_legado.
        // Reexecutar nao duplica: (origem_tipo, origem_id) identifica a linha da
        // area de pouso que gerou o movimento.
        DB::affectingStatement(
            "INSERT INTO ajuda_h_estoque_movimentos
                 (material_ah_id, deposito_id, quantidade, tipo,
                  origem_tipo, origem_id, ocorrido_em, payload_legado, created_at)
             SELECT
                 m.id, d.id, (r.doc->>'saldo')::numeric, 'ABERTURA',
                 'ajuda_h_legado_raw', r.id, now(), r.doc, now()
             FROM ajuda_h_legado_raw r
             JOIN materiais_ah      m ON m.codigo_legado = r.doc->>'id_produto'
             JOIN ajuda_h_depositos d ON d.codigo_legado = r.doc->>'id_deposito'
             WHERE r.tabela = 'aju_estoque'
               AND (r.doc->>'saldo') ~ '^-?[0-9]+(\\.[0-9]+)?$'
               AND (r.doc->>'saldo')::numeric <> 0
               AND NOT EXISTS (
                   SELECT 1 FROM ajuda_h_estoque_movimentos em
                   WHERE em.origem_tipo = 'ajuda_h_legado_raw' AND em.origem_id = r.id
               )"
        );

        return DB::affectingStatement(
            'INSERT INTO ajuda_h_estoque_saldos (material_ah_id, deposito_id, saldo, atualizado_em)
             SELECT material_ah_id, deposito_id, sum(quantidade), now()
             FROM ajuda_h_estoque_movimentos
             GROUP BY material_ah_id, deposito_id
             ON CONFLICT (material_ah_id, deposito_id) DO UPDATE
                 SET saldo         = EXCLUDED.saldo,
                     atualizado_em = now()'
        );
    }

    /**
     * aju_produto e registro de entrada, apesar do nome. Cada linha vira uma
     * entrada com um item.
     *
     * Nao lanca movimento no ledger: ver a regra central no cabecalho da classe.
     */
    private function refinarEntradas(): int
    {
        $entradas = DB::affectingStatement(
            "INSERT INTO ajuda_h_entradas
                 (deposito_id, fonte_recurso_id, nota_fiscal, recebido_em, cancelado,
                  observacao, payload_legado, codigo_legado, created_at, updated_at)
             SELECT
                 d.id,
                 f.id,
                 nullif(nullif(trim(r.doc->>'nota_fiscal'), ''), '-'),
                 (r.doc->>'dtEntradaSaida')::timestamptz,
                 coalesce((r.doc->>'cancelado')::int, 0) = 1,
                 nullif(trim(r.doc->>'obs'), ''),
                 jsonb_build_object(
                     'origem',         r.doc->>'origem',
                     'depDestino',     r.doc->>'depDestino',
                     'tipo',           r.doc->>'tipo',
                     'id_usuario',     r.doc->>'id_usuario',
                     'id_entrada',     r.doc->>'id_entrada',
                     'id_dep_origem',  r.doc->>'id_dep_origem'
                 ),
                 r.doc->>'id_produto',
                 now(), now()
             FROM ajuda_h_legado_raw r
             -- id_dep_destino e a via normal; o nome textual em depDestino
             -- recupera a unica linha do legado que veio sem o id.
             JOIN ajuda_h_depositos d
               ON d.codigo_legado = r.doc->>'id_dep_destino'
               OR (r.doc->>'id_dep_destino' IS NULL
                   AND upper(unaccent(trim(d.nome))) = upper(unaccent(trim(r.doc->>'depDestino'))))
             LEFT JOIN ajuda_h_fontes_recurso f
               ON upper(unaccent(trim(f.nome))) = upper(unaccent(trim(r.doc->>'origem')))
             WHERE r.tabela = 'aju_produto'
               AND r.doc->>'id_produto' IS NOT NULL
             ON CONFLICT (codigo_legado) DO UPDATE
                 SET deposito_id      = EXCLUDED.deposito_id,
                     fonte_recurso_id = EXCLUDED.fonte_recurso_id,
                     nota_fiscal      = EXCLUDED.nota_fiscal,
                     recebido_em      = EXCLUDED.recebido_em,
                     cancelado        = EXCLUDED.cancelado,
                     observacao       = EXCLUDED.observacao,
                     payload_legado   = EXCLUDED.payload_legado,
                     updated_at       = now()"
        );

        // O item nao tem chave propria no legado: a entrada e o item sao a mesma
        // linha de aju_produto. A entrada ja e unica por codigo_legado, entao o
        // NOT EXISTS sobre entrada_id basta para nao duplicar.
        DB::affectingStatement(
            "INSERT INTO ajuda_h_entrada_itens (entrada_id, material_ah_id, qtd, data_validade)
             SELECT
                 e.id,
                 m.id,
                 (r.doc->>'quantidade')::numeric,
                 CASE WHEN r.doc->>'validade' ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                      THEN (r.doc->>'validade')::date END
             FROM ajuda_h_legado_raw r
             JOIN ajuda_h_entradas e ON e.codigo_legado = r.doc->>'id_produto'
             JOIN materiais_ah     m ON m.codigo_legado = r.doc->>'codProd'
             WHERE r.tabela = 'aju_produto'
               AND (r.doc->>'quantidade') ~ '^-?[0-9]+(\\.[0-9]+)?$'
               AND NOT EXISTS (
                   SELECT 1 FROM ajuda_h_entrada_itens i WHERE i.entrada_id = e.id
               )"
        );

        return $entradas;
    }

    /**
     * Nao lanca movimento no ledger: ver a regra central no cabecalho da classe.
     */
    private function refinarTransferencias(): int
    {
        // O legado tem transferencia com origem igual ao destino, que o CHECK
        // ajuda_h_transf_depositos_distintos_ck recusa com razao. Ficam de fora
        // e sao reportadas pelo comando de conferencia, em vez de relaxar a
        // invariante para acomodar dado sujo.
        $transferencias = DB::affectingStatement(
            "INSERT INTO ajuda_h_transferencias
                 (deposito_origem_id, deposito_destino_id, motorista, veiculo, placa,
                  saiu_em, chegou_em, status, responsavel, observacao, codigo_legado,
                  created_at, updated_at)
             SELECT
                 o.id, dst.id,
                 nullif(nullif(trim(r.doc->>'motorista'), ''), '-'),
                 nullif(nullif(trim(r.doc->>'veiculo'), ''), '-'),
                 nullif(nullif(trim(r.doc->>'placa'), ''), '-'),
                 CASE WHEN r.doc->>'dt_saida' ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                      THEN (r.doc->>'dt_saida')::timestamptz END,
                 CASE WHEN r.doc->>'dt_chegada' ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                      THEN (r.doc->>'dt_chegada')::timestamptz END,
                 coalesce((r.doc->>'situacao')::int, 0),
                 nullif(nullif(trim(r.doc->>'responsavel'), ''), '-'),
                 nullif(trim(r.doc->>'obs'), ''),
                 r.doc->>'id_transferencia',
                 now(), now()
             FROM ajuda_h_legado_raw r
             JOIN ajuda_h_depositos o   ON o.codigo_legado   = r.doc->>'id_dep_origem'
             JOIN ajuda_h_depositos dst ON dst.codigo_legado = r.doc->>'id_dep_destino'
             WHERE r.tabela = 'aju_transferencia'
               AND r.doc->>'id_transferencia' IS NOT NULL
               AND r.doc->>'id_dep_origem' <> r.doc->>'id_dep_destino'
             ON CONFLICT (codigo_legado) DO UPDATE
                 SET deposito_origem_id  = EXCLUDED.deposito_origem_id,
                     deposito_destino_id = EXCLUDED.deposito_destino_id,
                     status              = EXCLUDED.status,
                     saiu_em             = EXCLUDED.saiu_em,
                     chegou_em           = EXCLUDED.chegou_em,
                     updated_at          = now()"
        );

        DB::affectingStatement(
            "INSERT INTO ajuda_h_transferencia_itens (transferencia_id, material_ah_id, qtd, status)
             SELECT
                 t.id, m.id,
                 (r.doc->>'quantidade')::numeric,
                 coalesce((r.doc->>'situacao')::int, 0)
             FROM ajuda_h_legado_raw r
             JOIN ajuda_h_transferencias t ON t.codigo_legado = r.doc->>'id_transferencia'
             JOIN materiais_ah           m ON m.codigo_legado = r.doc->>'id_produto'
             WHERE r.tabela = 'aju_item_transf'
               AND (r.doc->>'quantidade') ~ '^-?[0-9]+(\\.[0-9]+)?$'
               AND NOT EXISTS (
                   SELECT 1 FROM ajuda_h_transferencia_itens i
                   WHERE i.transferencia_id = t.id AND i.material_ah_id = m.id
               )"
        );

        return $transferencias;
    }

    /**
     * Liberacoes e seus recibos de pagamento.
     *
     * Duas decisoes que valem registro.
     *
     * MUNICIPIO. aju_liberacao.id_municipio nao aponta para aju_municipio, e sim
     * para cedec_municipio, o espelho do cadastro antigo que o NewSDC ja carrega
     * (confere em 3.582 de 3.582). A ponte ate public.municipios e o codigo IBGE
     * em cedec_municipio.Codmundv, mesmo caminho ja usado pelo arquivo morto do
     * RAT. Casar por nome resolveria so 3.560: 22 liberacoes ficariam de fora
     * por divergencia de grafia.
     *
     * SOLICITANTE. Fica NULL de proposito. aju_liberacao.id_usuario vai de 26 a
     * 180 e todo valor coincide numericamente com algum users.id, o que parece
     * um mapeamento pronto e nao e: users.id 73 no NewSDC e BERIZAL665, uma
     * conta de municipio, nao o oficial da CEDEC que autorizou a liberacao.
     * Aproveitar a coincidencia atribuiria entrega de ajuda humanitaria a pessoa
     * errada. O id original fica em payload_legado ate existir um De-Para real.
     *
     * Nao lanca movimento no ledger: ver a regra central no cabecalho da classe.
     */
    private function refinarLiberacoes(): int
    {
        $liberacoes = DB::affectingStatement(
            "INSERT INTO ajuda_h_liberacoes
                 (municipio_id, deposito_id, beneficiario, data_libera, data_limite,
                  status, observacao, cancelado_em, motivo_cancelamento,
                  payload_legado, codigo_legado, created_at, updated_at)
             SELECT
                 mun.id,
                 dep.id,
                 nullif(trim(r.doc->>'beneficiario'), ''),
                 (r.doc->>'dataLibera')::date,
                 CASE WHEN r.doc->>'dtLimite' ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                      THEN (r.doc->>'dtLimite')::date END,
                 coalesce((r.doc->>'situacao')::int, 0),
                 nullif(trim(r.doc->>'observacao'), ''),
                 CASE WHEN r.doc->>'dt_cancela' ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                      THEN (r.doc->>'dt_cancela')::timestamptz END,
                 nullif(trim(r.doc->>'m_cancela'), ''),
                 jsonb_build_object(
                     'id_usuario',         r.doc->>'id_usuario',
                     'id_user_pgto',       r.doc->>'id_user_pgto',
                     'responsavel',        r.doc->>'responsavel',
                     'evento',             r.doc->>'evento',
                     'entrega',            r.doc->>'entrega',
                     'hora_libera',        r.doc->>'hora_libera',
                     'dt_recibo',          r.doc->>'dt_recibo',
                     'resp_receb',         r.doc->>'resp_receb',
                     'resp_receb_ci',      r.doc->>'resp_receb_ci',
                     'resp_receb_cpf',     r.doc->>'resp_receb_cpf',
                     'resp_receb_veiculo', r.doc->>'resp_receb_veiculo',
                     'resp_receb_placa',   r.doc->>'resp_receb_placa'
                 ),
                 r.doc->>'id_liberacao',
                 now(), now()
             FROM ajuda_h_legado_raw r
             JOIN cedec_municipio  c   ON c.id::text = r.doc->>'id_municipio'
             JOIN municipios       mun ON mun.codigo_ibge::text = c.\"Codmundv\"::text
             JOIN ajuda_h_depositos dep ON dep.codigo_legado = r.doc->>'depDestino'
             WHERE r.tabela = 'aju_liberacao'
               AND r.doc->>'id_liberacao' IS NOT NULL
               AND (r.doc->>'dataLibera') ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
             ON CONFLICT (codigo_legado) DO UPDATE
                 SET municipio_id        = EXCLUDED.municipio_id,
                     deposito_id         = EXCLUDED.deposito_id,
                     beneficiario        = EXCLUDED.beneficiario,
                     status              = EXCLUDED.status,
                     observacao          = EXCLUDED.observacao,
                     cancelado_em        = EXCLUDED.cancelado_em,
                     motivo_cancelamento = EXCLUDED.motivo_cancelamento,
                     payload_legado      = EXCLUDED.payload_legado,
                     updated_at          = now()"
        );

        // O legado tem um pagamento cuja liberacao nao existe. O JOIN o deixa de
        // fora, que e o comportamento correto: recibo sem liberacao nao tem
        // significado.
        DB::affectingStatement(
            "INSERT INTO ajuda_h_liberacao_recibos
                 (liberacao_id, pago_em, n_documento, n_recibo, responsavel_recebimento,
                  cpf_responsavel, placa_veiculo, status, motivo, created_at, updated_at)
             SELECT
                 l.id,
                 CASE WHEN r.doc->>'dtPagto' ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                      THEN (r.doc->>'dtPagto')::date END,
                 nullif(nullif(trim(r.doc->>'nDocumento'), ''), '-'),
                 CASE WHEN (r.doc->>'n_recibo') ~ '^[0-9]+$'
                      THEN (r.doc->>'n_recibo')::int END,
                 nullif(nullif(trim(r.doc->>'responsavel'), ''), '-'),
                 -- cpf_resp vem com lixo de mascara vazia ('..-'): so entra o
                 -- que tem digito.
                 CASE WHEN regexp_replace(coalesce(r.doc->>'cpf_resp',''), '\\D', '', 'g') <> ''
                      THEN trim(r.doc->>'cpf_resp') END,
                 nullif(nullif(trim(r.doc->>'placa'), ''), '-'),
                 CASE WHEN (r.doc->>'situacao') ~ '^[0-9]+$'
                      THEN (r.doc->>'situacao')::int ELSE 0 END,
                 nullif(trim(r.doc->>'motivo'), ''),
                 now(), now()
             FROM ajuda_h_legado_raw r
             JOIN ajuda_h_liberacoes l ON l.codigo_legado = r.doc->>'id_liberacao'
             WHERE r.tabela = 'aju_pagamento'
               AND NOT EXISTS (
                   SELECT 1 FROM ajuda_h_liberacao_recibos rec
                   WHERE rec.liberacao_id = l.id
                     AND rec.n_documento IS NOT DISTINCT FROM nullif(nullif(trim(r.doc->>'nDocumento'), ''), '-')
                     AND rec.pago_em IS NOT DISTINCT FROM
                         (CASE WHEN r.doc->>'dtPagto' ~ '^[0-9]{4}-[0-9]{2}-[0-9]{2}'
                               THEN (r.doc->>'dtPagto')::date END)
               )"
        );

        return $liberacoes;
    }
}
