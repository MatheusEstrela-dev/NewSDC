#!/usr/bin/env python3
from __future__ import annotations

import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from _module import cli, collect_module, render_module  # noqa: E402


MODULE = {
    "name": "commands",
    "purpose": "comandos e prompts reutilizaveis para iniciar fluxos de trabalho de LLM",
    "read_order": ["task.md", "*.md"],
    "commands": ["python .claude/commands/main.py"],
    "triggers": ["plan", "plano", "task", "comando", "command", "retomar", "continuar"],
    "actions": [
        {
            "slug": "summary",
            "description": "Lista os comandos/prompts e os planos salvos.",
            "command": "python .claude/commands/main.py",
            "when": "antes de iniciar uma tarefa nova para reaproveitar prompts.",
        },
        {
            "slug": "list-plans",
            "description": "Lista os planos de implementacao em commands/plans/.",
            "command": "ls .claude/commands/plans/",
            "when": "para retomar trabalho de uma feature ja planejada.",
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
