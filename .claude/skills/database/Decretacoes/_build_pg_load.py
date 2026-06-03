#!/usr/bin/env python3
"""
Conversor dos dumps MySQL legados (HeidiSQL / servidor 200.198.29.227, db dbsdc)
para uma carga PostgreSQL unica e transacional do dominio Decretacoes.

A base legada rodava com FOREIGN_KEY_CHECKS=0, entao ha linhas-filhas orfas
(FK apontando para pais inexistentes). Como o schema PostgreSQL ENFORCA as FKs,
a carga usa estrategia de STAGING:

  1. BEGIN
  2. cria tabelas temporarias stg_<t> sem constraints (LIKE ... ) ON COMMIT DROP
  3. carrega TODOS os dumps no staging (sem risco de erro de FK)
  4. TRUNCATE das tabelas reais (RESTART IDENTITY CASCADE)
  5. INSERT ... SELECT do staging para as reais, em ordem de FK, FILTRANDO
     linhas-filhas cujo pai nao existe (anti-join). Orfas legadas sao descartadas.
  6. setval das sequences (IDs explicitos foram inseridos)
  7. relatorio staged-vs-loaded por tabela (quantas orfas foram descartadas)
  8. COMMIT

Sem dependencias externas (stdlib). Nao toca em banco: apenas gera o .sql.
"""

from __future__ import annotations

import re
from pathlib import Path

BASE = Path(__file__).resolve().parent
OUT = BASE / "_load_decretacoes.pg.sql"

# (arquivo origem, tabela alvo) em ORDEM DE CARGA (pais antes de filhos).
LOAD_ORDER = [
    ("dec_categoria.sql", "dec_decreto_categorias"),
    ("dec_desastre_grupo.sql", "dec_desastre_grupos"),
    ("dec_desastre_categoria.sql", "dec_desastre_categorias"),
    ("dec_desastre_items.sql", "dec_desastre_items"),
    ("dec_desastre_items_campos.sql", "dec_desastre_item_campos"),
    ("dec_entrada_processos.sql", "dec_entrada_processos"),
    ("dec_entrada_decretos.sql", "dec_entrada_decretos"),
    ("dec_municipio.sql", "dec_decreto_municipios"),
    ("dec_entrada_categoria_desastres.sql", "dec_entrada_categoria_desastres"),
    ("dec_entrada_desastres.sql", "dec_entrada_desastres"),
]

# Colunas conhecidas para arquivos com header de INSERT corrompido (tuplas soltas).
FALLBACK_COLUMNS = {
    "dec_decreto_categorias": "(id, nome, descricao, created_at, updated_at)",
}

# Filtros anti-orfao: por tabela, condicoes WHERE que garantem que o pai existe
# na tabela REAL ja carregada. Espelha as FKs enforcadas no schema PostgreSQL
# (todas NOT NULL, confirmado em pg_constraint).
FK_FILTERS = {
    "dec_desastre_items": [
        "categoria_id IN (SELECT id FROM dec_desastre_categorias)",
    ],
    "dec_desastre_item_campos": [
        "desastre_item_id IN (SELECT id FROM dec_desastre_items)",
    ],
    "dec_entrada_decretos": [
        "entrada_processos_id IN (SELECT id FROM dec_entrada_processos)",
        "decreto_categoria_id IN (SELECT id FROM dec_decreto_categorias)",
    ],
    "dec_decreto_municipios": [
        "entrada_processos_id IN (SELECT id FROM dec_entrada_processos)",
    ],
    "dec_entrada_categoria_desastres": [
        "categoria_id IN (SELECT id FROM dec_desastre_categorias)",
        "entrada_processo_id IN (SELECT id FROM dec_entrada_processos)",
    ],
    "dec_entrada_desastres": [
        "entrada_processo_id IN (SELECT id FROM dec_entrada_processos)",
        "entrada_categoria_desastre_id IN (SELECT id FROM dec_entrada_categoria_desastres)",
        "item_id IN (SELECT id FROM dec_desastre_items)",
        "item_campo_id IN (SELECT id FROM dec_desastre_item_campos)",
    ],
}

COLS_RE = re.compile(r"^INSERT INTO \S+ \((.*?)\) VALUES", re.DOTALL)
PREFIX_RE = re.compile(r"^INSERT INTO \S+ \(.*?\) VALUES\s*", re.DOTALL)

# Linhas por comando INSERT na carga (multi-row). Reduz round-trips ~BATCH vezes.
BATCH = 500

# Cria processos-pai ausentes no legado (referenciados por filhos mas inexistentes
# no dump). Calculado dinamicamente das tabelas-filhas em staging. Stubs ficam
# soft-deleted (deleted_at) e created_by='legado-stub' para nao aparecerem como
# processos ativos. created_by e NOT NULL; demais colunas ficam NULL.
STUB_PROCESSOS_SQL = """-- (4b) Stubs de processos-pai ausentes no legado (preserva filhos orfaos).
INSERT INTO dec_entrada_processos (id, created_by, observacoes, deleted_at)
    SELECT DISTINCT refs.pid, 'legado-stub',
           'Stub carga legada: processo-pai ausente no dump legado; mantido para preservar filhos orfaos.',
           TIMESTAMP '2026-06-03 00:00:00'
    FROM (
        SELECT entrada_processos_id AS pid FROM stg_dec_entrada_decretos
        UNION SELECT entrada_processos_id FROM stg_dec_decreto_municipios
        UNION SELECT entrada_processo_id  FROM stg_dec_entrada_categoria_desastres
        UNION SELECT entrada_processo_id  FROM stg_dec_entrada_desastres
    ) refs
    WHERE refs.pid NOT IN (SELECT id FROM dec_entrada_processos);"""


def strip_mysql_escapes(text: str) -> str:
    """Converte escapes de string MySQL para o dialeto padrao do PostgreSQL.

    PG (standard_conforming_strings=on) nao interpreta barra invertida; logo
    \\' (apostrofo escapado no MySQL) precisa virar '' e \\\\ vira \\.
    A ordem importa: trata \\\\ antes de \\'.
    """
    text = text.replace("\\\\", "\\")
    text = text.replace("\\'", "''")
    text = text.replace('\\"', '"')
    return text


def extract_inserts(path: Path, table: str) -> tuple[str, list[str]]:
    """Extrai (lista_de_colunas, [statements INSERT]) de um dump, convertidos p/ PG.

    Mantem do 'INSERT INTO' ate a linha terminada em ';'. Para dumps com header
    corrompido (tuplas soltas, caso dec_categoria), reconstroi o header.
    Retorna a string de colunas (com parenteses) e os statements ja com 'stg_'.
    """
    raw = path.read_text(encoding="utf-8", errors="replace")
    raw = raw.replace("\r\n", "\n").replace("\r", "\n")

    statements: list[str] = []
    buf: list[str] = []
    in_stmt = False

    for line in raw.split("\n"):
        stripped = line.strip()
        if not in_stmt:
            if stripped.startswith("INSERT INTO"):
                in_stmt = True
                buf = [line]
                if stripped.endswith(";"):
                    statements.append("\n".join(buf))
                    in_stmt = False
                continue
            if (
                table in FALLBACK_COLUMNS
                and stripped.startswith("(")
                and stripped.endswith(";")
            ):
                header = f"INSERT INTO {table} {FALLBACK_COLUMNS[table]} VALUES"
                statements.append(header + "\n" + line)
                continue
            continue
        buf.append(line)
        if stripped.endswith(";"):
            statements.append("\n".join(buf))
            in_stmt = False

    columns = ""
    tuples: list[str] = []
    for stmt in statements:
        stmt = stmt.replace("`", "")
        stmt = strip_mysql_escapes(stmt).strip()
        if not columns:
            m = COLS_RE.match(stmt)
            if m:
                columns = "(" + m.group(1).strip() + ")"
        # Extrai apenas a tupla de VALUES (sem prefixo e sem ';' final).
        tup = PREFIX_RE.sub("", stmt, count=1).rstrip()
        if tup.endswith(";"):
            tup = tup[:-1].rstrip()
        tuples.append(tup)

    if not columns and table in FALLBACK_COLUMNS:
        columns = FALLBACK_COLUMNS[table]
    return columns, tuples


def main() -> int:
    truncate_order = [t for _, t in reversed(LOAD_ORDER)]
    parsed: list[tuple[str, str, list[str]]] = []  # (table, columns, statements)
    for filename, table in LOAD_ORDER:
        path = BASE / filename
        if not path.exists():
            raise SystemExit(f"Arquivo ausente: {path}")
        columns, inserts = extract_inserts(path, table)
        if not columns:
            raise SystemExit(f"Nao identifiquei colunas para {table} ({filename}).")
        parsed.append((table, columns, inserts))

    p: list[str] = []
    p.append("-- Carga legada Decretacoes (MySQL dbsdc -> PostgreSQL sdc).")
    p.append("-- Gerado por _build_pg_load.py. NAO editar a mao.")
    p.append("-- Estrategia: STAGING + filtro anti-orfao + TRUNCATE/reload, transacional (ACID).")
    p.append("")
    p.append("\\set ON_ERROR_STOP on")
    p.append("BEGIN;")
    p.append("SET client_encoding TO 'UTF8';")
    p.append("")

    p.append("-- (1) Tabelas de staging sem constraints (FK desligada por construcao).")
    for table, _, _ in parsed:
        p.append(f"CREATE TEMP TABLE stg_{table} (LIKE {table}) ON COMMIT DROP;")
    p.append("")

    p.append(f"-- (2) Carga bruta dos dumps no staging (INSERTs em lote de {BATCH}).")
    for table, columns, tuples in parsed:
        p.append(f"-- staging {table}: {len(tuples)} linha(s)")
        for i in range(0, len(tuples), BATCH):
            chunk = tuples[i:i + BATCH]
            p.append(f"INSERT INTO stg_{table} {columns} VALUES")
            p.append(",\n".join(chunk) + ";")
        p.append("")

    p.append("-- (3) Limpa as tabelas reais.")
    p.append("TRUNCATE TABLE")
    p.append("    " + ",\n    ".join(truncate_order))
    p.append("    RESTART IDENTITY CASCADE;")
    p.append("")

    p.append("-- (4) Carga filtrada staging -> real, em ordem de FK.")
    for table, columns, _ in parsed:
        filters = FK_FILTERS.get(table)
        if filters:
            where = "\n      AND ".join(filters)
            p.append(
                f"INSERT INTO {table} {columns}\n"
                f"    SELECT {columns[1:-1]} FROM stg_{table}\n"
                f"    WHERE {where};"
            )
        else:
            p.append(
                f"INSERT INTO {table} {columns}\n"
                f"    SELECT {columns[1:-1]} FROM stg_{table};"
            )

        # Logo apos os processos reais, cria os processos-pai ausentes no legado
        # (referenciados por filhos mas inexistentes no dump). Mantem as filhas
        # orfas via FK valida; stubs ficam soft-deleted e ocultos.
        if table == "dec_entrada_processos":
            p.append(STUB_PROCESSOS_SQL)
    p.append("")

    p.append("-- (5) Reposiciona sequences (IDs explicitos foram inseridos).")
    for table, _, _ in parsed:
        p.append(
            f"SELECT setval(pg_get_serial_sequence('{table}', 'id'), "
            f"GREATEST((SELECT COALESCE(MAX(id), 1) FROM {table}), 1));"
        )
    p.append("")

    p.append("-- (6) Relatorio: linhas no staging vs carregadas (diferenca = orfas descartadas).")
    union = "\nUNION ALL ".join(
        f"SELECT '{table}' AS tabela, "
        f"(SELECT count(*) FROM stg_{table}) AS staged, "
        f"(SELECT count(*) FROM {table}) AS carregado"
        for table, _, _ in parsed
    )
    p.append(f"{union}\nORDER BY tabela;")
    p.append("")
    p.append("COMMIT;")
    p.append("")

    OUT.write_text("\n".join(p), encoding="utf-8")
    print(f"Gerado: {OUT}")
    for table, _, inserts in parsed:
        print(f"  {table}: {len(inserts)} linha(s) no dump")
    print(f"  TOTAL: {sum(len(i) for _, _, i in parsed)} linha(s)")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
