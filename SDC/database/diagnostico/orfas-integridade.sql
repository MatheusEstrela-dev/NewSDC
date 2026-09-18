-- ============================================================================
-- DIAGNOSTICO DE REFERENCIAS ORFAS -- SOMENTE LEITURA
--
-- Nenhum INSERT, UPDATE, DELETE ou DDL. Pode rodar em producao com seguranca,
-- inclusive em horario de expediente: sao contagens sobre indice.
--
-- POR QUE ESTE LEVANTAMENTO EXISTE
--
-- O banco de homologacao (copia de producao) tem 6 foreign keys que as
-- migrations definem e o banco nao possui. A causa esta no proprio dump: ele
-- carrega, no mesmo arquivo, uma FK VALIDA e linhas que a violam. No restore,
-- o pg_restore aborta cada ADD CONSTRAINT por violacao, REGISTRA O ERRO E
-- SEGUE -- sem --exit-on-error ele nao falha. Os dados entram, as constraints
-- nao, e o banco resultante "passa" com 6 FKs a menos.
--
-- O QUE PRECISA SER RESPONDIDO AQUI
--
-- Em homologacao, 303 viagens de agua orfas estao ATIVAS e 206 delas tem
-- aprovacao registrada. A cronologia mostra que os caminhoes-pai existiam e
-- estavam em uso quando essas viagens foram aprovadas: foram apagados DEPOIS,
-- por DELETE manual fora da aplicacao (a aplicacao so faz soft delete).
--
-- Se producao tiver o mesmo quadro, o problema NAO e de foreign key -- e
-- perda de registro de entrega de agua potavel, com efeito financeiro, e a
-- conversa passa a ser outra. Se producao estiver limpa, o que ha em
-- homologacao e sujeira de recarga e pode ser tratada como tal.
--
-- COMO RODAR
--   psql -h <host> -U <usuario> -d <banco> -f orfas-integridade.sql
-- ============================================================================

\echo '=== 1. FOREIGN KEYS PRESENTES (esperado: 259 apos as migrations recentes) ==='
SELECT count(*) AS fks_existentes FROM pg_constraint WHERE contype = 'f';

\echo ''
\echo '=== 2. AS 6 FKs QUE FALTAM EM HOMOLOGACAO: existem aqui? ==='
SELECT
    esperada.conname AS foreign_key,
    CASE WHEN c.oid IS NULL THEN 'AUSENTE' ELSE 'presente' END AS situacao
FROM (VALUES
    ('tdap_lotes_municipio_id_foreign'),
    ('tdap_crono_caminhoes_cronograma_id_foreign'),
    ('tdap_crono_viagens_crono_caminhao_id_foreign'),
    ('tdap_cronogramas_ponto_captacao_id_foreign'),
    ('dec_decreto_municipios_entrada_processos_id_foreign'),
    ('dec_entrada_decretos_entrada_processos_id_foreign')
) AS esperada(conname)
LEFT JOIN pg_constraint c ON c.conname = esperada.conname AND c.contype = 'f'
ORDER BY 2 DESC, 1;

\echo ''
\echo '=== 3. CONTAGEM DE ORFAS POR RELACAO ==='
SELECT 'tdap_lotes.municipio_id' AS relacao,
       count(*) FILTER (WHERE m.id IS NULL) AS orfas,
       count(*) AS total
  FROM tdap_lotes l
  LEFT JOIN municipios m ON m.id = l.municipio_id
 WHERE l.municipio_id IS NOT NULL
UNION ALL
SELECT 'tdap_crono_caminhoes.cronograma_id',
       count(*) FILTER (WHERE p.id IS NULL), count(*)
  FROM tdap_crono_caminhoes c LEFT JOIN tdap_cronogramas p ON p.id = c.cronograma_id
 WHERE c.cronograma_id IS NOT NULL
UNION ALL
SELECT 'tdap_crono_viagens.crono_caminhao_id',
       count(*) FILTER (WHERE p.id IS NULL), count(*)
  FROM tdap_crono_viagens v LEFT JOIN tdap_crono_caminhoes p ON p.id = v.crono_caminhao_id
 WHERE v.crono_caminhao_id IS NOT NULL
UNION ALL
SELECT 'tdap_cronogramas.ponto_captacao_id',
       count(*) FILTER (WHERE p.id IS NULL), count(*)
  FROM tdap_cronogramas c LEFT JOIN pip_pmda_ponto p ON p.id = c.ponto_captacao_id
 WHERE c.ponto_captacao_id IS NOT NULL
UNION ALL
SELECT 'dec_decreto_municipios.entrada_processos_id',
       count(*) FILTER (WHERE p.id IS NULL), count(*)
  FROM dec_decreto_municipios d LEFT JOIN dec_entrada_processos p ON p.id = d.entrada_processos_id
 WHERE d.entrada_processos_id IS NOT NULL
UNION ALL
SELECT 'dec_entrada_decretos.entrada_processos_id',
       count(*) FILTER (WHERE p.id IS NULL), count(*)
  FROM dec_entrada_decretos e LEFT JOIN dec_entrada_processos p ON p.id = e.entrada_processos_id
 WHERE e.entrada_processos_id IS NOT NULL
ORDER BY 2 DESC;

\echo ''
\echo '=== 4. A PERGUNTA QUE DECIDE TUDO: viagens orfas com aprovacao registrada ==='
\echo '    Se vier > 0, houve perda de registro de entrega e o caso muda de natureza.'
SELECT
    count(*) AS viagens_orfas,
    count(*) FILTER (WHERE v.validado = 1) AS aprovadas,
    count(*) FILTER (WHERE v.deleted_at IS NOT NULL) AS ja_apagadas_logicamente,
    min(v.data_registro) AS registro_mais_antigo,
    max(v.data_registro) AS registro_mais_recente
  FROM tdap_crono_viagens v
 WHERE v.crono_caminhao_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM tdap_crono_caminhoes c WHERE c.id = v.crono_caminhao_id);

\echo ''
\echo '=== 5. EFEITO EM CASCATA: apagar caminhao orfao derrubaria viagem? ==='
\echo '    Se vier > 0, a limpeza NAO pode ser feita sem tratar as viagens antes.'
SELECT count(*) AS viagens_penduradas_em_caminhao_orfao
  FROM tdap_crono_viagens v
  JOIN tdap_crono_caminhoes c ON c.id = v.crono_caminhao_id
 WHERE NOT EXISTS (SELECT 1 FROM tdap_cronogramas p WHERE p.id = c.cronograma_id);

\echo ''
\echo '=== 6. DINHEIRO ENVOLVIDO nos caminhoes orfaos ==='
SELECT
    count(*) AS caminhoes_orfaos,
    coalesce(sum(c.vr_total), 0) AS valor_total,
    coalesce(sum(c.num_viagens), 0) AS viagens_previstas,
    count(*) FILTER (WHERE c.deleted_at IS NOT NULL) AS ja_apagados_logicamente
  FROM tdap_crono_caminhoes c
 WHERE c.cronograma_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM tdap_cronogramas p WHERE p.id = c.cronograma_id);

\echo ''
\echo '=== 7. CLONES: em homologacao, 5 copias identicas somavam 96% do valor ==='
\echo '    Se o mesmo padrao aparecer aqui, tambem e lixo de carga e nao operacao.'
SELECT c.cronograma_id, count(*) AS linhas,
       sum(c.vr_total) AS valor, sum(c.num_viagens) AS viagens
  FROM tdap_crono_caminhoes c
 WHERE c.cronograma_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM tdap_cronogramas p WHERE p.id = c.cronograma_id)
 GROUP BY c.cronograma_id
 ORDER BY 3 DESC NULLS LAST
 LIMIT 15;

\echo ''
\echo '=== 8. Os pais sumiram por DELETE fisico? (a aplicacao so faz soft delete) ==='
SELECT 'tdap_cronogramas' AS tabela,
       (SELECT max(id) FROM tdap_cronogramas) AS maior_id,
       (SELECT count(*) FROM tdap_cronogramas) AS linhas,
       (SELECT max(id) FROM tdap_cronogramas) - (SELECT count(*) FROM tdap_cronogramas) AS buracos
UNION ALL
SELECT 'tdap_crono_caminhoes',
       (SELECT max(id) FROM tdap_crono_caminhoes),
       (SELECT count(*) FROM tdap_crono_caminhoes),
       (SELECT max(id) FROM tdap_crono_caminhoes) - (SELECT count(*) FROM tdap_crono_caminhoes)
UNION ALL
SELECT 'dec_entrada_processos',
       (SELECT max(id) FROM dec_entrada_processos),
       (SELECT count(*) FROM dec_entrada_processos),
       (SELECT max(id) FROM dec_entrada_processos) - (SELECT count(*) FROM dec_entrada_processos);
