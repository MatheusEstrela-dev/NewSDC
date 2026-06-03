#!/usr/bin/env python3
"""
Helper para registrar fatos volateis em `.claude/memory/`.

Uso:
    python .claude/memory/append.py "REDIS_PREFIX vazio causa keyspace duplicada" --type gotcha
    python .claude/memory/append.py "Decisao: usar Vue+Atomic para emails" --type decision
    python .claude/memory/append.py "Producao e sdcdefesa, nao newsdc2027" --type fact

Grava em `.claude/memory/YYYY-MM-DD-<slug>.md` com cabecalho ISO, branch, tipo.
"""

from __future__ import annotations

import argparse
import re
import subprocess
from datetime import datetime
from pathlib import Path


MEMORY_DIR = Path(__file__).resolve().parent
REPO_ROOT = MEMORY_DIR.parent.parent


VALID_TYPES = ["gotcha", "decision", "fact", "postmortem"]


def slugify(text: str) -> str:
    text = text.lower().strip()
    text = re.sub(r"[^a-z0-9\s-]", "", text)
    text = re.sub(r"\s+", "-", text)
    return text[:60] or "memoria"


def current_branch() -> str:
    try:
        result = subprocess.run(
            ["git", "branch", "--show-current"],
            cwd=REPO_ROOT,
            text=True,
            capture_output=True,
            timeout=3,
            check=False,
        )
        return result.stdout.strip() or "?"
    except Exception:
        return "?"


def write_entry(fact: str, memory_type: str, slug_override: str | None) -> Path:
    now = datetime.now()
    date_iso = now.strftime("%Y-%m-%d")
    time_iso = now.strftime("%H:%M:%S")
    slug = slug_override or slugify(fact.splitlines()[0])
    target = MEMORY_DIR / f"{date_iso}-{slug}.md"

    counter = 1
    base_target = target
    while target.exists():
        target = base_target.with_stem(f"{base_target.stem}-{counter}")
        counter += 1

    content = (
        f"# {memory_type.title()} ({date_iso} {time_iso})\n\n"
        f"- Tipo: {memory_type}\n"
        f"- Branch: `{current_branch()}`\n"
        f"- Registrado por: `.claude/memory/append.py`\n\n"
        f"{fact.rstrip()}\n"
    )
    target.write_text(content, encoding="utf-8")
    return target


def main() -> int:
    parser = argparse.ArgumentParser(description="Registra um fato em .claude/memory/.")
    parser.add_argument("fact", help="Fato/decisao/gotcha a registrar (texto livre, primeira linha vira titulo).")
    parser.add_argument(
        "--type",
        dest="memory_type",
        default="fact",
        choices=VALID_TYPES,
        help="Tipo: gotcha (armadilha), decision (decisao), fact (fato), postmortem.",
    )
    parser.add_argument("--slug", help="Slug curto para o nome do arquivo (override automatico).")
    args = parser.parse_args()

    if not args.fact.strip():
        return 1

    target = write_entry(args.fact, args.memory_type, args.slug)
    print(target.as_posix())
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
