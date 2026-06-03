#!/usr/bin/env python3
"""
Helper para registrar erros/incidentes da sessao em `.claude/error/`.

Uso:
    python .claude/error/log.py "Mensagem do erro" --tag deploy-falhou
    python .claude/error/log.py - --tag build < error_output.log
    python .claude/error/log.py "Migration X falhou em prod" --tag migration --severity high

Grava em `.claude/error/YYYY-MM-DD-<tag>.md` com cabecalho ISO, branch, severidade.
"""

from __future__ import annotations

import argparse
import re
import subprocess
import sys
from datetime import datetime
from pathlib import Path


ERROR_DIR = Path(__file__).resolve().parent
REPO_ROOT = ERROR_DIR.parent.parent


def slugify(text: str) -> str:
    text = text.lower().strip()
    text = re.sub(r"[^a-z0-9\s-]", "", text)
    text = re.sub(r"\s+", "-", text)
    return text[:60] or "incidente"


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


def write_entry(message: str, tag: str | None, severity: str) -> Path:
    now = datetime.now()
    date_iso = now.strftime("%Y-%m-%d")
    time_iso = now.strftime("%H:%M:%S")
    slug = slugify(tag or message.splitlines()[0] if message else "incidente")
    target = ERROR_DIR / f"{date_iso}-{slug}.md"

    counter = 1
    base_target = target
    while target.exists():
        target = base_target.with_stem(f"{base_target.stem}-{counter}")
        counter += 1

    content = (
        f"# {tag or 'Incidente'} ({date_iso} {time_iso})\n\n"
        f"- Branch: `{current_branch()}`\n"
        f"- Severidade: {severity}\n"
        f"- Registrado por: `.claude/error/log.py`\n\n"
        f"## Mensagem\n\n"
        f"```\n{message.rstrip()}\n```\n"
    )
    target.write_text(content, encoding="utf-8")
    return target


def main() -> int:
    parser = argparse.ArgumentParser(description="Registra um erro em .claude/error/.")
    parser.add_argument("message", help="Mensagem (ou '-' para ler do stdin).")
    parser.add_argument("--tag", help="Tag curta para o nome do arquivo (ex.: deploy-falhou).")
    parser.add_argument(
        "--severity",
        default="medium",
        choices=["low", "medium", "high", "critical"],
        help="Severidade percebida.",
    )
    args = parser.parse_args()

    if args.message == "-":
        body = sys.stdin.read()
    else:
        body = args.message

    if not body.strip():
        print("Mensagem vazia, nada gravado.", file=sys.stderr)
        return 1

    target = write_entry(body, args.tag, args.severity)
    print(target.as_posix())
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
