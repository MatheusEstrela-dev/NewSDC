#!/usr/bin/env python3
from __future__ import annotations

import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from _module import cli, collect_module, render_module  # noqa: E402


MODULE = {
    "name": "error",
    "purpose": "erros capturados, logs curados e casos de falha para diagnostico rapido",
    "read_order": ["latest.md", "*.log", "*.md", "*.txt"],
    "commands": ["python .claude/error/main.py"],
    "triggers": ["erro", "error", "bug", "falha", "exception", "stacktrace", "regressao", "regression"],
    "actions": [
        {
            "slug": "summary",
            "description": "Lista incidentes registrados e logs curados.",
            "command": "python .claude/error/main.py",
            "when": "ao depurar uma falha ou regressao para checar precedentes.",
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
