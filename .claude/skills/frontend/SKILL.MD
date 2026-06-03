---
name: frontend
description: Use esta skill para implementar ou diagnosticar frontend Vue do NewSDC, incluindo Inertia, componentes, composables, Tailwind, Vite e comportamento responsivo.
---

# Frontend

## Inicializacao do kernel

No inicio da conversa, classifique a mensagem:

```bash
python .claude/kernel.py --detect "<mensagem-do-usuario>"
```

O hook `UserPromptSubmit` executa essa classificacao automaticamente no Claude Code.

## Quando usar

Use esta skill para tarefas em `SDC/resources/js/`, paginas Vue, componentes, composables, layouts, Tailwind, Inertia e Vite.

## Fluxo

1. Localize a pagina, o componente e os composables envolvidos.
2. Reuse componentes e padroes visuais existentes.
3. Preserve estados de carregamento, erro e comportamento responsivo.
4. Valide com o build do Vite e testes focados quando existirem.

## Referencias

- Para arquitetura e inicializacao do frontend, leia [01 - Frontend.md](01%20-%20Frontend/01%20-%20Frontend.md).
- Para decisoes de abordagem, leia [02 - abordagem.md](02%20-%20Abordagem/02%20-%20abordagem.md).
