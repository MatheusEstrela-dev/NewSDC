#!/usr/bin/env python3
"""
Catalogo de integracoes externas do projeto (Notion, Azure, Postgres, Redis,
SMTP, Gemini, Telegram, Power BI).

Cada integracao declara:
- name: identificador curto
- triggers: palavras-chave que indicam intencao do usuario sobre essa integracao
- credentials_section: chave em `.credentials.local.json` (se aplicavel)
- mcp_tools: prefixo de tools MCP relacionadas (se aplicavel)
- cli: ferramentas de linha de comando (se aplicavel)
- how_to_use: instrucao curta de como usar a integracao
- when_to_use: quando aplicar

Uso programatico:
    from integrations import INTEGRATIONS, detect_integrations
    matched = detect_integrations("documenta isso no notion")
"""

from __future__ import annotations

import re
from typing import Any


INTEGRATIONS: list[dict[str, Any]] = [
    {
        "name": "notion",
        "triggers": ["notion", "documentar no notion", "pagina notion", "database notion"],
        "credentials_section": "notion_prod",
        "mcp_tools": "mcp__claude_ai_Notion__*",
        "cli": [],
        "how_to_use": (
            "Use as tools MCP do Notion (notion-search, notion-fetch, notion-create-pages, "
            "notion-update-page). A chave de integracao (`ntn_*`) ja esta vinculada via OAuth "
            "no claude.ai - voce nao precisa passar o token manualmente. Para criar paginas/dbs "
            "diretos via API, use a chave de `notion_prod.key`."
        ),
        "when_to_use": (
            "Sempre que o usuario pedir para 'documentar no Notion', 'criar pagina', "
            "'atualizar database Notion' ou referenciar workspace Notion."
        ),
    },
    {
        "name": "azure",
        "triggers": [
            "azure", "az login", "key vault", "app service", "appservice",
            "storage account", "container registry", "acr", "managed identity",
            "service principal", "sp-newsdc",
        ],
        "credentials_section": "azure_prod",
        "mcp_tools": "",
        "cli": ["az"],
        "how_to_use": (
            "Use `az` CLI. Credenciais em `azure_prod`: subscription_id, tenant_id, client_id, "
            "client_secret (SP `sp-newsdc-claude`). Login com `az login --service-principal -u <client_id> "
            "-p <client_secret> --tenant <tenant_id>`. Resource group padrao: `Defesa_Civil`. "
            "App Service prod: `sdcdefesa`. ACR: `acrdefesacivil`. Storage: `newsdc`."
        ),
        "when_to_use": (
            "Tarefas envolvendo deploy, ACR, App Service, Storage, Managed Identity, "
            "Key Vault, role assignment ou qualquer recurso em `Defesa_Civil`."
        ),
    },
    {
        "name": "postgres_prod",
        "triggers": [
            "postgres", "postgresql", "pgsql", "banco prod", "database prod",
            "newsdc.postgres", "azure database for postgres",
        ],
        "credentials_section": "database_prod",
        "mcp_tools": "",
        "cli": ["psql"],
        "how_to_use": (
            "Conexao em `database_prod`: host `newsdc.postgres.database.azure.com`, db `sdc`, "
            "user `newsdc`, sslmode `require`. NUNCA execute DDL/DML destrutivo sem confirmacao "
            "explicita do usuario. Para leitura, prefira `psql -c '...'` com timeout curto."
        ),
        "when_to_use": (
            "Quando o usuario pedir diagnostico em prod ou consulta de dados reais. "
            "Para schema, prefira ler `SDC/database/migrations/`."
        ),
    },
    {
        "name": "redis_prod",
        "triggers": [
            "redis prod", "azure redis", "newsdc-redis", "redis.cache.windows.net",
            "cache prod", "queue prod",
        ],
        "credentials_section": "redis_prod",
        "mcp_tools": "",
        "cli": ["redis-cli"],
        "how_to_use": (
            "Conexao em `redis_prod`: host `newsdc-redis.redis.cache.windows.net:6379`, "
            "sslmode `require`. Lembrar da armadilha REDIS_PREFIX x APP_NAME (keyspace isolada "
            "entre containers). Ver memory/ se houver registro detalhado."
        ),
        "when_to_use": (
            "Diagnostico de filas/cache em prod, verificacao de keyspace, troubleshooting de "
            "worker desincronizado do app."
        ),
    },
    {
        "name": "smtp",
        "triggers": ["smtp", "envio de email", "enviar email", "prodemge", "defesa_civil_sdc"],
        "credentials_section": "smtp_prod",
        "mcp_tools": "",
        "cli": [],
        "how_to_use": (
            "Credenciais em `smtp_prod`: host `smtpprdm.prodemge.gov.br:587` TLS, "
            "from `sdc@defesacivil.mg.gov.br`. Lembrar: Outlook corp bloqueia em-dash em "
            "subjects - use ASCII puro (-) para nao cair no filtro do Exchange Defesa Civil."
        ),
        "when_to_use": (
            "Testar/debugar entrega de emails do SDC (verification, notification, OTP). "
            "Conferir templates em Vue+Atomic (nao Blade)."
        ),
    },
    {
        "name": "gemini",
        "triggers": ["gemini", "google ai", "vertex ai", "ia generativa", "llm google"],
        "credentials_section": "ai_gemini",
        "mcp_tools": "",
        "cli": [],
        "how_to_use": (
            "Credenciais em `ai_gemini`: api_key, model `gemini-2.5-flash-lite`, max_tokens 4096. "
            "Usar via `google-generativeai` Python ou direto via HTTPS API do Gemini."
        ),
        "when_to_use": (
            "Quando o usuario quiser delegar uma tarefa de IA para o Gemini (resumo, traducao, "
            "geracao de docs). Tambem pode rodar via `zen` MCP se conectado."
        ),
    },
]


def _word_pattern(term: str) -> re.Pattern[str]:
    return re.compile(rf"(?<![a-z0-9_])\.?{re.escape(term.lower())}(?![a-z0-9_])")


def detect_integrations(prompt_text: str) -> list[dict[str, Any]]:
    """Retorna as integracoes cujos triggers casam com o texto do prompt."""
    text = prompt_text.lower()
    matched: list[dict[str, Any]] = []
    for integration in INTEGRATIONS:
        hits: list[str] = []
        for trigger in integration.get("triggers", []):
            if _word_pattern(trigger).search(text):
                hits.append(trigger)
        if hits:
            entry = dict(integration)
            entry["matched_triggers"] = hits
            matched.append(entry)
    return matched


def render_integration(integration: dict[str, Any]) -> str:
    lines: list[str] = [f"### Integracao: {integration['name']}"]
    lines.append(f"- Gatilhos casados: {', '.join(integration.get('matched_triggers', []))}")
    if integration.get("credentials_section"):
        lines.append(f"- Credencial: secao `{integration['credentials_section']}` em `.credentials.local.json`")
        lines.append(f"  Carregar: `python .claude/credentials.py --section {integration['credentials_section']}`")
    if integration.get("mcp_tools"):
        lines.append(f"- MCP tools: `{integration['mcp_tools']}`")
    if integration.get("cli"):
        lines.append(f"- CLI: {', '.join(f'`{cmd}`' for cmd in integration['cli'])}")
    if integration.get("how_to_use"):
        lines.append(f"- Como usar: {integration['how_to_use']}")
    if integration.get("when_to_use"):
        lines.append(f"- Quando: {integration['when_to_use']}")
    return "\n".join(lines)


def main() -> int:
    import argparse
    import json

    parser = argparse.ArgumentParser(description="Catalogo de integracoes do projeto.")
    parser.add_argument("--detect", help="Texto do prompt para casar integracoes.")
    parser.add_argument("--json", action="store_true", help="Emite resultado em JSON.")
    parser.add_argument("--list", action="store_true", help="Lista todas as integracoes.")
    args = parser.parse_args()

    if args.detect is not None:
        matched = detect_integrations(args.detect)
        if args.json:
            print(json.dumps(matched, ensure_ascii=False, indent=2))
        else:
            for integration in matched:
                print(render_integration(integration))
                print()
        return 0

    if args.list or not args.detect:
        if args.json:
            print(json.dumps(INTEGRATIONS, ensure_ascii=False, indent=2))
        else:
            for integration in INTEGRATIONS:
                print(f"- {integration['name']}: {', '.join(integration.get('triggers', []))}")
        return 0

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
