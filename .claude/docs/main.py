#!/usr/bin/env python3
from __future__ import annotations

import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from _module import cli, collect_module, render_module  # noqa: E402


MODULE = {
    "name": "docs",
    "purpose": "documentacao estavel de longo prazo (arquitetura, exemplos, referencia)",
    "read_order": ["README.md", "ARCHITECTURE.md", "*.md"],
    "commands": ["python .claude/docs/main.py"],
    "triggers": ["arquitetura", "architecture", "stack", "modulo", "decretacao", "decreto", "credencial", "credenciais", "documentar"],
    "actions": [
        {
            "slug": "summary",
            "description": "Lista os documentos estaveis disponiveis.",
            "command": "python .claude/docs/main.py",
            "when": "antes de qualquer decisao arquitetural ou de modelagem.",
        },
        {
            "slug": "architecture",
            "description": "Abre o documento mestre de arquitetura.",
            "command": "cat .claude/docs/ARCHITECTURE.md",
            "when": "para entender stack, modulos, deploy ou padroes globais.",
        },
        {
            "slug": "credentials",
            "description": "Mostra onde estao os segredos e como rotacionar.",
            "command": "cat .claude/docs/CREDENCIAIS.md",
            "when": "ao integrar com Azure, Postgres prod, Redis, SMTP, Gemini ou Notion.",
        },
    ],
}


def collect(repo_root: Path, app_root: Path, claude_root: Path) -> dict:
    _ = app_root, claude_root
    return collect_module(Path(__file__).resolve().parent, repo_root, MODULE)


def render(data: dict) -> str:
    return render_module(data)


def main() -> int:
    return cli(MODULE, Path(__file__).resolve().parent)


if __name__ == "__main__":
    raise SystemExit(main())
