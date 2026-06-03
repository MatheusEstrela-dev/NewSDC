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
    "name": "routine",
    "class": "RoutineAgent",
    "purpose": "rotinas de abertura, acompanhamento, fechamento, handoff e higiene operacional da sessao",
    "triggers": [
        "rotina",
        "routine",
        "briefing",
        "handoff",
        "fechamento",
        "checklist",
        "planejar dia",
        "acompanhamento",
        "memoria",
    ],
    "functions": [
        {
            "slug": "morning",
            "name": "morning_briefing",
            "description": "Cria uma rotina curta para abrir sessao de trabalho com contexto e riscos.",
            "command": "python .claude/agents/routine_agent.py morning",
            "when": "no inicio de uma sessao ou antes de assumir uma tarefa grande.",
        },
        {
            "slug": "closeout",
            "name": "closeout_checklist",
            "description": "Gera checklist de fechamento com validacao, logs e handoff.",
            "command": "python .claude/agents/routine_agent.py closeout",
            "when": "ao finalizar implementacao, investigacao ou manutencao.",
        },
        {
            "slug": "handoff",
            "name": "task_handoff",
            "description": "Organiza estado atual, decisoes, pendencias e proximas acoes.",
            "command": "python .claude/agents/routine_agent.py handoff --topic \"tarefa\"",
            "when": "quando a tarefa precisar ser retomada depois ou delegada.",
        },
    ],
}


class RoutineAgent:
    def __init__(self, repo_root: Path | None = None, app_root: Path | None = None):
        default_repo, default_app, _ = default_roots(__file__)
        self.name = "RoutineAgent"
        self.repo_root = repo_root or default_repo
        self.app_root = app_root or default_app

    def morning_briefing(self) -> AgentReport:
        return AgentReport(
            agent=self.name,
            objective="abrir sessao de trabalho",
            summary="Sequencia curta para carregar contexto local antes de implementar ou revisar.",
            checks=[
                "Rodar ou consultar `.claude/kernel.py --detect` para o prompt atual.",
                "Conferir `git status --short` e nao sobrescrever mudancas alheias.",
                "Ler docs/skills/memory relevantes antes de editar.",
                "Definir validacao minima esperada antes de iniciar mudancas.",
            ],
            commands=[
                "python .claude/kernel.py --actions",
                "git status --short",
                "python .claude/kernel.py --module agents",
                "python .claude/kernel.py --agent-functions",
            ],
            handoff=[
                "Delegar ambiente para InfraAgent, revisao para CodeReviewAgent e agenda/roteamento para ScheduleAgent.",
            ],
        )

    def closeout_checklist(self) -> AgentReport:
        return AgentReport(
            agent=self.name,
            objective="fechar tarefa com rastreabilidade",
            summary="Checklist final para entregar mudanca com estado verificavel.",
            checks=[
                "Listar arquivos alterados e separar mudancas do usuario das suas.",
                "Rodar validacoes proporcionais ou registrar claramente o que nao foi possivel rodar.",
                "Atualizar memoria/erro somente se a tarefa gerou decisao ou armadilha reutilizavel.",
                "Responder com mudancas feitas, validacoes e riscos residuais.",
            ],
            commands=[
                "git status --short",
                "python .claude/kernel.py --no-cache --module agents",
                "python .claude/kernel.py --verify-subsystem",
            ],
            handoff=[
                "Se testes falharam por ambiente, anexar log e passar para InfraAgent.",
                "Se sobrou risco de regressao, passar para CodeReviewAgent.",
            ],
        )

    def task_handoff(self, topic: str = "tarefa atual") -> AgentReport:
        return AgentReport(
            agent=self.name,
            objective=f"handoff de {topic}",
            summary="Formato para deixar continuidade clara e auditavel.",
            checks=[
                "Estado atual: o que foi lido, alterado e validado.",
                "Decisoes: escolhas tecnicas e motivos.",
                "Pendencias: itens nao concluidos, bloqueios e riscos.",
                "Proximas acoes: comandos ou arquivos que a proxima pessoa deve abrir primeiro.",
            ],
            commands=[
                "git status --short",
                "python .claude/kernel.py --agent-call routine.handoff --topic \"tarefa\"",
            ],
            handoff=[
                "Use `.claude/memory/append.py` apenas para fatos reutilizaveis entre sessoes.",
            ],
        )


def main() -> int:
    parser = argparse.ArgumentParser(description=AGENT["purpose"])
    parser.add_argument("--json", action="store_true", help="Emite relatorio em JSON.")
    sub = parser.add_subparsers(dest="command")
    sub.add_parser("morning", help="Briefing de abertura.")
    sub.add_parser("closeout", help="Checklist de fechamento.")
    handoff = sub.add_parser("handoff", help="Handoff de tarefa.")
    handoff.add_argument("--topic", default="tarefa atual")
    args = parser.parse_args()

    agent = RoutineAgent()
    if args.command == "closeout":
        report = agent.closeout_checklist()
    elif args.command == "handoff":
        report = agent.task_handoff(args.topic)
    else:
        report = agent.morning_briefing()
    print_report(report, as_json=args.json)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
