# `.claude/docs/` - referencia estavel

Documentos de longo prazo que descrevem **como o sistema e**. Coisas que mudam raramente e cuja leitura orienta decisoes de arquitetura, modelagem ou integracao.

## Fronteira com `.claude/memory/`

- Vai em `docs/` o que **descreve o sistema**: arquitetura, exemplos canonicos, referencia de credenciais, papiros, guias de processo.
- Vai em [`.claude/memory/`](../memory/README.md) o que **registra um momento**: armadilhas conhecidas, decisoes datadas, gotchas, post-mortems, fatos volateis que podem mudar amanha.

Em duvida: se voce escreveria a mesma coisa daqui a 6 meses, e `docs/`. Se voce esta registrando "isso quebrou ontem, cuidado", e `memory/`.

## Arquivos atuais

| Arquivo | Papel |
|---|---|
| [`ARCHITECTURE.md`](ARCHITECTURE.md) | Documento mestre de arquitetura do NewSDC (stack, modulos, backend/frontend, DB, deploy). |
| [`EXEMPLOS_DECRETACOES.md`](EXEMPLOS_DECRETACOES.md) | Exemplos canonicos de decretos de calamidade. |
| [`CREDENCIAIS.md`](CREDENCIAIS.md) | Onde ficam os segredos, como carregar, checklist de rotacao. |
| `main.py` | Entrypoint UNIX-init do modulo (chamado por `kernel.py`). |

## Itens a (eventualmente) migrar para `memory/`

Quando algum doc abaixo virar "vivo demais" (mudar a cada sprint), considere mover para `memory/`:

- Nenhum no momento. Reavaliar periodicamente.
