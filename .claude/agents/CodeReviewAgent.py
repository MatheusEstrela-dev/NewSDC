#!/usr/bin/env python3
from __future__ import annotations

import argparse
from pathlib import Path
import subprocess
import sys

try:
    from _base import AgentReport, default_roots, print_report
except ImportError:
    sys.path.insert(0, str(Path(__file__).resolve().parent))
    from _base import AgentReport, default_roots, print_report


AGENT = {
    "name": "code-review",
    "class": "CodeReviewAgent",
    "purpose": "revisao tecnica de codigo, riscos de regressao, testes e contratos Laravel/Vue",
    "triggers": [
        "review",
        "revisao",
        "code review",
        "bug",
        "regressao",
        "teste",
        "validacao",
        "refactor",
        "controller",
        "vue",
        "laravel",
    ],
    "functions": [
        {
            "slug": "plan",
            "name": "plan_review",
            "description": "Monta plano de revisao por arquivos/risco e sugere validacoes.",
            "command": "python .claude/agents/CodeReviewAgent.py plan",
            "when": "antes ou depois de alterar codigo de produto.",
        },
        {
            "slug": "changed",
            "name": "review_changed_files",
            "description": "Usa o diff local para orientar revisao dos arquivos alterados.",
            "command": "python .claude/agents/CodeReviewAgent.py changed",
            "when": "quando houver worktree suja ou pedido de revisar alteracoes locais.",
        },
        {
            "slug": "validation",
            "name": "validation_commands",
            "description": "Sugere comandos de validacao proporcionais aos caminhos afetados.",
            "command": "python .claude/agents/CodeReviewAgent.py validation --path SDC/app/Modules/...",
            "when": "ao finalizar uma implementacao ou investigar regressao.",
        },
    ],
}


class CodeReviewAgent:
    def __init__(self, repo_root: Path | None = None, app_root: Path | None = None):
        default_repo, default_app, _ = default_roots(__file__)
        self.name = "CodeReviewAgent"
        self.repo_root = repo_root or default_repo
        self.app_root = app_root or default_app

    def _changed_files(self) -> list[str]:
        try:
            result = subprocess.run(
                ["git", "status", "--short"],
                cwd=self.repo_root,
                text=True,
                capture_output=True,
                timeout=5,
                check=False,
            )
        except Exception:
            return []
        files: list[str] = []
        for line in result.stdout.splitlines():
            path = line[3:].strip()
            if " -> " in path:
                path = path.split(" -> ", 1)[1]
            if path:
                files.append(path)
        return files

    def plan_review(self, paths: list[str] | None = None, risk: str = "normal") -> AgentReport:
        paths = paths or []
        checks = [
            "Ler o codigo existente antes de apontar problema ou propor refatoracao.",
            "Priorizar bug, regressao, quebra de contrato, permissao, validacao e falta de teste.",
            "Conferir rota nomeada, request/service/model/resource e pagina/composable quando a mudanca cruzar backend/frontend.",
            "Evitar comentario cosmetico se nao houver impacto tecnico real.",
        ]
        if paths:
            checks.append("Escopo informado: " + ", ".join(paths[:8]))

        return AgentReport(
            agent=self.name,
            objective="planejar revisao tecnica",
            summary="Plano de revisao focado em defeitos observaveis e validacoes proporcionais ao risco.",
            checks=checks,
            commands=self.validation_commands(paths).commands,
            handoff=[
                "Se encontrar falha de ambiente, passar contexto para InfraAgent.",
                "Ao concluir, listar achados por severidade com arquivo/linha.",
            ],
            risk=risk,
        )

    def review_changed_files(self) -> AgentReport:
        changed = self._changed_files()
        if not changed:
            return AgentReport(
                agent=self.name,
                objective="revisar alteracoes locais",
                summary="Nenhuma alteracao local foi detectada por `git status --short`.",
                checks=["Se o objetivo era revisar outra branch, informar base/diff explicitamente."],
                commands=["git status --short"],
            )
        risk = "alto" if any(path.startswith(("SDC/database/", "SDC/routes/", "SDC/app/Modules/")) for path in changed) else "normal"
        report = self.plan_review(changed, risk=risk)
        report.objective = "revisar alteracoes locais"
        report.summary = f"{len(changed)} arquivo(s) alterado(s) detectado(s)."
        return report

    def validation_commands(self, paths: list[str] | None = None) -> AgentReport:
        paths = paths or []
        commands = ["git status --short"]
        checks = ["Escolher o menor conjunto de validacoes que prove a mudanca."]

        joined = " ".join(paths)
        if not paths or any(path.endswith(".php") or "/app/" in path or "/routes/" in path for path in paths):
            commands.append("docker exec newsdc_frankenphp_local php artisan test")
        if any(path.endswith(".php") for path in paths):
            commands.append("docker exec newsdc_frankenphp_local php -l /app/caminho/do/arquivo.php")
        if not paths or any(path.endswith((".vue", ".js", ".ts")) or "/resources/js/" in path for path in paths):
            commands.append("docker exec newsdc_bun bunx vite build")
        if "routes" in joined or "ziggy" in joined.lower():
            commands.append("docker exec newsdc_frankenphp_local php artisan route:list")
            commands.append("docker exec newsdc_frankenphp_local php artisan ziggy:generate resources/js/ziggy.js")
        if "database" in joined or "migration" in joined.lower():
            commands.append("docker exec newsdc_frankenphp_local php artisan migrate:status")

        return AgentReport(
            agent=self.name,
            objective="sugerir validacoes",
            summary="Comandos de validacao derivados dos caminhos afetados.",
            checks=checks,
            commands=commands,
            handoff=["Substituir caminhos genericos antes de executar lint pontual."],
        )


def main() -> int:
    parser = argparse.ArgumentParser(description=AGENT["purpose"])
    parser.add_argument("--json", action="store_true", help="Emite relatorio em JSON.")
    sub = parser.add_subparsers(dest="command")
    plan = sub.add_parser("plan", help="Plano de revisao.")
    plan.add_argument("--risk", default="normal", choices=["baixo", "normal", "alto"])
    plan.add_argument("--path", action="append", default=[])
    sub.add_parser("changed", help="Revisao orientada pelo diff local.")
    validation = sub.add_parser("validation", help="Comandos de validacao.")
    validation.add_argument("--path", action="append", default=[])
    args = parser.parse_args()

    agent = CodeReviewAgent()
    if args.command == "changed":
        report = agent.review_changed_files()
    elif args.command == "validation":
        report = agent.validation_commands(args.path)
    else:
        report = agent.plan_review(getattr(args, "path", []), getattr(args, "risk", "normal"))
    print_report(report, as_json=args.json)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
