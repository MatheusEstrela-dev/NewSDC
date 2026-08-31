#!/usr/bin/env bash
# ============================================================================
# Merge logico do cluster de backup 2026-07-27 (db_bkp, host 5435) para dentro
# do banco de desenvolvimento (db, host 5434), preservando os dados da Cisterna.
#
# O backup e de um schema pre-Cisterna: restaurar o volume por cima apagaria as
# tabelas cisterna_*. Por isso o cluster do backup sobe lado a lado (servico
# db_bkp no compose.dev.yml) e apenas os dados dos modulos sao copiados tabela
# a tabela, pela intersecao de colunas dos dois lados.
#
# Uso:
#   bash docker/scripts/merge_bkp_2607.sh              # dry-run (nao escreve)
#   bash docker/scripts/merge_bkp_2607.sh --apply      # executa
#
# Idempotente: cada tabela e limpada com DELETE antes da recarga. Nunca usa
# TRUNCATE CASCADE (arrastaria cisterna_beneficiarios via FK de created_by).
# As FKs sao desligadas na sessao de destino com session_replication_role.
# ============================================================================
set -euo pipefail

SRC=${SRC:-newsdc_dev_db_bkp}
DST=${DST:-newsdc_dev_db}
PGU=${PGU:-sdc}
PGDB=${PGDB:-sdc}

MODE="${1:---dry-run}"
case "$MODE" in
  --dry-run|--apply) ;;
  *) echo "uso: $0 [--dry-run|--apply]" >&2; exit 2 ;;
esac

# Tabelas cujo conteudo do live e substituido pelo do backup. Todas sao
# escopadas em usuario/orgao: precisam vir do backup para que os IDs casem com
# os users importados (users.orgao_principal_id -> compdec_orgaos).
REPLACE_TABLES=(
  compdec_orgaos
  users
  compdec_orgao_user
  user_notification_preferences
  user_status_histories
  legado_rat_alvo
  legado_rat_ocorrencia
)

# Tabelas com dados no backup e vazias no live (dados dos modulos).
IMPORT_TABLES=(
  cedec_municipio
  compdec_anexos
  planos_contingencia
  media
  personal_access_tokens
  dec_cobrade
  dec_decreto_categorias
  dec_desastre_categorias
  dec_desastre_grupos
  dec_desastre_item_campos
  dec_desastre_items
  dec_decreto_municipios
  dec_entrada_processos
  dec_entrada_decretos
  dec_entrada_desastres
  dec_entrada_categoria_desastres
  legado_rat
  rat_ocorrencias
  rat_ocorrencia_relatos
  rat_relato_dados_gerais
  rat_relato_envolvidos
  rat_relato_recursos
  rat_relato_vistoria
  rat_recursos_componentes_guarnicao
  rat_anexos
  pae_protocolos
  pae_forms
  pae_form_anexos
  pae_form_apontamentos
  pae_form_conclusao
  pae_timeline
  pae_tramit_prot
  tdap_prestadores
  tdap_caminhoes
  tdap_lotes
  tdap_atas
  tdap_cronogramas
  tdap_crono_caminhoes
  tdap_crono_viagens
  tdap_vistorias
  tdap_historicos
  pmda_planos
  pmda_comunidades
  pmda_compdec_membros
  pmda_plano_ponto
  pip_pmda_ponto
)

# NAO TOCADAS (motivo):
#   cisterna_*                -> dados atuais sao canonicos, ausentes no backup
#   municipios, spatial_ref_sys -> identicas nos dois lados
#   migrations                -> schema do live e mais novo (214 vs 186)
#   permissions, roles, role_has_permissions -> semeadas por codigo; live mais
#                                atual (258 permissions / 14 roles / 1002 vinculos)
#   audit_logs, permission_audit_log, webhook_events -> log operacional de julho
#   _stg_cedec_mun, staging_users_legado -> tabelas de staging que nao existem
#                                mais no schema atual

psql_src() { docker exec "$SRC" psql -U "$PGU" -d "$PGDB" -v ON_ERROR_STOP=1 "$@"; }
psql_dst() { docker exec "$DST" psql -U "$PGU" -d "$PGDB" -v ON_ERROR_STOP=1 "$@"; }

count_src() { psql_src -tAc "select count(*) from public.$1"; }
count_dst() { psql_dst -tAc "select count(*) from public.$1"; }

# Intersecao de colunas entre backup e live, na ordem do live.
common_cols() {
  local t="$1" b
  b=$(psql_src -tAc "select coalesce(string_agg(column_name, ','), '') from information_schema.columns where table_schema='public' and table_name='$t'")
  psql_dst -tAc "select string_agg(quote_ident(column_name), ',' order by ordinal_position) from information_schema.columns where table_schema='public' and table_name='$t' and column_name = any(string_to_array('$b', ','))"
}

dropped_cols() {
  local t="$1" b
  b=$(psql_src -tAc "select coalesce(string_agg(column_name, ','), '') from information_schema.columns where table_schema='public' and table_name='$t'")
  psql_src -tAc "select coalesce(string_agg(column_name, ','), '-') from information_schema.columns where table_schema='public' and table_name='$t' and column_name <> all(string_to_array('$(psql_dst -tAc "select coalesce(string_agg(column_name, ','), '') from information_schema.columns where table_schema='public' and table_name='$t'")', ','))"
}

copy_table() {
  local t="$1" cols n_src n_dst drop
  cols=$(common_cols "$t")
  if [ -z "$cols" ]; then
    echo "  !! $t: sem colunas em comum — PULADA"
    return
  fi
  n_src=$(count_src "$t")
  n_dst=$(count_dst "$t")
  drop=$(dropped_cols "$t")
  printf '  %-38s bkp=%-7s live=%-7s descartadas=%s\n' "$t" "$n_src" "$n_dst" "$drop"

  [ "$MODE" = "--dry-run" ] && return

  docker exec "$SRC" psql -U "$PGU" -d "$PGDB" -v ON_ERROR_STOP=1 -qtAc \
      "copy (select $cols from public.$t) to stdout" \
    | docker exec -i "$DST" psql -U "$PGU" -d "$PGDB" -v ON_ERROR_STOP=1 -q \
      -c "set session_replication_role = replica" \
      -c "delete from public.$t" \
      -c "copy public.$t ($cols) from stdin"
}

# Vinculos do Spatie: os IDs de role/permission divergem entre os clusters, so
# os nomes casam. Recarrega model_has_roles/model_has_permissions traduzindo
# role_id/permission_id pelo nome via tabela temporaria no destino.
copy_pivot_by_name() {
  local t="$1" ref="$2" fk="$3" n_src n_dst
  n_src=$(psql_src -tAc "select count(*) from public.$t")
  n_dst=$(psql_dst -tAc "select count(*) from public.$t")
  printf '  %-38s bkp=%-7s live=%-7s (remapeado por nome via %s)\n' "$t" "$n_src" "$n_dst" "$ref"

  [ "$MODE" = "--dry-run" ] && return

  docker exec "$SRC" psql -U "$PGU" -d "$PGDB" -v ON_ERROR_STOP=1 -qtAc \
      "copy (select r.name, p.model_type, p.model_id from public.$t p join public.$ref r on r.id = p.$fk) to stdout" \
    | docker exec -i "$DST" psql -U "$PGU" -d "$PGDB" -v ON_ERROR_STOP=1 -q \
      -c "set session_replication_role = replica" \
      -c "create temp table tmp_pivot (ref_name text, model_type text, model_id bigint)" \
      -c "copy tmp_pivot from stdin" \
      -c "delete from public.$t" \
      -c "insert into public.$t ($fk, model_type, model_id) select r.id, t.model_type, t.model_id from tmp_pivot t join public.$ref r on r.name = t.ref_name" \
      -c "select count(*) as descartados_sem_nome_correspondente from tmp_pivot t where not exists (select 1 from public.$ref r where r.name = t.ref_name)"
}

fix_sequences() {
  [ "$MODE" = "--dry-run" ] && { echo "  (dry-run: setval das sequences nao executado)"; return; }
  psql_dst -q -c "
do \$\$
declare r record; seq text; mx bigint;
begin
  for r in select c.table_name, c.column_name
             from information_schema.columns c
             join information_schema.tables t
               on t.table_schema = c.table_schema and t.table_name = c.table_name
            where c.table_schema = 'public' and t.table_type = 'BASE TABLE'
  loop
    seq := pg_get_serial_sequence('public.' || quote_ident(r.table_name), r.column_name);
    if seq is not null then
      execute format('select coalesce(max(%I), 0) from public.%I', r.column_name, r.table_name) into mx;
      perform setval(seq, greatest(mx, 1), mx > 0);
    end if;
  end loop;
end \$\$;"
  echo "  sequences realinhadas ao max(id) de cada tabela"
}

echo "=============================================================="
echo " merge_bkp_2607  modo=$MODE  origem=$SRC(5435)  destino=$DST(5434)"
echo "=============================================================="
echo
echo "[1/4] Substituicao (escopo usuario/orgao)"
for t in "${REPLACE_TABLES[@]}"; do copy_table "$t"; done
echo
echo "[2/4] Importacao dos modulos (${#IMPORT_TABLES[@]} tabelas)"
for t in "${IMPORT_TABLES[@]}"; do copy_table "$t"; done
echo
echo "[3/4] Vinculos de permissao remapeados por nome"
copy_pivot_by_name model_has_roles roles role_id
copy_pivot_by_name model_has_permissions permissions permission_id
echo
echo "[4/4] Sequences"
fix_sequences
echo
if [ "$MODE" = "--dry-run" ]; then
  echo "DRY-RUN concluido: nada foi escrito. Rode com --apply para executar."
else
  echo "Merge concluido. Rode a verificacao (verify_bkp_2607.sh) para conferir."
fi
