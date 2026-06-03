#!/usr/bin/env python3
"""
Utilitarios compartilhados dos modulos de inicializacao da pasta .claude.

Cada subpasta de .claude pode expor um main.py com este contrato:
    MODULE = {"name": "...", "purpose": "..."}
    collect(repo_root, app_root, claude_root) -> dict
    render(data) -> str
    main() -> int
"""

from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any


IGNORED_DIRS = {"__pycache__", ".git", "node_modules", "vendor"}
TEXT_EXTENSIONS = {".md", ".txt", ".json", ".yml", ".yaml", ".py", ".php", ".vue", ".js", ".ts"}


def ascii_text(value: str) -> str:
    return value.encode("ascii", errors="ignore").decode("ascii").strip()


def read_json(path: Path) -> dict[str, Any]:
    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except Exception:
        return {}


def count_files(path: Path, limit: int = 500) -> tuple[int, bool]:
    if not path.exists():
        return 0, False
    total = 0
    for item in path.rglob("*"):
        if any(part in IGNORED_DIRS for part in item.parts):
            continue
        if item.is_file():
            total += 1
            if total >= limit:
                return total, True
    return total, False


def list_dir_names(path: Path, limit: int = 40) -> list[str]:
    if not path.exists():
        return []
    names = sorted(
        item.name
        for item in path.iterdir()
        if item.is_dir() and item.name not in IGNORED_DIRS
    )
    return names[:limit]


def list_relative_files(path: Path, root: Path, pattern: str = "*", limit: int = 40) -> list[str]:
    if not path.exists():
        return []
    files = sorted(
        item.relative_to(root).as_posix()
        for item in path.glob(pattern)
        if item.is_file()
    )
    return files[:limit]


def relative(path: Path, root: Path) -> str:
    try:
        return path.relative_to(root).as_posix()
    except ValueError:
        return path.as_posix()


def first_heading(path: Path) -> str:
    if path.suffix.lower() not in {".md", ".txt"}:
        return ""
    try:
        for line in path.read_text(encoding="utf-8", errors="ignore").splitlines():
            stripped = line.strip()
            if stripped.startswith("#"):
                return ascii_text(stripped.lstrip("#").strip())
    except OSError:
        return ""
    return ""


def list_context_files(module_dir: Path, repo_root: Path, recursive: bool = True, limit: int = 30) -> list[dict[str, str]]:
    iterator = module_dir.rglob("*") if recursive else module_dir.glob("*")
    files: list[dict[str, str]] = []
    for item in sorted(iterator):
        if any(part in IGNORED_DIRS for part in item.parts):
            continue
        if item.name == "main.py":
            continue
        if not item.is_file() or item.suffix.lower() not in TEXT_EXTENSIONS:
            continue

        files.append(
            {
                "path": relative(item, repo_root),
                "name": item.name,
                "heading": first_heading(item),
            }
        )
        if len(files) >= limit:
            break
    return files


def collect_module(
    module_dir: Path,
    repo_root: Path,
    module: dict[str, Any],
    recursive: bool = True,
    file_limit: int = 30,
) -> dict[str, Any]:
    file_count, truncated = count_files(module_dir)
    subdirs = sorted(
        item.name
        for item in module_dir.iterdir()
        if item.is_dir() and item.name not in IGNORED_DIRS
    )

    return {
        "name": module["name"],
        "purpose": module["purpose"],
        "path": relative(module_dir, repo_root),
        "mode": module.get("mode", "unix-init-module"),
        "read_order": module.get("read_order", []),
        "commands": module.get("commands", []),
        "actions": module.get("actions", []),
        "triggers": module.get("triggers", []),
        "file_count": file_count,
        "file_count_truncated": truncated,
        "subdirs": subdirs,
        "files": list_context_files(module_dir, repo_root, recursive=recursive, limit=file_limit),
    }


def render_module(data: dict[str, Any]) -> str:
    suffix = "+" if data.get("file_count_truncated") else ""
    lines = [
        f"## {data['name']}",
        f"- Caminho: `{data['path']}`",
        f"- Papel: {data['purpose']}",
        f"- Modo: {data.get('mode', 'unix-init-module')}",
        f"- Arquivos: {data.get('file_count', 0)}{suffix}",
    ]
    if data.get("subdirs"):
        lines.append(f"- Subpastas: {', '.join(data['subdirs'])}")
    if data.get("read_order"):
        lines.append(f"- Ordem de leitura: {', '.join(f'`{item}`' for item in data['read_order'])}")
    if data.get("commands"):
        lines.append(f"- Comandos: {', '.join(f'`{item}`' for item in data['commands'])}")
    if data.get("files"):
        lines.append("- Arquivos de contexto:")
        for file in data["files"]:
            label = f"`{file['path']}`"
            if file.get("heading"):
                label += f" ({file['heading']})"
            lines.append(f"  - {label}")
    return "\n".join(lines)


def default_roots(module_dir: Path) -> tuple[Path, Path, Path]:
    claude_root = module_dir.parent
    repo_root = claude_root.parent
    app_root = repo_root / "SDC"
    return repo_root, app_root, claude_root


def cli(module: dict[str, Any], module_dir: Path, recursive: bool = True) -> int:
    parser = argparse.ArgumentParser(description=module["purpose"])
    parser.add_argument("--json", action="store_true", help="Emite o contexto do modulo em JSON.")
    args = parser.parse_args()

    repo_root, app_root, claude_root = default_roots(module_dir)
    _ = app_root, claude_root
    data = collect_module(module_dir, repo_root, module, recursive=recursive)
    if args.json:
        print(json.dumps(data, ensure_ascii=False, indent=2))
    else:
        print(render_module(data))
    return 0
