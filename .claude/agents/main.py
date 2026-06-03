#!/usr/bin/env python3
from __future__ import annotations

import sys
import importlib.util
import argparse
import json
from pathlib import Path
from types import ModuleType
from typing import Any

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from _module import collect_module, default_roots, render_module  # noqa: E402


MODULE = {
    "name": "agents",
    "purpose": "registro de agentes de orquestracao, subagentes e funcoes especializadas",
    "read_order": ["ScheduleAgent.py", "InfraAgent.py", "CodeReviewAgent.py", "routine_agent.py"],
    "commands": ["python .claude/agents/main.py", "python .claude/agents/ScheduleAgent.py route --prompt \"...\""],
    "triggers": [
        "agente",
        "agent",
        "subagente",
        "delegar",
        "orquestrar",
        "orquestracao",
        "schedule",
        "infra",
        "rotina",
        "code review",
        "revisor",
        "reviewer",
    ],
    "actions": [
        {
            "slug": "summary",
            "description": "Lista os perfis de agentes/subagentes disponiveis.",
            "command": "python .claude/agents/main.py",
            "when": "antes de delegar uma tarefa especializada via Agent tool.",
        },
        {
            "slug": "route",
            "description": "Roteia uma tarefa textual para o agente especialista adequado.",
            "command": "python .claude/kernel.py --agent-call schedule.route --prompt \"texto da tarefa\"",
            "when": "quando houver duvida sobre qual agente deve assumir.",
        },
        {
            "slug": "schedule.tick",
            "description": "Avalia a agenda atual e recomenda a rotina/agente do horario.",
            "command": "python .claude/kernel.py --agent-call schedule.tick",
            "when": "em loops autonomos ou checkpoints periodicos.",
        },
        {
            "slug": "infra.health",
            "description": "Monta triagem de saude para containers, logs, filas, banco e cache.",
            "command": "python .claude/kernel.py --agent-call infra.health",
            "when": "em tarefas de ambiente, deploy, container, fila, log ou banco.",
        },
        {
            "slug": "code-review.plan",
            "description": "Planeja revisao tecnica e validacoes proporcionais ao risco.",
            "command": "python .claude/kernel.py --agent-call code-review.plan",
            "when": "antes/depois de alterar codigo ou ao investigar regressao.",
        },
        {
            "slug": "routine.closeout",
            "description": "Gera checklist de fechamento, validacao e handoff.",
            "command": "python .claude/kernel.py --agent-call routine.closeout",
            "when": "ao finalizar implementacao, diagnostico ou manutencao.",
        },
    ],
}


def _agent_entrypoints(module_dir: Path) -> list[Path]:
    return sorted(
        path
        for path in module_dir.glob("*.py")
        if path.name not in {"main.py", "_base.py"} and not path.name.startswith("__")
    )


def _load_agent_module(path: Path) -> ModuleType:
    module_name = f"claude_agent_{path.stem}"
    spec = importlib.util.spec_from_file_location(module_name, path)
    if spec is None or spec.loader is None:
        raise RuntimeError(f"Nao foi possivel carregar {path}")
    module = importlib.util.module_from_spec(spec)
    sys.path.insert(0, str(path.parent))
    spec.loader.exec_module(module)
    return module


def discover_agents(module_dir: Path | None = None, repo_root: Path | None = None) -> list[dict[str, Any]]:
    module_dir = module_dir or Path(__file__).resolve().parent
    repo_root = repo_root or module_dir.parents[1]
    agents: list[dict[str, Any]] = []
    for path in _agent_entrypoints(module_dir):
        try:
            module = _load_agent_module(path)
            manifest = getattr(module, "AGENT")
            entry = dict(manifest)
            entry["path"] = path.relative_to(repo_root).as_posix()
            entry["status"] = "ok"
        except Exception as exc:
            entry = {
                "name": path.stem,
                "class": "",
                "purpose": "falha ao carregar agente",
                "path": path.relative_to(repo_root).as_posix(),
                "functions": [],
                "triggers": [],
                "status": "error",
                "error": str(exc),
            }
        agents.append(entry)
    return agents


def _agent_actions(agents: list[dict[str, Any]]) -> list[dict[str, str]]:
    actions: list[dict[str, str]] = list(MODULE.get("actions", []))
    seen = {action.get("slug", "") for action in actions}
    for agent in agents:
        if agent.get("status") != "ok":
            continue
        for function in agent.get("functions", []):
            slug = f"{agent['name']}.{function.get('slug', function.get('name', 'run'))}"
            if slug in seen:
                continue
            seen.add(slug)
            actions.append({
                "slug": slug,
                "description": function.get("description", ""),
                "command": _kernel_command_for(slug),
                "when": function.get("when", ""),
            })
    return actions


def _kernel_command_for(slug: str) -> str:
    command = f"python .claude/kernel.py --agent-call {slug}"
    if slug == "schedule.route":
        return command + ' --prompt "texto da tarefa"'
    if slug == "infra.diagnose":
        return command + ' --symptom "texto do erro"'
    if slug in {"code-review.plan", "code-review.validation"}:
        return command + " --path SDC/app/Modules/..."
    if slug == "routine.handoff":
        return command + ' --topic "tarefa"'
    return command


def collect(repo_root: Path, app_root: Path, claude_root: Path) -> dict:
    _ = app_root, claude_root
    module_dir = Path(__file__).resolve().parent
    agents = discover_agents(module_dir, repo_root)
    data = collect_module(module_dir, repo_root, MODULE)
    data["agents"] = agents
    data["actions"] = _agent_actions(agents)
    return data


def render(data: dict) -> str:
    lines = [render_module(data)]
    if data.get("agents"):
        lines.append("")
        lines.append("### Agentes registrados")
        for agent in data["agents"]:
            status = agent.get("status", "ok")
            lines.append(f"- `{agent['name']}` ({agent.get('class', '')}) via `{agent.get('path', '')}`: {agent.get('purpose', '')}")
            if status != "ok":
                lines.append(f"  - Erro: {agent.get('error', 'desconhecido')}")
                continue
            triggers = agent.get("triggers", [])
            if triggers:
                lines.append(f"  - Gatilhos: {', '.join(triggers[:10])}")
            for function in agent.get("functions", []):
                lines.append(
                    f"  - `{function.get('name')}`: {function.get('description', '')} "
                    f"(`{function.get('command', '')}`)"
                )
    return "\n".join(lines)


def main() -> int:
    parser = argparse.ArgumentParser(description=MODULE["purpose"])
    parser.add_argument("--json", action="store_true", help="Emite o contexto do modulo em JSON.")
    args = parser.parse_args()

    module_dir = Path(__file__).resolve().parent
    repo_root, app_root, claude_root = default_roots(module_dir)
    data = collect(repo_root, app_root, claude_root)
    if args.json:
        print(json.dumps(data, ensure_ascii=False, indent=2))
    else:
        print(render(data))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
