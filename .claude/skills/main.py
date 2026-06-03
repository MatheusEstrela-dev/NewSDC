#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import re
import sys
import unicodedata
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from _module import collect_module, render_module  # noqa: E402


SKILLS = [
    {
        "name": "api",
        "path": "api/SKILL.MD",
        "purpose": "API HTTP, endpoints REST, autenticacao Sanctum e documentacao Swagger/OpenAPI.",
        "triggers": [
            "api", "endpoint", "endpoints", "rest", "swagger", "openapi",
            "l5-swagger", "sanctum", "bearer", "token",
        ],
    },
    {
        "name": "backend",
        "path": "backend/SKILL.MD",
        "purpose": "Backend Laravel, modulos de dominio, controllers, services, DTOs e Eloquent.",
        "triggers": [
            "backend", "laravel", "php", "controller", "controllers", "service",
            "services", "dto", "usecase", "eloquent", "ddd", "artisan", "route",
            "routes", "rota", "rotas",
        ],
    },
    {
        "name": "database",
        "path": "database/SKILL.MD",
        "purpose": "Banco de dados, schema, migrations, consultas e compatibilidade PostgreSQL/MySQL.",
        "triggers": [
            "database", "banco", "bancos", "schema", "migration", "migrations",
            "migracao", "migracoes", "postgres", "postgresql", "mysql", "sql",
            "indice", "indices", "query", "queries",
        ],
    },
    {
        "name": "frontend",
        "path": "frontend/SKILL.MD",
        "purpose": "Frontend Vue, Inertia, componentes, composables, Tailwind e Vite.",
        "triggers": [
            "frontend", "vue", "inertia", "tailwind", "vite", "javascript",
            "typescript", "composable", "composables", "component", "components",
            "componente", "componentes",
        ],
    },
    {
        "name": "specs",
        "path": "specs/SKILL.MD",
        "purpose": "Especificacoes, planos de implementacao, requisitos e decisoes de arquitetura.",
        "triggers": [
            "spec", "specs", "especificacao", "especificacoes", "requisito",
            "requisitos", "plano", "planejamento", "design", "arquitetura",
            "architecture",
        ],
    },
]


MODULE = {
    "name": "skills",
    "purpose": "habilidades especializadas com roteamento por assunto da conversa",
    "read_order": ["*/SKILL.md", "*/SKILL.MD", "*.md"],
    "commands": [
        "python .claude/skills/main.py",
        'python .claude/skills/main.py --detect "<mensagem-do-usuario>"',
    ],
    "triggers": sorted({
        "skill", "skills", "tanstack", "spatie", "octane", "frankenphp",
        *(trigger for skill in SKILLS for trigger in skill["triggers"]),
    }),
    "actions": [{
        "slug": "summary",
        "description": "Lista as skills disponiveis e o comando de classificacao.",
        "command": "python .claude/skills/main.py",
        "when": "antes de iniciar trabalho especializado.",
    }, {
        "slug": "detect",
        "description": "Identifica as skills relevantes para a mensagem da conversa.",
        "command": 'python .claude/skills/main.py --detect "<mensagem-do-usuario>"',
        "when": "no inicio de uma conversa ou antes de trocar de contexto.",
    }],
}


def normalize_text(value: str) -> str:
    normalized = unicodedata.normalize("NFKD", value.lower())
    return "".join(char for char in normalized if not unicodedata.combining(char))


def detect_skills(prompt_text: str) -> list[dict[str, object]]:
    text = normalize_text(prompt_text)
    matched: list[dict[str, object]] = []
    for skill in SKILLS:
        hits = [
            trigger
            for trigger in skill["triggers"]
            if re.search(rf"(?<![a-z0-9_]){re.escape(trigger)}(?![a-z0-9_])", text)
        ]
        if hits:
            item = dict(skill)
            item["matched_triggers"] = hits
            matched.append(item)
    return matched


def render_detected_skills(matched: list[dict[str, object]]) -> str:
    if not matched:
        return ""

    lines = [
        "<context-from-claude-skills>",
        "Skills especificas recomendadas para esta conversa:",
        "",
    ]
    for skill in matched:
        lines.extend([
            f"## {skill['name']}",
            f"- Papel: {skill['purpose']}",
            f"- Gatilhos casados: {', '.join(skill['matched_triggers'])}",
            f"- Leia primeiro: `.claude/skills/{skill['path']}`",
            "",
        ])
    lines.append("</context-from-claude-skills>")
    return "\n".join(lines)


def collect(repo_root: Path, app_root: Path, claude_root: Path) -> dict:
    _ = app_root, claude_root
    data = collect_module(Path(__file__).resolve().parent, repo_root, MODULE)
    data["skill_catalog"] = SKILLS
    return data


def render(data: dict) -> str:
    lines = [
        render_module(data),
        "",
        "### Inicializacao do roteador",
        '- `python .claude/skills/main.py --detect "<mensagem-do-usuario>"`',
        "",
        "### Skills disponiveis",
    ]
    for skill in SKILLS:
        lines.append(f"- `{skill['name']}`: `{skill['path']}` - {skill['purpose']}")
    return "\n".join(lines)


def main() -> int:
    parser = argparse.ArgumentParser(description=MODULE["purpose"])
    parser.add_argument("--json", action="store_true", help="Emite o contexto em JSON.")
    parser.add_argument("--detect", help="Texto da conversa usado para recomendar skills.")
    args = parser.parse_args()

    if args.detect is not None:
        matched = detect_skills(args.detect)
        if args.json:
            print(json.dumps(matched, ensure_ascii=False, indent=2))
        else:
            output = render_detected_skills(matched)
            if output:
                print(output)
        return 0

    repo_root = Path(__file__).resolve().parents[2]
    data = collect(repo_root, repo_root / "SDC", repo_root / ".claude")
    if args.json:
        print(json.dumps(data, ensure_ascii=False, indent=2))
    else:
        print(render(data))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
