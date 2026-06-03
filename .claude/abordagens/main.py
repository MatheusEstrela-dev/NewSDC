#!/usr/bin/env python3
from __future__ import annotations

import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from _module import cli, collect_module, render_module  # noqa: E402


MODULE = {
    "name": "abordagens",
    "purpose": "estrategias, padroes de abordagem e notas operacionais para conduzir tarefas",
    "read_order": ["*.md", "*.txt"],
    "commands": ["python .claude/abordagens/main.py"],
    "triggers": ["abordagem", "estrategia", "como atacar", "passo a passo"],
    "actions": [
        {
            "slug": "summary",
            "description": "Lista as estrategias e abordagens registradas.",
            "command": "python .claude/abordagens/main.py",
            "when": "antes de decidir como atacar uma tarefa nao trivial.",
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
