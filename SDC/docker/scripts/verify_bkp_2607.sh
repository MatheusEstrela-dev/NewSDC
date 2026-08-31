#!/usr/bin/env bash
# ============================================================================
# Verificacao do merge do backup 2026-07-27 (ver merge_bkp_2607.sh).
#
#   1. contagem por tabela: backup vs live pos-merge (tem que casar)
#   2. dados da Cisterna intactos contra o baseline pre-merge
#   3. orfaos em TODAS as FKs do schema public (esperado: zero)
#   4. sequences a frente do max(id) das tabelas importadas
#
# Uso: bash docker/scripts/verify_bkp_2607.sh
# ============================================================================
set -uo pipefail

SRC=${SRC:-newsdc_dev_db_bkp}
DST=${DST:-newsdc_dev_db}
PGU=${PGU:-sdc}
PGDB=${PGDB:-sdc}

psql_src() { docker exec "$SRC" psql -U "$PGU" -d "$PGDB" -tAq "$@"; }
psql_dst() { docker exec "$DST" psql -U "$PGU" -d "$PGDB" -tAq "$@"; }

TABELAS="compdec_orgaos users compdec_orgao_user user_notification_preferences
user_status_histories legado_rat_alvo legado_rat_ocorrencia cedec_municipio
compdec_anexos planos_contingencia media personal_access_tokens dec_cobrade
dec_decreto_categorias dec_desastre_categorias dec_desastre_grupos
dec_desastre_item_campos dec_desastre_items dec_decreto_municipios
dec_entrada_processos dec_entrada_decretos dec_entrada_desastres
dec_entrada_categoria_desastres legado_rat rat_ocorrencias rat_ocorrencia_relatos
rat_relato_dados_gerais rat_relato_envolvidos rat_relato_recursos
rat_relato_vistoria rat_recursos_componentes_guarnicao rat_anexos pae_protocolos
pae_forms pae_form_anexos pae_form_apontamentos pae_form_conclusao pae_timeline
pae_tramit_prot tdap_prestadores tdap_caminhoes tdap_lotes tdap_atas
tdap_cronogramas tdap_crono_caminhoes tdap_crono_viagens tdap_vistorias
tdap_historicos pmda_planos pmda_comunidades pmda_compdec_membros
pmda_plano_ponto pip_pmda_ponto model_has_roles model_has_permissions"

echo "== 1. Contagem backup vs live =="
falhas=0
for t in $TABELAS; do
  a=$(psql_src -c "select count(*) from public.$t")
  b=$(psql_dst -c "select count(*) from public.$t")
  if [ "$a" != "$b" ]; then
    printf '   DIVERGE  %-38s bkp=%-8s live=%s\n' "$t" "$a" "$b"
    falhas=$((falhas + 1))
  fi
done
if [ "$falhas" -eq 0 ]; then
  echo "   OK: todas as $(echo $TABELAS | wc -w) tabelas com contagem identica ao backup"
else
  echo "   $falhas tabela(s) divergente(s)"
fi

echo
echo "== 2. Cisterna e referencias intactas (esperado ao lado) =="
psql_dst -c "
with esperado(t, n) as (values
  ('cisterna_itens_conferidos', 27677), ('cisterna_etl_log', 19624),
  ('cisterna_legado_raw', 11396), ('cisterna_beneficiarios', 8096),
  ('cisterna_atendimentos_pipa', 2904), ('cisterna_vistorias', 2129),
  ('cisterna_comunidades', 840), ('cisterna_ordens_servico', 7),
  ('cisterna_notificacoes', 7), ('cisterna_lotes', 3),
  ('municipios', 853), ('migrations', 214), ('permissions', 258), ('roles', 14))
select e.t as tabela, e.n as esperado,
       (xpath('/row/c/text()', query_to_xml(format('select count(*) c from public.%I', e.t), false, true, '')))[1]::text::bigint as atual,
       case when (xpath('/row/c/text()', query_to_xml(format('select count(*) c from public.%I', e.t), false, true, '')))[1]::text::bigint = e.n
            then 'OK' else '*** DIVERGE ***' end as status
from esperado e order by e.n desc;"

echo "== 3. Orfaos em todas as FKs do schema public =="
psql_dst -c "
with fk as (
  select c.conname,
         c.conrelid::regclass::text as filha,
         c.confrelid::regclass::text as pai,
         (select array_agg(quote_ident(a.attname) order by k.ord)
            from unnest(c.conkey) with ordinality k(att, ord)
            join pg_attribute a on a.attrelid = c.conrelid and a.attnum = k.att) as scols,
         (select array_agg(quote_ident(a.attname) order by k.ord)
            from unnest(c.confkey) with ordinality k(att, ord)
            join pg_attribute a on a.attrelid = c.confrelid and a.attnum = k.att) as tcols
    from pg_constraint c
   where c.contype = 'f' and c.connamespace = 'public'::regnamespace
), q as (
  select conname, filha, pai,
         format('select count(*) c from %s s where %s and not exists (select 1 from %s t where %s)',
                filha,
                (select string_agg(format('s.%s is not null', col), ' and ') from unnest(scols) col),
                pai,
                (select string_agg(format('t.%s = s.%s', u.tc, u.sc), ' and ')
                   from unnest(tcols, scols) with ordinality u(tc, sc, ord))) as sql
    from fk
), r as (
  select conname, filha, pai,
         (xpath('/row/c/text()', query_to_xml(sql, false, true, '')))[1]::text::bigint as orfaos
    from q
)
select count(*) as fks_verificadas,
       count(*) filter (where orfaos > 0) as fks_com_orfaos,
       coalesce(sum(orfaos), 0) as linhas_orfas
  from r;
with fk as (
  select c.conname, c.conrelid::regclass::text as filha, c.confrelid::regclass::text as pai,
         (select array_agg(quote_ident(a.attname) order by k.ord) from unnest(c.conkey) with ordinality k(att, ord)
            join pg_attribute a on a.attrelid = c.conrelid and a.attnum = k.att) as scols,
         (select array_agg(quote_ident(a.attname) order by k.ord) from unnest(c.confkey) with ordinality k(att, ord)
            join pg_attribute a on a.attrelid = c.confrelid and a.attnum = k.att) as tcols
    from pg_constraint c where c.contype = 'f' and c.connamespace = 'public'::regnamespace
), q as (
  select conname, filha, pai,
         format('select count(*) c from %s s where %s and not exists (select 1 from %s t where %s)',
                filha,
                (select string_agg(format('s.%s is not null', col), ' and ') from unnest(scols) col),
                pai,
                (select string_agg(format('t.%s = s.%s', u.tc, u.sc), ' and ')
                   from unnest(tcols, scols) with ordinality u(tc, sc, ord))) as sql
    from fk
)
select filha, pai, conname,
       (xpath('/row/c/text()', query_to_xml(sql, false, true, '')))[1]::text::bigint as orfaos
  from q
 where (xpath('/row/c/text()', query_to_xml(sql, false, true, '')))[1]::text::bigint > 0
 order by orfaos desc;"

echo "== 4. Sequences das tabelas importadas =="
psql_dst -c "
select count(*) as sequences_verificadas,
       count(*) filter (where ultimo_valor < max_id) as atrasadas
  from (
    select c.relname,
           (xpath('/row/c/text()', query_to_xml(format('select coalesce(max(%I),0) c from public.%I', a.attname, c.relname), false, true, '')))[1]::text::bigint as max_id,
           (select last_value from pg_sequences where schemaname = 'public'
              and 'public.' || quote_ident(sequencename) = pg_get_serial_sequence('public.' || quote_ident(c.relname), a.attname)) as ultimo_valor
      from pg_class c
      join pg_namespace n on n.oid = c.relnamespace
      join pg_attribute a on a.attrelid = c.oid and a.attnum > 0 and not a.attisdropped
     where n.nspname = 'public' and c.relkind = 'r'
       and pg_get_serial_sequence('public.' || quote_ident(c.relname), a.attname) is not null
  ) s
 where max_id > 0;"
