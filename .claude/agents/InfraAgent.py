#!/usr/bin/env python3
from __future__ import annotations

import argparse
from pathlib import Path
import sys

try:
    from _base import AgentReport, default_roots, print_report
except ImportError:
    sys.path.insert(0, str(Path(__file__).resolve().parent))
    from _base import AgentReport, default_roots, print_report


AGENT = {
    "name": "infra",
    "class": "InfraAgent",
    "purpose": "triagem de infraestrutura local/prod, containers, logs, filas, deploy e saude operacional",
    "triggers": [
        "infra",
        "infraestrutura",
        "deploy",
        "docker",
        "frankenphp",
        "container",
        "azure",
        "redis",
        "postgres",
        "fila",
        "queue",
        "log",
        "erro de ambiente",
    ],
    "functions": [
        {
            "slug": "health",
            "name": "check_system_health",
            "description": "Monta uma triagem de saude para app, containers, logs, banco, cache e filas.",
            "command": "python .claude/agents/InfraAgent.py health",
            "when": "quando a tarefa envolver ambiente, container, deploy, log, fila, cache ou banco.",
        },
        {
            "slug": "deploy",
            "name": "plan_deploy_checklist",
            "description": "Gera checklist de pre-deploy/pos-deploy alinhado a Docker, Laravel e Azure.",
            "command": "python .claude/agents/InfraAgent.py deploy",
            "when": "antes de publicar, reiniciar servicos ou diagnosticar pipeline.",
        },
        {
            "slug": "diagnose",
            "name": "diagnose_failure",
            "description": "Classifica uma falha informada e sugere leituras/validacoes iniciais.",
            "command": "python .claude/agents/InfraAgent.py diagnose --symptom \"texto do erro\"",
            "when": "quando houver sintoma operacional especifico.",
        },
    ],
}


class InfraAgent:
    def __init__(self, repo_root: Path | None = None, app_root: Path | None = None):
        default_repo, default_app, _ = default_roots(__file__)
        self.name = "InfraAgent"
        self.repo_root = repo_root or default_repo
        self.app_root = app_root or default_app

    def check_system_health(self, focus: str = "local") -> AgentReport:
        checks = [
            "Confirmar se o container `newsdc_frankenphp_local` esta ativo antes de rodar comandos Laravel.",
            "Ler os ultimos logs antes de alterar configuracao ou reiniciar servicos.",
            "Verificar migrations, filas e cache quando a falha envolver dados, jobs ou sessoes.",
            "Separar problema de build frontend de problema PHP/container.",
        ]
        commands = [
            "docker ps --filter name=newsdc",
            "docker logs newsdc_frankenphp_local --tail 120",
            "docker exec newsdc_frankenphp_local php artisan about",
            "docker exec newsdc_frankenphp_local php artisan migrate:status",
            "docker exec newsdc_frankenphp_local php artisan queue:failed",
            "docker exec newsdc_frankenphp_local tail -n 120 /app/storage/logs/laravel.log",
        ]
        if focus in {"prod", "azure"}:
            checks.extend([
                "Carregar credenciais somente via `.claude/credentials.py` quando a tarefa exigir Azure/prod.",
                "Evitar DDL/DML ou reinicio de recursos produtivos sem confirmacao explicita.",
            ])
            commands.extend([
                "python .claude/credentials.py --section azure_prod",
                "python .claude/credentials.py --section database_prod",
            ])

        return AgentReport(
            agent=self.name,
            objective=f"saude de infraestrutura ({focus})",
            summary="Roteiro de triagem para confirmar estado do ambiente antes de mexer em codigo ou configuracao.",
            checks=checks,
            commands=commands,
            handoff=[
                "Se o erro vier de codigo PHP/JS, delegar para CodeReviewAgent apos coletar stack trace.",
                "Se a falha for recorrente ou datada, registrar em `.claude/error/log.py`.",
            ],
            risk="alto" if focus in {"prod", "azure"} else "normal",
        )

    def plan_deploy_checklist(self) -> AgentReport:
        return AgentReport(
            agent=self.name,
            objective="preparar deploy ou manutencao operacional",
            summary="Checklist de deploy com validacoes minimas antes e depois da publicacao.",
            checks=[
                "Confirmar branch, diff local e plano de rollback.",
                "Rodar build/testes proporcionais antes de publicar.",
                "Confirmar migrations pendentes e impacto em filas/workers.",
                "Validar variaveis de ambiente e integracoes externas envolvidas.",
                "Apos deploy, conferir health, logs, filas e rota critica alterada.",
            ],
            commands=[
                "git status --short",
                "docker exec newsdc_frankenphp_local php artisan test",
                "docker exec newsdc_bun bunx vite build",
                "docker logs newsdc_frankenphp_local --tail 120",
            ],
            handoff=[
                "Encaminhar revisao de regressao para CodeReviewAgent quando houver mudanca de contrato.",
                "Encaminhar rotina de fechamento para RoutineAgent ao final da manutencao.",
            ],
            risk="alto",
        )

    def diagnose_failure(self, symptom: str) -> AgentReport:
        normalized = symptom.lower()
        checks = ["Capturar erro literal, horario aproximado e acao que disparou a falha."]
        commands = ["docker exec newsdc_frankenphp_local tail -n 120 /app/storage/logs/laravel.log"]
        if any(term in normalized for term in ["vite", "build", "asset", "ziggy", "vue"]):
            checks.append("Verificar se a falha esta no build frontend, imports, Ziggy ou componente Vue.")
            commands.append("docker exec newsdc_bun bunx vite build")
        if any(term in normalized for term in ["route", "rota", "404", "ziggy"]):
            checks.append("Confirmar rota nomeada, middleware e geracao do Ziggy.")
            commands.append("docker exec newsdc_frankenphp_local php artisan route:list")
        if any(term in normalized for term in ["sql", "database", "banco", "migration", "postgres"]):
            checks.append("Checar migration/status do schema antes de alterar model ou query.")
            commands.append("docker exec newsdc_frankenphp_local php artisan migrate:status")
        if any(term in normalized for term in ["queue", "fila", "job", "redis"]):
            checks.append("Verificar fila, Redis e jobs falhados.")
            commands.append("docker exec newsdc_frankenphp_local php artisan queue:failed")

        return AgentReport(
            agent=self.name,
            objective="diagnosticar falha operacional",
            summary=f"Sintoma analisado: {symptom or 'nao informado'}.",
            checks=checks,
            commands=commands,
            handoff=["Depois de isolar camada afetada, delegar correcao ao agente especialista correspondente."],
            risk="normal",
        )


def main() -> int:
    parser = argparse.ArgumentParser(description=AGENT["purpose"])
    parser.add_argument("--json", action="store_true", help="Emite relatorio em JSON.")
    sub = parser.add_subparsers(dest="command")
    health = sub.add_parser("health", help="Triagem de saude de infraestrutura.")
    health.add_argument("--focus", default="local", choices=["local", "prod", "azure"])
    sub.add_parser("deploy", help="Checklist de deploy.")
    diagnose = sub.add_parser("diagnose", help="Diagnostico inicial por sintoma.")
    diagnose.add_argument("--symptom", default="")
    args = parser.parse_args()

    agent = InfraAgent()
    if args.command == "deploy":
        report = agent.plan_deploy_checklist()
    elif args.command == "diagnose":
        report = agent.diagnose_failure(args.symptom)
    else:
        report = agent.check_system_health(getattr(args, "focus", "local"))
    print_report(report, as_json=args.json)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
