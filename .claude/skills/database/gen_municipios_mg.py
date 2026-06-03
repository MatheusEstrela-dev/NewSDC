"""
Gerador do dataset canonico dos municipios de Minas Gerais (UF=MG, codigo_uf=31).

Fontes autoritativas:
  - kelvins/municipios-brasileiros (CSV): codigo_ibge, nome, latitude, longitude
  - IBGE localidades API: mesorregiao e microrregiao por codigo_ibge

Saidas (fonte unica -> dois artefatos):
  - SDC/database/data/municipios_mg.json  (consumido pelo MunicipiosMGSeeder)
  - .claude/skills/database/municipios.sql (referencia humana, ON CONFLICT)

Uso: python .claude/skills/database/gen_municipios_mg.py
"""
import csv
import io
import json
import os
import ssl
import urllib.request

KELVINS_CSV = "https://raw.githubusercontent.com/kelvins/municipios-brasileiros/main/csv/municipios.csv"
IBGE_API = "https://servicodados.ibge.gov.br/api/v1/localidades/estados/31/municipios"
COD_UF_MG = "31"

ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", ".."))
JSON_OUT = os.path.join(ROOT, "SDC", "database", "data", "municipios_mg.json")
SQL_OUT = os.path.join(os.path.dirname(__file__), "municipios.sql")


def _get(url: str) -> bytes:
    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    ctx.verify_mode = ssl.CERT_NONE
    req = urllib.request.Request(url, headers={
        "Accept-Encoding": "identity",
        "User-Agent": "newsdc-municipios-gen/1.0",
    })
    with urllib.request.urlopen(req, context=ctx, timeout=60) as r:
        raw = r.read()
    if raw[:2] == b"\x1f\x8b":  # fallback: gzip mesmo pedindo identity
        import gzip
        raw = gzip.decompress(raw)
    return raw


def fetch_kelvins() -> dict:
    text = _get(KELVINS_CSV).decode("utf-8")
    out = {}
    for row in csv.DictReader(io.StringIO(text)):
        if row["codigo_uf"] != COD_UF_MG:
            continue
        out[row["codigo_ibge"]] = {
            "nome": row["nome"],
            "latitude": float(row["latitude"]) if row["latitude"] else None,
            "longitude": float(row["longitude"]) if row["longitude"] else None,
        }
    return out


def fetch_ibge() -> dict:
    data = json.loads(_get(IBGE_API).decode("utf-8"))
    out = {}
    for m in data:
        micro = m["microrregiao"]
        out[str(m["id"])] = {
            "nome": m["nome"],
            "microrregiao": micro["nome"],
            "mesorregiao": micro["mesorregiao"]["nome"],
        }
    return out


def build() -> list:
    kelvins = fetch_kelvins()
    ibge = fetch_ibge()
    codes = sorted(set(kelvins) | set(ibge))
    registros = []
    for cod in codes:
        k = kelvins.get(cod, {})
        i = ibge.get(cod, {})
        registros.append({
            "codigo_ibge": cod,
            "nome": i.get("nome") or k.get("nome"),  # IBGE oficial tem prioridade
            "uf": "MG",
            "regiao": "Sudeste",
            "mesorregiao": i.get("mesorregiao"),
            "microrregiao": i.get("microrregiao"),
            "latitude": k.get("latitude"),
            "longitude": k.get("longitude"),
        })
    return registros


def _sql_str(v) -> str:
    if v is None:
        return "NULL"
    if isinstance(v, (int, float)):
        return repr(v)
    return "'" + str(v).replace("'", "''") + "'"


def write_sql(registros: list) -> None:
    cols = ["codigo_ibge", "nome", "uf", "regiao", "mesorregiao", "microrregiao", "latitude", "longitude"]
    lines = [
        "-- Municipios de Minas Gerais (853) - fontes: kelvins/municipios-brasileiros + IBGE",
        "-- Gerado por .claude/skills/database/gen_municipios_mg.py. Nao editar manualmente.",
        "INSERT INTO public.municipios",
        "(codigo_ibge, nome, uf, regiao, mesorregiao, microrregiao, latitude, longitude, created_at, updated_at)",
        "VALUES",
    ]
    rows = []
    for r in registros:
        vals = ", ".join(_sql_str(r[c]) for c in cols)
        rows.append(f"({vals}, NOW(), NOW())")
    body = ",\n".join(rows)
    tail = (
        "\nON CONFLICT (codigo_ibge) DO UPDATE SET\n"
        "  nome = EXCLUDED.nome, uf = EXCLUDED.uf, regiao = EXCLUDED.regiao,\n"
        "  mesorregiao = EXCLUDED.mesorregiao, microrregiao = EXCLUDED.microrregiao,\n"
        "  latitude = EXCLUDED.latitude, longitude = EXCLUDED.longitude, updated_at = NOW();"
    )
    with open(SQL_OUT, "w", encoding="utf-8", newline="\n") as f:
        f.write("\n".join(lines) + "\n" + body + tail + "\n")


def main() -> None:
    registros = build()
    os.makedirs(os.path.dirname(JSON_OUT), exist_ok=True)
    with open(JSON_OUT, "w", encoding="utf-8", newline="\n") as f:
        json.dump(registros, f, ensure_ascii=False, indent=2)
        f.write("\n")
    write_sql(registros)
    nulos_meso = sum(1 for r in registros if not r["mesorregiao"])
    nulos_coord = sum(1 for r in registros if r["latitude"] is None)
    print(f"total registros : {len(registros)}")
    print(f"sem mesorregiao : {nulos_meso}")
    print(f"sem coordenada  : {nulos_coord}")
    print(f"json -> {JSON_OUT}")
    print(f"sql  -> {SQL_OUT}")


if __name__ == "__main__":
    main()
