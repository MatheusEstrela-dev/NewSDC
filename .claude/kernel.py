#!/usr/bin/env python3
"""
Kernel rapido de contexto para LLMs no repositorio NewSDC.

Uso:
    python .claude/kernel.py                 # briefing em Markdown
    python .claude/kernel.py --json          # briefing em JSON
    python .claude/kernel.py --actions       # catalogo unificado de acoes
    python .claude/kernel.py --module NOME   # apenas um modulo
    python .claude/kernel.py --agent-functions
    python .claude/kernel.py --agent-call schedule.route --prompt "..."
    python .claude/kernel.py --verify-subsystem
    python .claude/kernel.py --start-kernel
    python .claude/kernel.py --no-cache      # forca regeneracao
    python .claude/kernel.py --clear-cache   # remove o cache e sai

Objetivo:
    Emitir um briefing curto, atual e acionavel sobre o projeto para uma LLM
    comecar uma tarefa sem precisar redescobrir a estrutura basica do codigo.
"""

from __future__ import annotations

import argparse
import hashlib
import importlib.util
import json
import os
import shutil
import subprocess
import sys
import time
from datetime import datetime
from pathlib import Path
from types import ModuleType
from typing import Any, Iterable


KERNEL_DIR = Path(__file__).resolve().parent
REPO_ROOT = KERNEL_DIR.parent
APP_ROOT = REPO_ROOT / "SDC"
CLAUDE_ROOT = REPO_ROOT / ".claude"
CACHE_DIR = CLAUDE_ROOT / ".cache"
CACHE_FILE = CACHE_DIR / "context.json"
HASH_FILE = CACHE_DIR / "hash.txt"

sys.path.insert(0, str(CLAUDE_ROOT))
sys.path.insert(0, str(CLAUDE_ROOT / "agents"))
from _module import (  # noqa: E402
    IGNORED_DIRS,
    count_files,
    list_dir_names,
    list_relative_files,
    read_json,
)
from integrations import detect_integrations, render_integration  # noqa: E402
from CodeReviewAgent import CodeReviewAgent  # noqa: E402
from InfraAgent import InfraAgent  # noqa: E402
from routine_agent import RoutineAgent  # noqa: E402
from ScheduleAgent import ScheduleAgent  # noqa: E402
from _base import render_report  # noqa: E402


KEY_BACKEND_PACKAGES = (
    "laravel/framework",
    "inertiajs/inertia-laravel",
    "laravel/octane",
    "laravel/sanctum",
    "tightenco/ziggy",
    "spatie/laravel-medialibrary",
    "spatie/laravel-permission",
    "predis/predis",
)

KEY_FRONTEND_PACKAGES = (
    "vue",
    "@inertiajs/vue3",
    "vite",
    "tailwindcss",
    "ziggy-js",
    "@tanstack/vue-query",
    "leaflet",
    "@playwright/test",
)

FAST_READS = (
    "README.md",
    "ARQUITETURA_ATUAL.md",
    "GUIA_ARQUITETURA_NEWSDC.md",
    "PADRONIZACAO_ROTAS.md",
    "INICIAR_VITE.md",
    ".claude/commands/task.md",
    ".claude/*/main.py",
    ".claude/memory",
    ".claude/docs",
    ".claude/agents",
    ".claude/skills",
    "docs/superpowers/plans",
    "docs/superpowers/specs",
)

CLAUDE_DIR_HINTS = {
    "abordagens": "estrategias, padroes de abordagem e notas operacionais para conduzir tarefas",
    "agents": "perfis de agentes/subagentes e especializacoes de trabalho",
    "commands": "prompts e comandos reutilizaveis para iniciar fluxos de tarefa",
    "docs": "documentacao estavel de longo prazo (arquitetura, exemplos, referencia)",
    "error": "erros capturados, logs curados e casos de falha para diagnostico",
    "memory": "fatos volateis, decisoes datadas, armadilhas e gotchas entre sessoes",
    "skills": "habilidades especializadas (backend, frontend e fluxos) com instrucoes praticas",
    "worktrees": "worktrees auxiliares; use como referencia historica, nao como fonte principal de edicao",
}


def run_git(args: list[str]) -> str:
    try:
        result = subprocess.run(
            ["git", *args],
            cwd=REPO_ROOT,
            text=True,
            capture_output=True,
            timeout=5,
            check=False,
        )
        return result.stdout.strip()
    except Exception:
        return ""


def start_kernel(tick_seconds: int = 60) -> int:
    """Inicia o loop autonomo de orquestracao dos agentes locais."""
    print("Iniciando Kernel Autonomo...")

    registry = build_agent_registry()
    schedule_bot = registry["agents"]["schedule"]

    try:
        while True:
            report = schedule_bot.tick()
            print(render_report(report))
            print("", flush=True)
            time.sleep(tick_seconds)
    except KeyboardInterrupt:
        print("\nKernel desligado com sucesso.")
        return 0


def build_agent_registry() -> dict[str, Any]:
    """Registra agentes e funcoes chamaveis pelo kernel autonomo."""
    infra_bot = InfraAgent()
    code_bot = CodeReviewAgent()
    routine_bot = RoutineAgent()
    schedule_bot = ScheduleAgent(infra_bot, code_bot, routine_bot)

    return {
        "agents": {
            "infra": infra_bot,
            "code-review": code_bot,
            "routine": routine_bot,
            "schedule": schedule_bot,
        },
        "functions": {
            "infra.health": infra_bot.check_system_health,
            "infra.deploy": infra_bot.plan_deploy_checklist,
            "infra.diagnose": infra_bot.diagnose_failure,
            "code-review.plan": code_bot.plan_review,
            "code-review.changed": code_bot.review_changed_files,
            "code-review.validation": code_bot.validation_commands,
            "routine.morning": routine_bot.morning_briefing,
            "routine.closeout": routine_bot.closeout_checklist,
            "routine.handoff": routine_bot.task_handoff,
            "schedule.tick": schedule_bot.tick,
            "schedule.route": schedule_bot.route_task,
            "schedule.day": schedule_bot.plan_day,
        },
    }


def render_agent_function_registry(registry: dict[str, Any]) -> str:
    lines = ["# Registro de agentes do kernel", ""]
    lines.append("## Agentes")
    for name, agent in registry["agents"].items():
        lines.append(f"- `{name}`: `{agent.__class__.__name__}`")
    lines.append("")
    lines.append("## Funcoes")
    for name in sorted(registry["functions"]):
        method = registry["functions"][name]
        lines.append(f"- `{name}` -> `{method.__self__.__class__.__name__}.{method.__name__}`")
    return "\n".join(lines)


def call_agent_function(function_name: str, args: argparse.Namespace) -> Any:
    registry = build_agent_registry()
    if function_name not in registry["functions"]:
        available = ", ".join(sorted(registry["functions"]))
        raise KeyError(f"Funcao de agente nao registrada: {function_name}. Disponiveis: {available}")

    func = registry["functions"][function_name]
    if function_name == "infra.health":
        return func(args.focus)
    if function_name == "infra.diagnose":
        return func(args.symptom or args.prompt or "")
    if function_name in {"code-review.plan", "code-review.validation"}:
        return func(args.path or [])
    if function_name == "routine.handoff":
        return func(args.topic or args.prompt or "tarefa atual")
    if function_name == "schedule.route":
        return func(args.prompt or "")
    if function_name == "schedule.tick":
        at = None
        if args.at:
            hour, minute = args.at.split(":", 1)
            at = datetime.now().replace(hour=int(hour), minute=int(minute), second=0, microsecond=0)
        return func(at)
    return func()


def verify_claude_subsystem() -> dict[str, Any]:
    """Verifica se `.claude` esta operando como um subsistema modular."""
    checks: list[dict[str, Any]] = []

    modules = collect_tool_modules()
    bad_modules = [module for module in modules if module.get("status") != "ok"]
    checks.append({
        "name": "modules.load",
        "ok": not bad_modules,
        "details": f"{len(modules)} modulo(s) carregado(s), {len(bad_modules)} erro(s).",
    })

    actions = []
    for module in modules:
        if module.get("status") == "ok":
            actions.extend(normalize_actions(module))
    checks.append({
        "name": "modules.actions",
        "ok": bool(actions),
        "details": f"{len(actions)} acao(oes) publicada(s) no catalogo.",
    })

    settings = read_json(CLAUDE_ROOT / "settings.local.json")
    hooks = settings.get("hooks", {})
    hook_commands = json.dumps(hooks, ensure_ascii=False)
    hooks_ok = (
        "UserPromptSubmit" in hooks
        and "Stop" in hooks
        and "on_user_prompt.py" in hook_commands
        and "on_stop.py" in hook_commands
    )
    checks.append({
        "name": "hooks.config",
        "ok": hooks_ok,
        "details": "UserPromptSubmit e Stop configurados." if hooks_ok else "hooks esperados nao encontrados em settings.local.json.",
    })

    registry = build_agent_registry()
    expected_agents = {"infra", "code-review", "routine", "schedule"}
    found_agents = set(registry["agents"])
    checks.append({
        "name": "agents.registry",
        "ok": expected_agents.issubset(found_agents),
        "details": f"agentes registrados: {', '.join(sorted(found_agents))}.",
    })

    expected_functions = {
        "infra.health",
        "infra.deploy",
        "infra.diagnose",
        "code-review.plan",
        "code-review.changed",
        "code-review.validation",
        "routine.morning",
        "routine.closeout",
        "routine.handoff",
        "schedule.tick",
        "schedule.route",
        "schedule.day",
    }
    found_functions = set(registry["functions"])
    missing_functions = sorted(expected_functions - found_functions)
    checks.append({
        "name": "agents.functions",
        "ok": not missing_functions,
        "details": "faltando: " + ", ".join(missing_functions) if missing_functions else "todas as funcoes esperadas registradas.",
    })

    schedule = registry["agents"]["schedule"]
    route_report = schedule.route_task("preciso revisar um bug em controller vue")
    checks.append({
        "name": "agents.routing",
        "ok": "code-review.plan" in " ".join(route_report.commands),
        "details": route_report.summary,
    })

    dispatch_report = registry["functions"]["schedule.route"]("diagnosticar fila redis no docker")
    checks.append({
        "name": "agents.dispatch",
        "ok": "infra.health" in " ".join(dispatch_report.commands),
        "details": dispatch_report.summary,
    })

    detect_report = detect_modules("orquestrar deploy docker com code review", collect_module_metas())
    checks.append({
        "name": "kernel.detect",
        "ok": any(module.get("name") == "agents" for module in detect_report),
        "details": f"{len(detect_report)} modulo(s) casado(s) por trigger.",
    })

    ok = all(check["ok"] for check in checks)
    return {
        "ok": ok,
        "summary": "subsistema .claude integrado" if ok else "subsistema .claude com falhas",
        "checks": checks,
    }


def render_subsystem_verification(result: dict[str, Any]) -> str:
    lines = [
        "# Verificacao do subsistema `.claude`",
        "",
        f"Status: {'OK' if result['ok'] else 'FALHA'}",
        f"Resumo: {result['summary']}",
        "",
    ]
    for check in result["checks"]:
        marker = "OK" if check["ok"] else "FALHA"
        lines.append(f"- {marker} `{check['name']}`: {check['details']}")
    return "\n".join(lines)


def claude_dir_map(limit_per_dir: int = 12) -> list[dict[str, Any]]:
    if not CLAUDE_ROOT.exists():
        return []

    mapped: list[dict[str, Any]] = []
    for directory in sorted(p for p in CLAUDE_ROOT.iterdir() if p.is_dir() and p.name not in IGNORED_DIRS):
        if directory.name == ".cache":
            continue
        file_count, file_count_truncated = count_files(directory)
        entry: dict[str, Any] = {
            "name": directory.name,
            "path": directory.relative_to(REPO_ROOT).as_posix(),
            "hint": CLAUDE_DIR_HINTS.get(directory.name, "contexto auxiliar do Claude"),
            "entrypoint": "",
            "subdirs": list_dir_names(directory, 20),
            "file_count": file_count,
            "file_count_truncated": file_count_truncated,
            "files": [],
        }
        main_file = directory / "main.py"
        if main_file.exists():
            entry["entrypoint"] = main_file.relative_to(REPO_ROOT).as_posix()

        if directory.name == "worktrees":
            entry["files"] = list_relative_files(directory, REPO_ROOT, "*", limit_per_dir)
            entry["worktrees"] = list_dir_names(directory, 20)
        else:
            files = sorted(
                p.relative_to(REPO_ROOT).as_posix()
                for p in directory.rglob("*")
                if p.is_file() and "__pycache__" not in p.parts
            )
            entry["files"] = files[:limit_per_dir]

        mapped.append(entry)

    return mapped


def claude_root_files(limit: int = 20) -> list[str]:
    if not CLAUDE_ROOT.exists():
        return []
    return sorted(
        p.relative_to(REPO_ROOT).as_posix()
        for p in CLAUDE_ROOT.iterdir()
        if p.is_file()
    )[:limit]


def pick_versions(packages: dict[str, str], keys: Iterable[str]) -> list[str]:
    return [f"{name}: {packages[name]}" for name in keys if name in packages]


def bullet(items: Iterable[str], fallback: str = "Nao encontrado.") -> str:
    values = [item for item in items if item]
    if not values:
        return f"- {fallback}"
    return "\n".join(f"- {item}" for item in values)


def render_claude_map(entries: list[dict[str, Any]]) -> str:
    if not entries:
        return "- `.claude/` nao encontrada."

    lines: list[str] = []
    for entry in entries:
        count_suffix = "+" if entry.get("file_count_truncated") else ""
        lines.append(f"- `{entry['path']}`: {entry['hint']} ({entry['file_count']}{count_suffix} arquivo(s)).")
        if entry.get("entrypoint"):
            lines.append(f"  Entrypoint: `{entry['entrypoint']}`.")
        if entry.get("subdirs"):
            lines.append(f"  Subpastas: {', '.join(entry['subdirs'])}.")
        if entry.get("worktrees"):
            lines.append(f"  Worktrees: {', '.join(entry['worktrees'])}.")
        if entry.get("files"):
            lines.append(f"  Arquivos-chave: {', '.join(f'`{file}`' for file in entry['files'])}.")

    return "\n".join(lines)


def module_entrypoints() -> list[Path]:
    if not CLAUDE_ROOT.exists():
        return []
    return sorted(
        directory / "main.py"
        for directory in CLAUDE_ROOT.iterdir()
        if directory.is_dir()
        and directory.name not in IGNORED_DIRS
        and directory.name != ".cache"
        and (directory / "main.py").exists()
    )


def load_tool_module(entrypoint: Path) -> ModuleType:
    safe_name = "".join(char if char.isalnum() or char == "_" else "_" for char in entrypoint.parent.name)
    module_name = f"claude_tool_{safe_name}"
    spec = importlib.util.spec_from_file_location(module_name, entrypoint)
    if spec is None or spec.loader is None:
        raise RuntimeError(f"Nao foi possivel carregar {entrypoint}")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


def collect_tool_modules(selected: str | None = None) -> list[dict[str, Any]]:
    modules: list[dict[str, Any]] = []
    for entrypoint in module_entrypoints():
        folder_name = entrypoint.parent.name
        try:
            module = load_tool_module(entrypoint)
            meta = getattr(module, "MODULE", {"name": folder_name, "purpose": ""})
            module_name = meta.get("name", folder_name)
            if selected and selected not in {folder_name, module_name}:
                continue
            if hasattr(module, "collect"):
                data = module.collect(REPO_ROOT, APP_ROOT, CLAUDE_ROOT)
            else:
                data = {"name": module_name, "purpose": meta.get("purpose", ""), "files": []}
            data["folder"] = folder_name
            data["entrypoint"] = entrypoint.relative_to(REPO_ROOT).as_posix()
            data["status"] = "ok"
            if not data.get("actions") and meta.get("actions"):
                data["actions"] = meta["actions"]
        except Exception as exc:
            if selected and selected != folder_name:
                continue
            data = {
                "name": folder_name,
                "folder": folder_name,
                "purpose": "falha ao carregar modulo",
                "entrypoint": entrypoint.relative_to(REPO_ROOT).as_posix(),
                "status": "error",
                "error": str(exc),
            }
        modules.append(data)
    return modules


def render_tool_modules(modules: list[dict[str, Any]]) -> str:
    if not modules:
        return "- Nenhum modulo `.claude/*/main.py` encontrado."

    lines: list[str] = []
    for module in modules:
        if module.get("status") != "ok":
            lines.append(f"- `{module['name']}`: erro em `{module['entrypoint']}`: {module.get('error', 'desconhecido')}")
            continue

        suffix = "+" if module.get("file_count_truncated") else ""
        lines.append(
            f"- `{module['name']}` via `{module['entrypoint']}`: {module.get('purpose', '')} "
            f"({module.get('file_count', 0)}{suffix} arquivo(s))."
        )
        if module.get("read_order"):
            lines.append(f"  Ordem: {', '.join(f'`{item}`' for item in module['read_order'])}.")
        if module.get("files"):
            selected = module["files"][:5]
            lines.append(f"  Contexto: {', '.join('`' + item['path'] + '`' for item in selected)}.")
        if module.get("worktrees"):
            selected_worktrees = [item["name"] for item in module["worktrees"][:5]]
            lines.append(f"  Worktrees: {', '.join(selected_worktrees)}.")
    return "\n".join(lines)


def collect_module_metas() -> list[dict[str, Any]]:
    """Versao leve de collect_tool_modules: le APENAS o dict MODULE de cada submodulo.

    Nao executa collect() nem lista arquivos. Usado por --detect para responder em
    tempo de hook (<300ms).
    """
    metas: list[dict[str, Any]] = []
    for entrypoint in module_entrypoints():
        folder_name = entrypoint.parent.name
        try:
            module = load_tool_module(entrypoint)
            meta = getattr(module, "MODULE", {"name": folder_name, "purpose": ""})
            metas.append({
                "name": meta.get("name", folder_name),
                "folder": folder_name,
                "path": entrypoint.parent.relative_to(REPO_ROOT).as_posix(),
                "purpose": meta.get("purpose", ""),
                "read_order": meta.get("read_order", []),
                "actions": meta.get("actions", []),
                "triggers": meta.get("triggers", []),
                "status": "ok",
            })
        except Exception as exc:
            metas.append({
                "name": folder_name,
                "folder": folder_name,
                "status": "error",
                "error": str(exc),
            })
    return metas


def detect_modules(prompt_text: str, modules: list[dict[str, Any]] | None = None) -> list[dict[str, Any]]:
    """Retorna os modulos cujos triggers casam com o texto do prompt.

    Match: case-insensitive, palavra inteira (word boundary). Trigger de uma
    palavra so casa se aparecer como termo no texto, nao substring.
    """
    import re

    if modules is None:
        modules = collect_module_metas()

    text = prompt_text.lower()
    matched: list[dict[str, Any]] = []
    for module in modules:
        if module.get("status") != "ok":
            continue
        triggers = module.get("triggers") or []
        if not triggers:
            continue
        hits: list[str] = []
        for trigger in triggers:
            pattern = re.compile(rf"(?<![a-z0-9_])\.?{re.escape(trigger.lower())}(?![a-z0-9_])")
            if pattern.search(text):
                hits.append(trigger)
        if hits:
            entry = dict(module)
            entry["matched_triggers"] = hits
            matched.append(entry)
    return matched


def render_detect(matched: list[dict[str, Any]], prompt_text: str, integrations: list[dict[str, Any]] | None = None) -> str:
    integrations = integrations or []
    if not matched and not integrations:
        return ""

    lines: list[str] = [
        "<context-from-claude-kernel>",
        "Gatilhos do `.claude/kernel.py --detect` casaram com este prompt.",
        "",
    ]
    if matched:
        lines.append("## Modulos relevantes")
        for module in matched:
            lines.append(f"### {module['name']} (`{module.get('path', '')}`)")
            lines.append(f"- Papel: {module.get('purpose', '')}")
            lines.append(f"- Gatilhos casados: {', '.join(module['matched_triggers'])}")
            if module.get("read_order"):
                lines.append(f"- Leia primeiro: {', '.join(f'`{item}`' for item in module['read_order'])}")
            actions = normalize_actions(module)
            if actions:
                lines.append("- Acoes:")
                for action in actions[:3]:
                    cmd = action.get("command", "")
                    desc = action.get("description", "")
                    lines.append(f"  - `{cmd}` - {desc}")
            if module.get("files"):
                top_files = [item["path"] for item in module["files"][:5]]
                lines.append(f"- Arquivos-chave: {', '.join(f'`{f}`' for f in top_files)}")
            lines.append("")

    if integrations:
        lines.append("## Integracoes externas")
        for integration in integrations:
            lines.append(render_integration(integration))
            lines.append("")

    lines.append("</context-from-claude-kernel>")
    return "\n".join(lines)


def run_skills_detect(prompt_text: str, as_json: bool = False) -> str:
    """Executa o roteador especializado de skills sem comprometer o hook."""
    entrypoint = CLAUDE_ROOT / "skills" / "main.py"
    if not entrypoint.exists():
        return ""

    command = [sys.executable, str(entrypoint), "--detect", prompt_text]
    if as_json:
        command.append("--json")
    try:
        result = subprocess.run(
            command,
            cwd=REPO_ROOT,
            text=True,
            capture_output=True,
            timeout=5,
            check=False,
        )
        return result.stdout.strip()
    except Exception:
        return ""


def normalize_actions(module: dict[str, Any]) -> list[dict[str, str]]:
    actions = module.get("actions") or []
    if actions:
        return actions
    commands = module.get("commands") or []
    if commands:
        return [{
            "slug": "default",
            "description": module.get("purpose", "").strip() or "executar modulo",
            "command": commands[0],
        }]
    return []


def render_actions_catalog(modules: list[dict[str, Any]]) -> str:
    if not modules:
        return "# Catalogo de acoes\n\n- Nenhum modulo encontrado."

    lines: list[str] = ["# Catalogo de acoes por modulo `.claude/`", ""]
    for module in modules:
        if module.get("status") != "ok":
            lines.append(f"## {module.get('name', '?')} (erro)")
            lines.append(f"- {module.get('error', 'falha ao carregar')}")
            lines.append("")
            continue

        actions = normalize_actions(module)
        lines.append(f"## {module['name']}")
        lines.append(f"- Pasta: `{module.get('path', '')}`")
        lines.append(f"- Papel: {module.get('purpose', '')}")
        if not actions:
            lines.append("- Sem acoes declaradas.")
            lines.append("")
            continue
        lines.append("- Acoes:")
        for action in actions:
            slug = action.get("slug", "?")
            desc = action.get("description", "").strip()
            cmd = action.get("command", "").strip()
            when = action.get("when", "").strip()
            lines.append(f"  - **{slug}** - {desc}")
            if cmd:
                lines.append(f"    `{cmd}`")
            if when:
                lines.append(f"    quando: {when}")
        lines.append("")
    return "\n".join(lines).rstrip() + "\n"


def git_summary() -> tuple[str, list[str]]:
    branch = run_git(["branch", "--show-current"]) or "desconhecida"
    status = run_git(["status", "--short"])
    changed = status.splitlines()
    return branch, changed[:25]


def collect_context() -> dict[str, Any]:
    composer = read_json(APP_ROOT / "composer.json")
    package = read_json(APP_ROOT / "package.json")
    require = composer.get("require", {})
    require_dev = composer.get("require-dev", {})
    deps = package.get("dependencies", {})
    dev_deps = package.get("devDependencies", {})
    scripts = package.get("scripts", {})
    branch, changed = git_summary()

    return {
        "generated_at": datetime.now().isoformat(timespec="seconds"),
        "repo_root": str(REPO_ROOT),
        "app_root": str(APP_ROOT),
        "claude_root": str(CLAUDE_ROOT),
        "branch": branch,
        "changed": changed,
        "backend_versions": pick_versions({**require, **require_dev}, KEY_BACKEND_PACKAGES),
        "frontend_versions": pick_versions({**deps, **dev_deps}, KEY_FRONTEND_PACKAGES),
        "npm_scripts": [f"{name}: {cmd}" for name, cmd in sorted(scripts.items())],
        "app_dirs": list_dir_names(APP_ROOT / "app"),
        "modules": list_dir_names(APP_ROOT / "app" / "Modules"),
        "js_dirs": list_dir_names(APP_ROOT / "resources" / "js"),
        "route_files": list_relative_files(APP_ROOT / "routes", REPO_ROOT, "*.php", 80)
        + list_relative_files(APP_ROOT / "routes" / "modules", REPO_ROOT, "*.php", 80),
        "claude_root_files": claude_root_files(),
        "claude_map": claude_dir_map(),
        "tool_modules": collect_tool_modules(),
        "recent_plans": list_relative_files(REPO_ROOT / "docs" / "superpowers" / "plans", REPO_ROOT, "*.md", 12),
        "recent_specs": list_relative_files(REPO_ROOT / "docs" / "superpowers" / "specs", REPO_ROOT, "*.md", 12),
    }


def render_markdown(ctx: dict[str, Any]) -> str:
    changed = ctx["changed"]
    dirty_line = (
        f"{len(changed)} arquivo(s) com alteracoes detectadas. Leia antes de tocar."
        if changed
        else "Sem alteracoes locais detectadas pelo git status."
    )

    return f"""# NewSDC LLM Context Kernel

Gerado em: {ctx["generated_at"]}
Branch: {ctx["branch"]}
Repo: `{ctx["repo_root"]}`
App principal: `{ctx["app_root"]}`
Contexto Claude: `{ctx["claude_root"]}`
Estado local: {dirty_line}

## Norte rapido
- O produto e o SDC, Sistema da Defesa Civil.
- O codigo executavel principal fica em `SDC/`; a raiz tem docs, scripts, binarios locais e materiais de arquitetura.
- Stack central: Laravel 12/PHP 8.3, Vue 3, Inertia, Vite, Tailwind, Ziggy, Octane/FrankenPHP, Redis e banco relacional.
- Ha docs antigas que citam MySQL e notas recentes que citam Postgres. Antes de trabalho de banco, confirme em `.env`, migrations e conexoes usadas.
- O backend tem organizacao modular em `SDC/app/Modules/<Modulo>` e rotas em `SDC/routes/modules/<modulo>.php`.
- O frontend fica em `SDC/resources/js`, com paginas Inertia em `Pages`, layouts em `Layouts`, componentes em `Components` e composables em `composables`.
- A pasta `.claude/` e um indice de contexto operacional: comandos, memoria, docs, agentes, skills, abordagens e worktrees auxiliares.
- Cada subpasta direta de `.claude/` pode funcionar como modulo UNIX de inicializacao quando tiver um `main.py`.
- Planos e specs de tarefas recentes ficam em `docs/superpowers/plans` e `docs/superpowers/specs`.

## Regras de trabalho para LLMs
- Sempre leia o codigo existente antes de editar. Use `rg` para localizar padroes, rotas, componentes e testes.
- Antes de tarefas complexas, consulte `.claude/commands`, `.claude/memory`, `.claude/docs` e `.claude/skills` para contexto local ja curado.
- Para inspecionar um modulo isolado, rode `python .claude/kernel.py --module nome` ou `python .claude/nome/main.py`.
- Para ver todas as acoes disponiveis por modulo, rode `python .claude/kernel.py --actions`.
- Use `.claude/worktrees` apenas como referencia de historico ou comparacao; edite o repo principal salvo instrucao explicita.
- Mantenha mudancas pequenas e alinhadas ao modulo afetado. Nao refatore areas fora da tarefa.
- Nao reverta alteracoes locais que voce nao criou. Se um arquivo estiver sujo, entenda o trecho antes de editar.
- Para novas rotas Inertia, cheque controller, rota nomeada, permissao/policy quando existir, pagina Vue e `ziggy.js`.
- Para uploads/anexos, procure uso de Spatie Media Library e servicos do modulo antes de criar logica nova.
- Para UI, siga os componentes e layouts existentes; evite criar padrao visual paralelo.
- Ao final, rode validacoes proporcionais: `php -l`, `php artisan test --filter=...`, `npm run build` ou teste e2e quando o risco justificar.

## Comandos comuns
Executar a partir de `SDC/`, salvo quando indicado.

```powershell
# Backend no container local mais usado neste repo
docker exec newsdc_frankenphp_local php artisan test
docker exec newsdc_frankenphp_local php artisan test --filter=NomeDoTeste
docker exec newsdc_frankenphp_local php -l /app/caminho/do/arquivo.php
docker exec newsdc_frankenphp_local php artisan route:list --name=parte.da.rota
docker exec newsdc_frankenphp_local tail -n 120 /app/storage/logs/laravel.log

# Frontend/build
npm run build
npm run dev
npm run test:e2e

# Busca local
rg -n "termo" SDC/app SDC/routes SDC/resources/js SDC/tests docs
```

## Pacotes backend relevantes
{bullet(ctx["backend_versions"])}

## Pacotes frontend relevantes
{bullet(ctx["frontend_versions"])}

## Scripts npm
{bullet(ctx["npm_scripts"])}

## Mapa do backend
Pastas em `SDC/app`:
{bullet(ctx["app_dirs"])}

Modulos em `SDC/app/Modules`:
{bullet(ctx["modules"])}

## Mapa do frontend
Pastas em `SDC/resources/js`:
{bullet(ctx["js_dirs"])}

## Rotas
Arquivos de rota:
{bullet(ctx["route_files"])}

## Mapa `.claude`
Estas pastas carregam contexto extra para orientar LLMs:
Arquivos na raiz: {", ".join(f"`{file}`" for file in ctx["claude_root_files"]) or "nenhum"}.

{render_claude_map(ctx["claude_map"])}

## Modulos UNIX `.claude`
Cada modulo exposto por `main.py` tem contrato `MODULE`, `collect()`, `render()` e `main()`.
{render_tool_modules(ctx["tool_modules"])}

## Docs de partida
Leia primeiro conforme a tarefa:
{bullet(FAST_READS)}

Planos recentes:
{bullet(ctx["recent_plans"])}

Specs recentes:
{bullet(ctx["recent_specs"])}

## Checklist antes de implementar
- Identifique o modulo/dominio exato da tarefa.
- Localize rota, controller, model, request/service, pagina Vue e testes relacionados.
- Confirme nomes de rotas e props Inertia antes de mexer no frontend.
- Preserve contratos existentes de API, permissao e validacao.
- Depois de editar, rode o menor conjunto de testes que prove a mudanca.
"""


def compute_inputs_hash() -> str:
    parts: list[str] = []
    parts.append(run_git(["rev-parse", "HEAD"]))
    parts.append(run_git(["status", "--short"]))

    tracked_files = [
        APP_ROOT / "composer.json",
        APP_ROOT / "composer.lock",
        APP_ROOT / "package.json",
        APP_ROOT / "package-lock.json",
        KERNEL_DIR / "kernel.py",
        KERNEL_DIR / "_module.py",
    ]
    for entrypoint in module_entrypoints():
        tracked_files.append(entrypoint)

    for path in tracked_files:
        try:
            mtime = path.stat().st_mtime_ns
        except OSError:
            mtime = 0
        parts.append(f"{path.as_posix()}:{mtime}")

    digest = hashlib.sha256("\n".join(parts).encode("utf-8")).hexdigest()
    return digest


def load_cached_context() -> dict[str, Any] | None:
    if not CACHE_FILE.exists() or not HASH_FILE.exists():
        return None
    try:
        stored_hash = HASH_FILE.read_text(encoding="utf-8").strip()
    except OSError:
        return None
    if stored_hash != compute_inputs_hash():
        return None
    try:
        return json.loads(CACHE_FILE.read_text(encoding="utf-8"))
    except Exception:
        return None


def save_cached_context(context: dict[str, Any]) -> None:
    try:
        CACHE_DIR.mkdir(parents=True, exist_ok=True)
        CACHE_FILE.write_text(json.dumps(context, ensure_ascii=False, indent=2), encoding="utf-8")
        HASH_FILE.write_text(compute_inputs_hash(), encoding="utf-8")
    except OSError:
        pass


def clear_cache() -> None:
    if CACHE_DIR.exists():
        shutil.rmtree(CACHE_DIR, ignore_errors=True)


def get_context(use_cache: bool = True) -> dict[str, Any]:
    if use_cache:
        cached = load_cached_context()
        if cached is not None:
            return cached
    context = collect_context()
    if use_cache:
        save_cached_context(context)
    return context


def main() -> int:
    parser = argparse.ArgumentParser(description="Gera contexto rapido do projeto NewSDC para LLMs.")
    parser.add_argument("--json", action="store_true", help="Emite o contexto bruto em JSON.")
    parser.add_argument("--list-modules", action="store_true", help="Lista modulos .claude disponiveis.")
    parser.add_argument("--module", help="Executa/coleta apenas um modulo .claude pelo nome da pasta.")
    parser.add_argument("--actions", action="store_true", help="Lista o catalogo de acoes por modulo.")
    parser.add_argument("--detect", help="Texto do prompt; emite contexto dos modulos casados (uso em hook UserPromptSubmit).")
    parser.add_argument("--start-kernel", action="store_true", help="Inicia o loop autonomo dos agentes de orquestracao.")
    parser.add_argument("--agent-functions", action="store_true", help="Lista agentes e funcoes registrados no kernel autonomo.")
    parser.add_argument("--agent-call", help="Executa uma funcao registrada, ex.: schedule.route, infra.health.")
    parser.add_argument("--prompt", default="", help="Texto usado por funcoes de roteamento/diagnostico.")
    parser.add_argument("--path", action="append", default=[], help="Caminho afetado para funcoes de revisao/validacao.")
    parser.add_argument("--symptom", default="", help="Sintoma para diagnostico de infraestrutura.")
    parser.add_argument("--focus", default="local", choices=["local", "prod", "azure"], help="Foco de infra.health.")
    parser.add_argument("--topic", default="", help="Topico para routine.handoff.")
    parser.add_argument("--at", help="Horario HH:MM para schedule.tick.")
    parser.add_argument("--verify-subsystem", action="store_true", help="Verifica integracao modular de `.claude` e agentes.")
    parser.add_argument("--tick-seconds", type=int, default=60, help="Intervalo do loop autonomo em segundos.")
    parser.add_argument("--no-cache", action="store_true", help="Ignora o cache e regenera o contexto.")
    parser.add_argument("--clear-cache", action="store_true", help="Apaga o cache e sai.")
    args = parser.parse_args()

    if args.start_kernel:
        return start_kernel(tick_seconds=max(1, args.tick_seconds))

    if args.agent_functions:
        registry = build_agent_registry()
        if args.json:
            payload = {
                "agents": {name: agent.__class__.__name__ for name, agent in registry["agents"].items()},
                "functions": sorted(registry["functions"]),
            }
            print(json.dumps(payload, ensure_ascii=False, indent=2))
        else:
            print(render_agent_function_registry(registry))
        return 0

    if args.agent_call:
        try:
            report = call_agent_function(args.agent_call, args)
        except KeyError as exc:
            print(str(exc))
            return 1
        if args.json and hasattr(report, "to_dict"):
            print(json.dumps(report.to_dict(), ensure_ascii=False, indent=2))
        else:
            print(render_report(report) if hasattr(report, "to_dict") else report)
        return 0

    if args.verify_subsystem:
        result = verify_claude_subsystem()
        if args.json:
            print(json.dumps(result, ensure_ascii=False, indent=2))
        else:
            print(render_subsystem_verification(result))
        return 0 if result["ok"] else 1

    if args.clear_cache:
        clear_cache()
        print("Cache removido.")
        return 0

    if args.list_modules:
        modules = collect_tool_modules()
        if args.json:
            print(json.dumps(modules, ensure_ascii=False, indent=2))
        else:
            print(render_tool_modules(modules))
        return 0

    if args.detect is not None:
        modules = collect_module_metas()
        matched = detect_modules(args.detect, modules)
        matched_integrations = detect_integrations(args.detect)
        matched_skills = run_skills_detect(args.detect, as_json=args.json)
        if args.json:
            payload = {
                "modules": [
                    {
                        "name": module["name"],
                        "path": module.get("path"),
                        "matched_triggers": module["matched_triggers"],
                        "actions": normalize_actions(module),
                    }
                    for module in matched
                ],
                "integrations": matched_integrations,
                "skills": json.loads(matched_skills) if matched_skills else [],
            }
            print(json.dumps(payload, ensure_ascii=False, indent=2))
        else:
            output = render_detect(matched, args.detect, matched_integrations)
            if output:
                print(output)
            if matched_skills:
                print(matched_skills)
        return 0

    if args.actions:
        modules = collect_tool_modules()
        if args.json:
            payload = [
                {
                    "name": module.get("name"),
                    "path": module.get("path"),
                    "purpose": module.get("purpose"),
                    "actions": normalize_actions(module),
                }
                for module in modules
                if module.get("status") == "ok"
            ]
            print(json.dumps(payload, ensure_ascii=False, indent=2))
        else:
            print(render_actions_catalog(modules))
        return 0

    if args.module:
        modules = collect_tool_modules(args.module)
        if not modules:
            print(f"Modulo nao encontrado: {args.module}")
            return 1
        if args.json:
            print(json.dumps(modules[0], ensure_ascii=False, indent=2))
        else:
            module = load_tool_module(CLAUDE_ROOT / modules[0]["folder"] / "main.py")
            renderer = getattr(module, "render", None)
            print(renderer(modules[0]) if renderer else render_tool_modules(modules))
        return 0

    context = get_context(use_cache=not args.no_cache)
    if args.json:
        print(json.dumps(context, ensure_ascii=False, indent=2))
    else:
        print(render_markdown(context))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
