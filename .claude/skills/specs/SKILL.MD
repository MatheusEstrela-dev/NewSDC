---
name: specs
description: Use esta skill para escrever, revisar ou executar especificacoes e planos do NewSDC, incluindo requisitos, decisoes de arquitetura e criterios de verificacao.
---

# Specs

## Inicializacao do kernel

No inicio da conversa, classifique a mensagem:

```bash
python .claude/kernel.py --detect "<mensagem-do-usuario>"
```

O hook `UserPromptSubmit` executa essa classificacao automaticamente no Claude Code.

## Quando usar

Use esta skill para elaborar ou consultar specs, planos de implementacao, requisitos, criterios de aceite e decisoes de arquitetura.

## Fluxo

1. Procure contexto existente em `docs/superpowers/specs/`, `docs/superpowers/plans/` e `.claude/commands/plans/`.
2. Separe claramente comportamento esperado, limites e verificacao.
3. Ao implementar um plano, acompanhe os passos executados e valide o resultado.
