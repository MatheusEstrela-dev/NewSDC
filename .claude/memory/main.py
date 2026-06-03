#!/usr/bin/env python3
from __future__ import annotations

import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from _module import cli, collect_module, render_module  # noqa: E402


MODULE = {
    "name": "memory",
    "purpose": "fatos volateis, decisoes datadas, armadilhas e gotchas entre sessoes",
    "read_order": ["README.md", "*.md", "*.json"],
    "commands": ["python .claude/memory/main.py"],
    "triggers": ["memoria", "memory", "armadilha", "gotcha", "decisao", "registro", "lembrar"],
    "actions": [
        {
            "slug": "summary",
            "description": "Lista armadilhas, decisoes datadas e gotchas do projeto.",
            "command": "python .claude/memory/main.py",
            "when": "antes de mexer em area com historico de armadilhas (Redis, email, deploy, prod).",
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
