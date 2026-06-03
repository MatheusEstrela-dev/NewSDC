#!/usr/bin/env python3
"""
Hook Stop.

Quando a sessao do Claude termina, injeta uma instrucao curta sugerindo que ele
verifique se ha fatos/decisoes/gotchas a registrar em `.claude/memory/` via
`python .claude/memory/append.py "<fato>" --type <gotcha|decision|fact>`.

A injecao acontece via stdout - o Claude le como contexto adicional antes de
encerrar.
"""

from __future__ import annotations

import json
import sys


def main() -> int:
    try:
        json.load(sys.stdin)  # consome o payload, nao usamos campos
    except Exception:
        pass

    sys.stdout.write(
        "<context-from-claude-kernel>\n"
        "Antes de encerrar: ha algo desta sessao que vale registrar em `.claude/memory/`?\n"
        "Casos comuns:\n"
        "- **gotcha** - uma armadilha que perdeu tempo (ex.: 'X so funciona se Y estiver setado').\n"
        "- **decision** - uma decisao arquitetural ou de processo (ex.: 'optamos por A em vez de B porque...').\n"
        "- **fact** - um fato volatil util (ex.: 'producao usa X hoje').\n\n"
        "Se sim, registre:\n"
        "`python .claude/memory/append.py \"<resumo curto>\" --type <gotcha|decision|fact>`\n"
        "Se nao houver nada digno, ignore esta sugestao.\n"
        "</context-from-claude-kernel>\n"
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
