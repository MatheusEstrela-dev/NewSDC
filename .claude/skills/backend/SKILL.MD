---
name: backend
description: Use esta skill para implementar ou diagnosticar backend Laravel no NewSDC, incluindo modulos de dominio, controllers, services, DTOs, Eloquent e regras DDD.
---

# Backend

## Inicializacao do kernel

No inicio da conversa, classifique a mensagem:

```bash
python .claude/kernel.py --detect "<mensagem-do-usuario>"
```

O hook `UserPromptSubmit` executa essa classificacao automaticamente no Claude Code.

## Quando usar

Use esta skill para tarefas em `SDC/app/`, rotas Laravel, services, DTOs, models, providers e regras de dominio.

## Fluxo

1. Localize o modulo em `SDC/app/Modules/{Modulo}/` e siga a estrutura existente.
2. Preserve as responsabilidades: Request valida, Controller delega, Service tem a logica, Model persiste, Resource serializa.
3. Verifique validacao (FormRequest), autorizacao (Policy/`can`) e efeitos persistidos.
4. Execute testes focados e validacao de sintaxe PHP.

Banco e PostgreSQL (`pgsql`). Nao existe UseCase nem Repository neste projeto.

## Referencias

- Padrao pratico, Quick Reference, boilerplate e erros comuns (comecar aqui): leia [01 - Padrao.md](03%20-%20Padrao%20Pratico/01%20-%20Padrao.md).
- Conceitos de dominio DDD (teoria): leia [01 - Domain.md](01%20-%20Domain%20DDD/01%20-%20Domain.md).
- Arquitetura em camadas do backend: leia [01 - Estrutura.md](02%20-%20Estrutura/01%20-%20Estrutura.md).
