#!/usr/bin/env python3
"""
Hook UserPromptSubmit.

Le o JSON do evento via stdin, extrai o `prompt`, roda o detector de gatilhos do
kernel e imprime o contexto casado no stdout (que vira `additionalContext` para
o Claude).

Configurar em `.claude/settings.local.json`:
    {
      "hooks": {
        "UserPromptSubmit": [
          {
            "matcher": "",
            "hooks": [
              {"type": "command", "command": "python ${CLAUDE_PROJECT_DIR}/.claude/hooks/on_user_prompt.py"}
            ]
          }
        ]
      }
    }
"""

from __future__ import annotations

import json
import os
import subprocess
import sys
from pathlib import Path


def main() -> int:
    try:
        payload = json.load(sys.stdin)
    except Exception:
        return 0

    prompt = (payload.get("prompt") or "").strip()
    if not prompt:
        return 0

    project_dir = os.environ.get("CLAUDE_PROJECT_DIR") or os.getcwd()
    kernel = Path(project_dir) / ".claude" / "kernel.py"
    if not kernel.exists():
        return 0

    try:
        result = subprocess.run(
            [sys.executable, str(kernel), "--detect", prompt],
            capture_output=True,
            text=True,
            timeout=10,
            check=False,
        )
    except Exception:
        return 0

    output = (result.stdout or "").strip()
    if output:
        sys.stdout.write(output)
        sys.stdout.write("\n")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
