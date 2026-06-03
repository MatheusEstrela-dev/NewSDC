# `.claude/memory/` - fatos volateis e decisoes datadas

Espaco para coisas que **registram um momento**, nao a estrutura do sistema. Armadilhas conhecidas, decisoes arquiteturais datadas, post-mortems curtos, gotchas que ainda valem mas podem nao valer mais em 3 meses.

## Fronteira com `.claude/docs/`

- Vai em [`.claude/docs/`](../docs/README.md) o que **descreve o sistema**: arquitetura, exemplos canonicos, referencia.
- Vai em `memory/` o que **registra um momento**: "isso quebrou", "decidimos X em <data>", "cuidado com Y", post-mortems.

Em duvida: se a informacao tem **data implicita ou risco de ficar errada**, e `memory/`. Se descreve um padrao perene, e `docs/`.

## Convencoes

- Cada arquivo deve ter **data ISO** no nome ou no topo do conteudo: `2026-05-30-redis-prefix-trap.md`, ou um cabecalho `> Registrado em 2026-05-30`.
- Quando uma armadilha for resolvida, **nao apague** - marque `RESOLVIDO em <data>` no topo e mantenha como referencia historica.
- Prefira arquivos curtos e focados (1 problema = 1 arquivo) a um `notes.md` monolitico.

## Arquivos atuais

| Arquivo | Papel |
|---|---|
| `main.py` | Entrypoint UNIX-init do modulo (chamado por `kernel.py`). |

## Backlog inicial sugerido (mover do `~/.claude/.../memory/MEMORY.md` global se relevante ao projeto)

Itens que ja existem na memoria global do usuario mas tambem cabem no projeto:

- `2026-XX-XX-redis-prefix-app-name-trap.md` - REDIS_PREFIX x APP_NAME causando keyspace isolada entre containers.
- `2026-XX-XX-outlook-corp-blocks-emdash.md` - subjects de email com `--` em vez de em-dash para passar pelo Exchange Defesa Civil.
- `2026-XX-XX-prod-app-service-sdcdefesa.md` - producao e `sdcdefesa`, nao `newsdc2027`.

Migrar quando alguem da equipe encostar nesses pontos.
