#!/usr/bin/env python3
from __future__ import annotations

import argparse
import datetime as dt
import json
from pathlib import Path
import sys

try:
    from _base import AgentReport, default_roots, print_report
    from CodeReviewAgent import CodeReviewAgent
    from InfraAgent import InfraAgent
    from routine_agent import RoutineAgent
except ImportError:
    sys.path.insert(0, str(Path(__file__).resolve().parent))
    from _base import AgentReport, default_roots, print_report
    from CodeReviewAgent import CodeReviewAgent
    from InfraAgent import InfraAgent
    from routine_agent import RoutineAgent


AGENT = {
    "name": "schedule",
    "class": "ScheduleAgent",
    "purpose": "orquestracao temporal e roteamento de tarefas para agentes especialistas",
    "triggers": [
        "schedule",
        "agenda",
        "orquestrar",
        "orquestracao",
        "rotear",
        "delegar",
        "quando",
        "planejar",
        "cron",
    ],
    "functions": [
        {
            "slug": "tick",
            "name": "tick",
            "description": "Avalia o horario atual e retorna a rotina/agente recomendado.",
            "command": "python .claude/agents/ScheduleAgent.py tick",
            "when": "em ciclos periodicos ou ao simular uma janela operacional.",
        },
        {
            "slug": "route",
            "name": "route_task",
            "description": "Roteia um pedido textual para infra, revisao de codigo ou rotina.",
            "command": "python .claude/agents/ScheduleAgent.py route --prompt \"texto da tarefa\"",
            "when": "quando a tarefa pedir delegacao ou houver duvida sobre qual agente usar.",
        },
        {
            "slug": "day",
            "name": "plan_day",
            "description": "Mostra a grade de rotinas sugeridas para o dia de trabalho.",
            "command": "python .claude/agents/ScheduleAgent.py day",
            "when": "para organizar uma sessao longa ou manutencao planejada.",
        },
    ],
}


class ScheduleAgent:
    SLOTS = {
        "03:00": ("infra", "health", "janela de manutencao"),
        "08:00": ("routine", "morning", "abertura de expediente"),
        "12:00": ("code-review", "validation", "checkpoint de risco"),
        "17:00": ("routine", "closeout", "fechamento e handoff"),
    }

    ROUTES = {
        "infra": {"infra", "deploy", "docker", "frankenphp", "azure", "redis", "postgres", "queue", "fila", "log"},
        "code-review": {"review", "revisao", "bug", "regressao", "teste", "controller", "vue", "laravel", "refactor"},
        "routine": {"rotina", "briefing", "handoff", "fechamento", "checklist", "memoria"},
    }

    def __init__(
        self,
        infra_agent: InfraAgent | None = None,
        code_agent: CodeReviewAgent | None = None,
        routine_agent: RoutineAgent | None = None,
        repo_root: Path | None = None,
        app_root: Path | None = None,
    ):
        default_repo, default_app, _ = default_roots(__file__)
        self.name = "ScheduleAgent"
        self.repo_root = repo_root or default_repo
        self.app_root = app_root or default_app
        self.infra_agent = infra_agent or InfraAgent(self.repo_root, self.app_root)
        self.code_agent = code_agent or CodeReviewAgent(self.repo_root, self.app_root)
        self.routine_agent = routine_agent or RoutineAgent(self.repo_root, self.app_root)

    def tick(self, at: dt.datetime | None = None) -> AgentReport:
        now = at or dt.datetime.now()
        current_time = now.strftime("%H:%M")
        if current_time in self.SLOTS:
            agent, action, reason = self.SLOTS[current_time]
            return AgentReport(
                agent=self.name,
                objective="avaliar agenda operacional",
                summary=f"{current_time}: executar `{action}` com `{agent}` ({reason}).",
                checks=[f"Janela casada: {reason}.", "Executar a acao apenas se fizer sentido para a tarefa atual."],
                commands=[self._command_for(agent, action)],
                handoff=[f"Delegar para {agent}."],
            )

        next_slot = self._next_slot(now)
        return AgentReport(
            agent=self.name,
            objective="avaliar agenda operacional",
            summary=f"{current_time}: nenhuma rotina obrigatoria. Proxima janela sugerida: {next_slot}.",
            checks=["Manter foco na tarefa atual e chamar `route` se precisar delegar."],
            commands=["python .claude/agents/ScheduleAgent.py route --prompt \"texto da tarefa\""],
        )

    def route_task(self, prompt: str) -> AgentReport:
        text = prompt.lower()
        scores = {
            agent: sum(1 for trigger in triggers if trigger in text)
            for agent, triggers in self.ROUTES.items()
        }
        selected = max(scores, key=scores.get)
        if scores[selected] == 0:
            selected = "routine"

        action = {
            "infra": "health",
            "code-review": "plan",
            "routine": "morning",
        }[selected]
        return AgentReport(
            agent=self.name,
            objective="rotear tarefa para agente especialista",
            summary=f"Agente recomendado: `{selected}` com acao `{action}`.",
            checks=[
                f"Prompt analisado: {prompt or 'nao informado'}.",
                "Confirmar se ha contexto local suficiente antes de executar comandos sugeridos.",
            ],
            commands=[self._command_for(selected, action, prompt)],
            handoff=[f"Use `{selected}` como responsavel primario; retorne ao schedule se a tarefa mudar de dominio."],
        )

    def plan_day(self) -> AgentReport:
        checks = [f"{slot}: {agent}.{action} - {reason}" for slot, (agent, action, reason) in self.SLOTS.items()]
        return AgentReport(
            agent=self.name,
            objective="planejar rotinas do dia",
            summary="Grade simples de orquestracao para abertura, checkpoints, manutencao e fechamento.",
            checks=checks,
            commands=[self._command_for(agent, action) for agent, action, _ in self.SLOTS.values()],
            handoff=["A agenda e sugestiva; tarefas criticas devem prevalecer sobre horario fixo."],
        )

    def _command_for(self, agent: str, action: str, prompt: str = "") -> str:
        if agent == "infra":
            return "python .claude/kernel.py --agent-call infra.health"
        if agent == "code-review":
            if action == "validation":
                return "python .claude/kernel.py --agent-call code-review.validation"
            return "python .claude/kernel.py --agent-call code-review.plan"
        if agent == "routine":
            if action == "closeout":
                return "python .claude/kernel.py --agent-call routine.closeout"
            return "python .claude/kernel.py --agent-call routine.morning"
        escaped = prompt.replace('"', '\\"')
        return f'python .claude/kernel.py --agent-call schedule.route --prompt "{escaped}"'

    def _next_slot(self, now: dt.datetime) -> str:
        today_minutes = now.hour * 60 + now.minute
        slots = sorted((int(slot[:2]) * 60 + int(slot[3:]), slot) for slot in self.SLOTS)
        for minute, slot in slots:
            if minute > today_minutes:
                return slot
        return f"{slots[0][1]} amanha"


def _parse_time(value: str | None) -> dt.datetime | None:
    if not value:
        return None
    hour, minute = value.split(":", 1)
    now = dt.datetime.now()
    return now.replace(hour=int(hour), minute=int(minute), second=0, microsecond=0)


def main() -> int:
    parser = argparse.ArgumentParser(description=AGENT["purpose"])
    parser.add_argument("--json", action="store_true", help="Emite relatorio em JSON.")
    sub = parser.add_subparsers(dest="command")
    tick = sub.add_parser("tick", help="Avalia rotina do horario.")
    tick.add_argument("--at", help="Horario HH:MM para simular.")
    route = sub.add_parser("route", help="Roteia uma tarefa por texto.")
    route.add_argument("--prompt", default="")
    sub.add_parser("day", help="Mostra grade de rotinas.")
    args = parser.parse_args()

    agent = ScheduleAgent()
    if args.command == "route":
        report = agent.route_task(args.prompt)
    elif args.command == "day":
        report = agent.plan_day()
    else:
        report = agent.tick(_parse_time(getattr(args, "at", None)))

    if args.json and args.command == "day":
        print(json.dumps(report.to_dict(), ensure_ascii=False, indent=2))
    else:
        print_report(report, as_json=args.json)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
