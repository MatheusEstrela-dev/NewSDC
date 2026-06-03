---
name: database
description: Use esta skill para tarefas de banco de dados do NewSDC, incluindo schema, migrations, indices, consultas SQL, Eloquent e compatibilidade PostgreSQL/MySQL.
---

# Database

## Inicializacao do kernel

No inicio da conversa, classifique a mensagem:

```bash
python .claude/kernel.py --detect "<mensagem-do-usuario>"
```

O hook `UserPromptSubmit` executa essa classificacao automaticamente no Claude Code.

## Quando usar

Use esta skill para migrations, alteracoes de schema, indices, chaves estrangeiras, consultas, diagnostico de persistencia e compatibilidade entre bancos.

## Fluxo

1. Inspecione migrations existentes, models e consultas afetadas.
2. Preserve dados existentes e avalie reversibilidade da migration.
3. Considere PostgreSQL e MySQL quando houver SQL especifico.
4. Valide com `php artisan migrate:status`, testes focados e consultas somente leitura quando aplicavel.
