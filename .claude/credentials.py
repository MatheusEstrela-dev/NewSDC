#!/usr/bin/env python3
"""
Loader de credenciais locais para integracoes (Gemini, Azure, Postgres, Redis, etc.).

Precedencia de leitura:
    1. `.claude/.credentials.local.json` (texto plano, gitignored) - dev local.
    2. `.claude/.credentials.local.json.enc` (Fernet, versionado) + env `SDC_CRED_KEY`
       - caminho de nuvem para acoes autonomas do kernel.

A chave Fernet nunca fica no repo: vive na variavel de ambiente SDC_CRED_KEY
(secret do runner). Localmente o arquivo .enc e produzido a partir do plaintext
via `--encrypt`.

Uso:
    from credentials import load_credentials
    creds = load_credentials()
    redis = load_credentials("redis_prod")

CLI:
    python credentials.py --genkey            # gera uma chave Fernet nova
    python credentials.py --encrypt           # plaintext -> .enc (precisa SDC_CRED_KEY)
    python credentials.py --list              # lista secoes
    python credentials.py --section redis_prod
"""

from __future__ import annotations

import json
import os
import re
from pathlib import Path
from typing import Any


KERNEL_DIR = Path(__file__).resolve().parent
LOCAL_FILE = KERNEL_DIR / ".credentials.local.json"
ENC_FILE = KERNEL_DIR / ".credentials.local.json.enc"
EXAMPLE_FILE = KERNEL_DIR / ".credentials.example.json"
ENV_KEY = "SDC_CRED_KEY"

_COMMENT_RE = re.compile(r"^\s*#.*$", re.MULTILINE)


def _strip_comments(text: str) -> str:
    return _COMMENT_RE.sub("", text)


def _fernet():
    key = os.environ.get(ENV_KEY)
    if not key:
        raise RuntimeError(
            f"Variavel de ambiente {ENV_KEY} ausente. "
            f"Gere uma chave com `python credentials.py --genkey` e defina-a no ambiente."
        )
    try:
        from cryptography.fernet import Fernet
    except ImportError as exc:
        raise RuntimeError(
            "Pacote 'cryptography' nao instalado. Instale com `pip install cryptography`."
        ) from exc
    return Fernet(key.encode("utf-8"))


def _read_raw() -> str:
    """Retorna o JSON cru, preferindo plaintext local e caindo para o .enc cifrado."""
    if LOCAL_FILE.exists():
        return LOCAL_FILE.read_text(encoding="utf-8")
    if ENC_FILE.exists():
        token = ENC_FILE.read_bytes()
        return _fernet().decrypt(token).decode("utf-8")
    raise FileNotFoundError(
        f"Credenciais ausentes: nem {LOCAL_FILE.name} (local) nem {ENC_FILE.name} (cifrado).\n"
        f"Local: copie de {EXAMPLE_FILE.name} e preencha. "
        f"Nuvem: garanta o .enc versionado e a env {ENV_KEY}."
    )


def load_credentials(section: str | None = None) -> dict[str, Any]:
    raw = _strip_comments(_read_raw())
    data: dict[str, Any] = json.loads(raw)

    if section is None:
        return data
    if section not in data:
        raise KeyError(
            f"Secao '{section}' nao encontrada nas credenciais. "
            f"Disponiveis: {sorted(data.keys())}"
        )
    return data[section]


def encrypt_local() -> Path:
    """Cifra o plaintext local em .credentials.local.json.enc usando SDC_CRED_KEY."""
    if not LOCAL_FILE.exists():
        raise FileNotFoundError(
            f"Plaintext ausente: {LOCAL_FILE.as_posix()}. Nada para cifrar."
        )
    token = _fernet().encrypt(LOCAL_FILE.read_bytes())
    ENC_FILE.write_bytes(token)
    return ENC_FILE


def main() -> int:
    import argparse

    parser = argparse.ArgumentParser(description="Inspeciona e cifra credenciais locais.")
    parser.add_argument("--section", help="Imprime apenas uma secao do JSON.")
    parser.add_argument("--list", action="store_true", help="Lista as secoes disponiveis.")
    parser.add_argument("--genkey", action="store_true", help="Gera uma chave Fernet nova.")
    parser.add_argument("--encrypt", action="store_true", help="Cifra o plaintext local em .enc.")
    args = parser.parse_args()

    if args.genkey:
        from cryptography.fernet import Fernet

        print(Fernet.generate_key().decode("utf-8"))
        return 0

    if args.encrypt:
        try:
            dest = encrypt_local()
        except (FileNotFoundError, RuntimeError) as exc:
            print(exc)
            return 1
        print(f"Cifrado: {dest.as_posix()}")
        return 0

    try:
        data = load_credentials()
    except (FileNotFoundError, RuntimeError) as exc:
        print(exc)
        return 1

    if args.list:
        for name in sorted(data.keys()):
            print(name)
        return 0

    if args.section:
        print(json.dumps(load_credentials(args.section), ensure_ascii=False, indent=2))
        return 0

    print(json.dumps(data, ensure_ascii=False, indent=2))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
