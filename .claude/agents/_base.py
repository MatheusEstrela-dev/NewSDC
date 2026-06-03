#!/usr/bin/env python3
from __future__ import annotations

import json
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any


@dataclass
class AgentReport:
    agent: str
    objective: str
    summary: str
    checks: list[str] = field(default_factory=list)
    commands: list[str] = field(default_factory=list)
    handoff: list[str] = field(default_factory=list)
    risk: str = "normal"

    def to_dict(self) -> dict[str, Any]:
        return {
            "agent": self.agent,
            "objective": self.objective,
            "summary": self.summary,
            "risk": self.risk,
            "checks": self.checks,
            "commands": self.commands,
            "handoff": self.handoff,
        }


def default_roots(current_file: str) -> tuple[Path, Path, Path]:
    claude_root = Path(current_file).resolve().parents[1]
    repo_root = claude_root.parent
    app_root = repo_root / "SDC"
    return repo_root, app_root, claude_root


def render_report(report: AgentReport) -> str:
    lines = [
        f"## {report.agent}",
        f"- Objetivo: {report.objective}",
        f"- Risco: {report.risk}",
        f"- Resumo: {report.summary}",
    ]
    if report.checks:
        lines.append("- Checklist:")
        lines.extend(f"  - {item}" for item in report.checks)
    if report.commands:
        lines.append("- Comandos sugeridos:")
        lines.extend(f"  - `{item}`" for item in report.commands)
    if report.handoff:
        lines.append("- Handoff:")
        lines.extend(f"  - {item}" for item in report.handoff)
    return "\n".join(lines)


def print_report(report: AgentReport, as_json: bool = False) -> None:
    if as_json:
        print(json.dumps(report.to_dict(), ensure_ascii=False, indent=2))
    else:
        print(render_report(report))


def agent_action(manifest: dict[str, Any], slug: str) -> dict[str, Any] | None:
    for function in manifest.get("functions", []):
        if function.get("slug") == slug or function.get("name") == slug:
            return function
    return None
