---
name: api
description: Use esta skill para criar, alterar, diagnosticar ou documentar APIs HTTP do NewSDC, incluindo endpoints REST, autenticacao Sanctum e Swagger/OpenAPI.
---

# API

## Inicializacao do kernel

No inicio da conversa, classifique a mensagem:

```bash
python .claude/kernel.py --detect "<mensagem-do-usuario>"
```

O hook `UserPromptSubmit` executa essa classificacao automaticamente no Claude Code.

## Quando usar

Use esta skill para tarefas sobre endpoints, controllers de API, payloads JSON, autenticacao por token, Swagger, OpenAPI e `l5-swagger`.

## Fluxo

1. Identifique a rota, o controller e o contrato HTTP afetados.
2. Verifique autenticacao, validacao, status HTTP e formato da resposta.
3. Atualize as anotacoes OpenAPI quando o contrato publico mudar.
4. Gere a documentacao quando necessario com `php artisan l5-swagger:generate`.

## Referencias

- Para anotacoes e configuracao Swagger, leia [01 - api.md](01%20-%20Swagger/01%20-%20api.md).
- Para o fluxo de geracao e visualizacao, leia [01 - Estrutural.md](02%20-%20Estrutura/01%20-%20Estrtural.md).
