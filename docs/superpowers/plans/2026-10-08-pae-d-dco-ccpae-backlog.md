# Backlog de passagem — PAE fase D: DCO e CCPAE

**Atualizado em:** 2026-10-08

**Branch:** `codex/pae-dco-gmg83`

**Base:** `origin/dev` no commit `5e6af26aa`
**Worktree:** `C:\Users\x24679188\.codex\worktrees\pae-dco-gmg83\NewSDC`

## Estado real da entrega

- As fases A (prazos/CCPAE), B (admissibilidade/comunicações) e C (ficha do item 2 do Anexo B) estão integradas em `origin/dev` até `5e6af26aa`.
- A fase D foi definida e aprovada pelo usuário, mas **nenhum código de produto da fase D foi implementado**. A Task 1 do plano ainda não começou.
- A spec e o plano abaixo estão escritos na worktree e aprovados pelo usuário. Eles ainda não foram commitados. O `AGENTS.md` exige que spec e plano da mesma feature sejam versionados juntos; este backlog pode entrar no mesmo commit documental.
- A branch foi criada a partir de `origin/dev`, sem mudanças herdadas do checkout principal. A worktree `dev` contém uma modificação RAT preexistente, fora deste escopo.

## Decisões do usuário e fontes

1. Priorizar **DCO e requisito de emissão do CCPAE** antes do restante do Anexo B. O roteiro preliminar da fase A chamava a calculadora de evacuação de fase D; a prioridade foi reordenada nesta conversa.
2. Registrar arquivo da DCO, competência anual, data e número SEI. A CEDEC decide de forma fundamentada se a DCO se aplica ao protocolo.
3. Impedir emissão do CCPAE sem DCO positiva vigente quando aplicável. Mostrar pendência anual e não conformidade após a emissão, **sem revogação automática**.
4. O usuário aprovou o desenho, a spec e o plano, e escolheu execução nativa neste chat. A intenção atual é entregar este backlog a outro agente para revisão e continuidade.

Fontes normativas: [Resolução GMG nº 83/2024, arts. 137 e 139](https://liferay.meioambiente.mg.gov.br/documents/117662/7003603/ResolucaoGMG_Nr83-2024/f6263034-1002-0587-f566-aeb642a1488f?t=1723499015768&version=1.0) e [Resolução ANM nº 95/2022, arts. 44 e 45](https://www.gov.br/anm/pt-br/assuntos/barragens/legislacao/resolucao-no-95-2022.pdf). A regra ANM prevê DCO anual entre 1º e 30 de junho; o SDC não deve inferir aplicabilidade só pelo nome/tipo do empreendimento.

Documentos da fase:

- Spec: `docs/superpowers/specs/2026-10-08-pae-d-dco-ccpae-design.md`
- Plano detalhado, com arquivos, interfaces, testes e ordem das tarefas: `docs/superpowers/plans/2026-10-08-pae-d-dco-ccpae.md`

## Backlog em ordem

- [ ] **Task 1 — Ciclo e schema:** escrever teste que falha, implementar `PaeDcoCiclo`, migration principal de avaliações/documentos e referências de evidência no CCPAE; manter versões históricas e FKs nullable para certificados legados.
- [ ] **Task 2 — Registro auditável:** serviço transacional para decisão de aplicabilidade e upload de DCO PDF com SEI, competência, resultado, data, idempotência e limpeza do arquivo quando a transação falhar.
- [ ] **Task 3 — Emissão:** conferir a avaliação vigente e a última DCO válida na data de emissão dentro do lock do protocolo. Congelar IDs da avaliação/documento em `pae_ccpae`; falha não pode criar status, certificado, comunicação ou outbox.
- [ ] **Task 4 — HTTP e interface:** Requests, controller, rotas com `pae.protocolos.validar` para decisão/upload e `pae.protocolos.view` para consulta/download; página DCO ligada às ações do protocolo.
- [ ] **Task 5 — Acompanhamento anual:** mostrar situação por competência, alertas e histórico na página e listagem sem N+1; modal de emissão mostra o bloqueio, com validação final sempre no servidor.
- [ ] **Task 6 — Verificação:** migration e suíte PAE em banco isolado, build, navegador, ACL, erro de storage e leitura de arquivo entre réplicas; revisão final e commits atômicos. Integração em `dev` só após a feature estar íntegra.

O plano detalha testes importantes: 30/06 versus 01/07; DCO negativa posterior à positiva; documento apresentado depois da data da emissão; concorrência; certificado legado; usuário sem permissão; documento de outro protocolo; upload em uma réplica e download em outra.

## Ambiente de teste já preparado

- Banco PostgreSQL local `pae_d_test` criado no container `newsdc_dev_db` e migrado até a fase C. Ele é **exclusivo para testes desta fase**; não usar o banco da prévia.
- Baseline verificada: `PaeProtocoloNumeracaoTest` passou com **5 testes e 5 asserções** em PHP 8.4.22.
- Runner local ignorado pelo Git: `.superpowers/sdd/2026-10-08-pae-d-dco-ccpae/run-phpunit.ps1`. Exemplo, na raiz desta worktree:

```powershell
& .superpowers/sdd/2026-10-08-pae-d-dco-ccpae/run-phpunit.ps1 --filter=PaeProtocoloNumeracaoTest
```

- O runner usa container descartável `newsdc-pae-d-test-runtime:local`, monta o código desta worktree, regenera o autoload autoritativo dentro do container e conecta ao banco `pae_d_test`. O PHP 8.1 do host não atende ao Laravel do projeto. Chamadas Docker exigiram permissão elevada nesta sessão.
- `.env` local foi copiado sem exibir valores. `SDC/vendor` e `SDC/node_modules` são junctions para pastas do checkout principal que não têm executáveis utilizáveis; o runner PHP usa o vendor da imagem. Para o build frontend ainda será necessário preparar dependências válidas na worktree.
- Ledger temporário, também ignorado pelo Git: `.superpowers/sdd/2026-10-08-pae-d-dco-ccpae/progress.md`. Ainda não há nenhuma linha `Task N: complete`.

## Regras de entrega

- Não editar `DOC.MD`; não usar emoji dentro do código; seguir DRY/SOLID e as instruções de `AGENTS.md`. `ENGINEER.MD` e `PAPIROS.MD` não foram encontrados neste checkout.
- Zen/Gemini/Notion MCP não estão disponíveis nesta sessão; o usuário já autorizou a exceção para escrever a documentação PAE diretamente.
- Consolidar ajustes de schema na migration principal desta fase. Testes criados ou alterados durante a implementação ficam fora dos commits, conforme `AGENTS.md`.
- Commit documental sugerido: `📝 docs(pae): especifica fase D da DCO e CCPAE` com spec, plano e este backlog. Depois da implementação completa e verificada, commit de feature: `✨ feat(pae): exige DCO aplicável na emissão do CCPAE`.
- Não misturar a modificação RAT da worktree `dev` nem outras alterações do checkout principal. Não afirmar que as quatro réplicas estão sincronizadas sem verificar o código servido e o acesso compartilhado ao arquivo DCO.
