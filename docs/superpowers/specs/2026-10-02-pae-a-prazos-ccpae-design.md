# PAE -- Subprojeto A: Base de prazos, fluxo e CCPAE

**Data:** 2026-10-02 | **Branch:** `feat/pae-gmg83` | **Status:** desenho aprovado, aguardando revisão do spec

**Fonte normativa:** Resolução GMG n. 83/2024 (`docs/ResolucaoGMG_Nr83-2024.pdf`)
**Substitui, para este escopo:** as seções 5.2 (G2) e 8.1 (G8, parte de emissão) de `docs/plans/plano-melhoria-pae-v2.md`

---

## 1. Contexto e objetivo

O módulo PAE já tem a estrutura de prazos no schema, mas nada a preenche:

- `pae_protocolos.limite_analise`, `ccpae` e `ccpae_venc` são lidos pela tela, pelo CSV e pelos eventos do Outbox, mas nenhum código os grava. Por isso o card "Vencidos" mostra sempre zero.
- As tabelas `pae_ccpae` e `pae_dilacoes` existem, mas não têm model nem uso.
- O único prazo codificado é o de 30 dias da notificação, escrito direto no código em 4 pontos de `PaeNotificacaoService`.

**Objetivo:** a CEDEC passa a ver, para cada protocolo, os prazos legais corretos (Arts. 7, 9 e 11) e a vigência do CCPAE (Arts. 4 e 5), calculados num único lugar. Também são corrigidos os defeitos de fluxo que distorcem esses números.

**Sucesso:**

- Todo protocolo ativo tem `limite_analise` preenchido, com a origem identificada (real ou estimada).
- O card "Vencidos" reflete a realidade.
- O CCPAE emitido gera um registro em `pae_ccpae` com o vencimento correto.
- O 3º ciclo vencido gera uma sinalização para a CEDEC decidir, sem suspensão automática.
- O Ranking (consumidor dos eventos do Outbox) continua recebendo os mesmos 4 eventos, agora com `prazo_em` preenchido.

## 2. Regras legais adotadas

| Regra | Fonte | Decisão de implementação |
|---|---|---|
| Empreendedor protocola na CEDEC em até 10 dias úteis da notificação da FEAM | Art. 7, caput e §2 | Dias úteis = segunda a sexta, menos os feriados de `config/feriados.php`. Gera apenas a marca "protocolado fora do prazo", sem bloquear |
| CEDEC decide em 300 dias, contados da notificação da FEAM | Art. 9 | Dias corridos. A contagem **pausa** enquanto há diligência aberta (da emissão da notificação até a devolutiva) |
| Diligência com prazo máximo de 30 dias | Art. 11 | Prazo da notificação = emissão + 30 dias + dilações aprovadas. O tempo da dilação também fica fora dos 300 dias |
| Atualização do PAE a cada 3 anos | Arts. 4 e 5 | Empreendimento novo: data da LO + 3 anos. Empreendimento com LO anterior: emissão do CCPAE + 3 anos |
| Suspensão e revogação "a definir de acordo com o caso concreto" | Art. 139 | O sistema nunca suspende sozinho. O 3º ciclo vencido gera sinalização, e a decisão é da CEDEC |

**Decisões da CEDEC registradas nesta sessão (2026-10-02):**

1. 3º ciclo vencido: sinalizar, não suspender. A renovação automática para no 3º ciclo, e a emissão manual não tem limite.
2. 300 dias: corridos, pausando na diligência.
3. 10 dias úteis: segunda a sexta + feriados em configuração.
4. Protocolos legados sem data da FEAM: estimar pela `dt_entrada`, com a marca de "estimada".
5. Dilação: estende o prazo da notificação, não os 300 dias da CEDEC.
6. Vigência do CCPAE: registrar a data da LO quando o empreendimento for novo.
7. Código do CCPAE: informado pelo analista (vem do documento oficial), com unicidade garantida.

## 3. Abordagem

O prazo é **calculado por classes puras, gravado no protocolo e recalculado quando algo muda**.

As alternativas descartadas:

- **Calcular na hora (accessor):** impede filtrar e contar em SQL, o que quebra as estatísticas e a paginação.
- **Tabela de eventos de prazo** (`pae_sla_snapshots` do plano v2): duplica a `PaeTimeline`.

O desenho segue o padrão do projeto (`.claude/skills/backend/03 - Padrao Pratico/01 - Padrao.md`): Request, Controller fino, DTO, Service, Model. Foram copiados dois padrões que já existem no código:

- **Tdap** (`app/Modules/Tdap/Support/VigenciaAta`): cálculo de prazo e vigência em classe pura com teste unitário.
- **Demandas** (`app/Modules/Demandas/Domain/Workflows/DemandaWorkflow` + `Guards`): máquina de estados com bloqueio de transição.

## 4. Dados

Uma migration de ajuste, consolidada para todo o subprojeto: `database/migrations/2026_10_02_120000_ajusta_pae_prazos_ccpae.php`. As migrations de criação já aplicadas não são editadas.

**`pae_protocolos`**

- `dt_notificacao_feam` date null: data da notificação da FEAM ao empreendedor (Arts. 7 e 9).
- `dt_notificacao_feam_estimada` boolean default false: true quando o valor foi estimado no backfill.
- Default de `status`: `'NOVO'` passa para `'novo'`, que é o valor real do enum `PaeProtocoloStatus`.
- Backfill: para cada protocolo com `dt_notificacao_feam` nulo, grava `dt_notificacao_feam = dt_entrada` e `dt_notificacao_feam_estimada = true`, depois recalcula `limite_analise` pela mesma regra da seção 5. O backfill é idempotente: só toca linhas com `dt_notificacao_feam` nulo.

**`pae_dilacoes`**

- `pae_notificacao_id` bigint null, FK para `pae_notificacoes`, com índice. A dilação passa a pertencer a uma notificação. `protocolo_id` continua, para consulta direta.

**`pae_ccpae`**

- `dt_licenca_operacao` date null: preenchida só para empreendimento novo (Art. 4).
- `emitido_por` bigint null, FK para `users`.

**Models novos** em `app/Modules/Pae/Models/`:

- `PaeCcpae` (tabela `pae_ccpae`)
- `PaeDilacao` (tabela `pae_dilacoes`)

**Relações novas:**

- `PaeProtocolo::ccpaes()`
- `PaeProtocolo::ccpaeVigente()`
- `PaeNotificacao::dilacoes()`

**Mantidas:** as colunas planas `pae_protocolos.ccpae` e `ccpae_venc` continuam e são preenchidas na emissão, porque a listagem, o CSV e o `EmpreendimentoResource` já as leem.

**Fora do escopo:** a `pae_datas_ciclos`, tabela legada sem uso, não muda.

## 5. Classes de cálculo puras

Sem acesso a banco, entrada e saída em Carbon e escalares, todas com teste unitário.

| Classe | Local | Responsabilidade |
|---|---|---|
| `CalendarioDiasUteis` | `app/Support/Calendario/` (compartilhada) | `adicionarDiasUteis(data, n)` e `ehDiaUtil(data)`. Usa os feriados de `config/feriados.php` (nacionais, MG e BH, por ano) |
| `PrazoProtocolo` | `app/Modules/Pae/Support/` | Limite = data da FEAM + 10 dias úteis. `foraDoPrazo(dtEntrada)` |
| `PrazoNotificacao` | `app/Modules/Pae/Support/` | Vencimento = emissão + 30 + soma das dilações aprovadas. Constante única `PRAZO_DIAS = 30`, que substitui as 4 ocorrências escritas no código |
| `PrazoAnalise` | `app/Modules/Pae/Support/` | Limite = data da FEAM + 300 + dias pausados. Também devolve a situação (`ok`, `proximo`, `vencido`, `pausado`, `sem_data`) |
| `VigenciaCcpae` | `app/Modules/Pae/Support/` | Vencimento = (empreendimento novo ? data da LO : emissão) + 3 anos |

**Dias pausados (`PrazoAnalise`).** Cada notificação define um intervalo que começa em `dt_notificacao` e termina no primeiro destes que existir:

1. a `dt_devolutiva`;
2. a `dt_notificacao` da notificação seguinte, porque a renovação automática deixa a anterior sem devolutiva;
3. hoje.

Os dias pausados são a **união** desses intervalos, sem contar duas vezes os trechos sobrepostos. Enquanto houver intervalo aberto, a situação é `pausado` e o limite é projetado considerando que a pausa termina hoje. O comando diário mantém essa projeção atualizada.

**Situação**, movendo para o servidor o critério que a tela usa hoje em `PaeProtocolosIndexTemplate.vue:329-337`, sem mudar o comportamento:

- `ok` para os status aprovado, ccpae, ativo_3_anos, reprovado e revogado;
- `vencido` quando o limite é anterior a hoje;
- `proximo` quando faltam 10 dias ou menos;
- `pausado` quando há diligência aberta;
- `sem_data` quando não há data da FEAM.

## 6. Máquina de estados e guards

**`app/Modules/Pae/Domain/Workflows/PaeProtocoloWorkflow`**

- `transitar(protocolo, novoStatus, user, obs)`: abre uma transação com `lockForUpdate`, valida pelo enum, executa os guards e grava status, `PaeTramitacao` e `PaeTimeline`. Mantém a publicação de `ParecerConcluidoV1` na mesma transação, como hoje.
- `conduzirAte(protocolo, alvo, user)`: segue o menor caminho da máquina numa transação só (busca em largura, como em `DemandaWorkflow::caminho`).
- **Validação para CCPAE:** a regra que hoje está solta em `PaeProtocoloService::changeStatus` (`:185-202`) vira um método explícito do workflow, com as mesmas origens permitidas. O desarquivamento automático continua igual.
- `PaeProtocoloService::changeStatus()` passa a delegar ao workflow e mantém a assinatura, para não quebrar os chamadores.

**`app/Modules/Pae/Domain/Contracts/GuardaTransicaoPae`**

- Interface `check(PaeProtocolo, PaeProtocoloStatus, ContextoTransicao): void`, que lança `TransicaoProibidaException`.
- `Domain/ContextoTransicao` é um objeto de valor imutável, criado a cada chamada, com a origem da transição (`manual` ou `emissao_ccpae`). Não guarda estado no container, o que é seguro no Octane mesmo com os services do PAE registrados como singleton em `PaeServiceProvider.php:32-35`.
- Guard do subprojeto A: `Domain/Guards/ExigeEmissaoCcpae`. A transição para CCPAE só é aceita com `ContextoTransicao::emissaoCcpae()`, que só `PaeCcpaeService::emitir` cria. A troca genérica de status para CCPAE é recusada com mensagem orientando a usar a emissão.
- O subprojeto B (admissibilidade) acrescenta seu guard por esse mesmo contrato.

**Correção do `atribuir()`.** Hoje `PaeProtocoloService.php:301-305` grava `NOTIFICACAO` direto, sem passar pela máquina. Passa a usar `conduzirAte(NOTIFICACAO)`. O resultado na tela é o mesmo, cada passo gera tramitação, e um guard que bloqueie no meio desfaz tudo.

**`app/Modules/Pae/Domain/Exceptions/TransicaoProibidaException`.** O controller converte a exceção em `ValidationException` na chave `status`, preservando a mensagem que a tela já exibe.

**Eventos do Outbox:** os 4 atuais (`ProtocoloEnviadoV1`, `FormularioValidadoV1`, `RevisaoAceitaV1`, `ParecerConcluidoV1`) não mudam de nome nem de versão, e `prazo_em` passa a sair preenchido. Nenhum evento novo no A. `CcpaeEmitidoV1` fica para o subprojeto da comunicação à FEAM e à COMPDEC (Art. 134).

## 7. Serviços

**`app/Modules/Pae/Services/PaePrazoService`**

- `recalcular(PaeProtocolo)`: grava `limite_analise` usando `PrazoAnalise` e as notificações da análise.
- Chamado em:
  1. data da FEAM informada ou alterada;
  2. notificação emitida;
  3. devolutiva registrada;
  4. dilação registrada;
  5. comando diário, para protocolos com diligência aberta.
- `situacao(PaeProtocolo)`: devolve a situação de `PrazoAnalise` para a listagem.

**`app/Modules/Pae/Services/PaeCcpaeService`**

- `emitir(PaeProtocolo, EmitirCcpaeDTO, User)`. O DTO tem `codigo`, `dt_emissao`, `empreendimento_novo` e `dt_licenca_operacao`, esta obrigatória se o empreendimento for novo.
- Em uma transação:
  1. cria o `PaeCcpae` com o vencimento de `VigenciaCcpae`;
  2. preenche `pae_protocolos.ccpae` e `ccpae_venc`;
  3. transita para CCPAE pelo workflow, pela regra de validação, com `ContextoTransicao::emissaoCcpae()`;
  4. registra na timeline.
- A unicidade do código é garantida pelo índice `idx_ccpae_codigo_unique`, que já existe, e pela validação do FormRequest.

**`PaeNotificacaoService` (alterado)**

- `registrarDilacao(PaeNotificacao, dias, justificativa, User)`: grava a `PaeDilacao` com status aprovado e `aprovado_por` igual ao usuário, registra na timeline e chama `PaePrazoService::recalcular`.
- `processarVencimentos()`: o vencimento passa a vir de `PrazoNotificacao`, considerando as dilações. Para ciclo menor que 3, continua emitindo a próxima notificação automática. **No 3º ciclo vencido, não suspende mais.** Em vez disso:
  - grava na timeline o evento `ciclos_esgotados`, uma única vez por notificação vencida;
  - envia um aviso urgente no inbox ao analista: "ciclos esgotados: decisão da CEDEC (suspender, reprovar ou notificar novamente)".
- `emitir()`:
  - o limite de 3 ciclos passa a valer **só para a emissão automática**; a emissão manual não tem limite;
  - o `acaoUrl` do inbox passa a usar `PaeProtocolo::urlNotificacao()` (hoje o link cai em 404).
- Os 30 dias escritos direto no código em `:78`, `:177`, `:301` e `:353` passam a usar `PrazoNotificacao`.

**Protocolo.** `dt_notificacao_feam` entra na criação e na edição do protocolo, com validação em FormRequest (data, não futura) e recálculo em seguida. Os controllers continuam finos: validação no Request, regra no Service.

## 8. Comando agendado

- `app/Console/Commands/VerificarNotificacoesPae.php` vai para `app/Modules/Pae/Console/VerificarNotificacoesPae.php` e é registrado em `PaeServiceProvider` com `$this->commands([...])`, como no `DemandasServiceProvider`.
- A assinatura (`pae:verificar-notificacoes`) e o agendamento (`routes/console.php:78`, diário às 03:00) não mudam.
- Além de `processarVencimentos()`, o comando passa a recalcular o `limite_analise` dos protocolos com diligência aberta.

## 9. Tela

**Listagem** (`Templates/Pae/PaeProtocolosIndexTemplate.vue` e seus organisms)

- O payload de cada protocolo ganha `prazo_situacao` e `dt_notificacao_feam_estimada`, e o cálculo no cliente (`:331-340`) é removido.
- O `Molecules/Pae/Protocolos/PrazosPill.vue` ganha:
  - o estado `pausado` (variante informativa);
  - a marca de "estimado", com tooltip, para os protocolos legados.
- O stat card "Vencidos" passa a mostrar o valor real.
- Novo stat card **"Ciclos esgotados"**, como filtro rápido no padrão PMDA (`clickable` + `@filter` disparando `router.get`). Ele filtra os protocolos cuja última notificação é do 3º ciclo ou posterior, está vencida e não tem devolutiva.
- Limpeza no arquivo já editado: saem os ramos `props.useMock`, o import de `@/mocks/pae`, o `MockPaeProtocoloRepository` e o próprio `mocks/pae.js`, se não houver outro consumidor. Hoje a página sempre passa `use-mock=false`.

**Protocolo:**

- Campo "Data da notificação da FEAM" na criação e na edição.
- Selo "Fora do prazo (Art. 7)" quando `PrazoProtocolo::foraDoPrazo` for verdadeiro.

**CCPAE.** O modal de confirmação "Concluir para CCPAE" (`:179-194`) vira o organism `Organisms/Pae/Protocolos/EmitirCcpaeModal.vue`, com os campos:

- código;
- data de emissão (padrão: hoje);
- "empreendimento novo?" e, se sim, a data da LO.

O modal mostra a prévia do vencimento e mantém o texto de reativação para protocolo arquivado.

**Notificações** (aba de `Organisms/Pae/Protocolos/PaeHistoricoModal.vue`)

- Cada ciclo mostra o número do ciclo, a emissão, o vencimento final (com as dilações), a devolutiva e as dilações registradas.
- Ações, condicionadas à permissão `pae.protocolos.edit`, a mesma das rotas atuais:
  - **Emitir notificação:** usa a rota `pae.protocolo.notificacoes.store`.
  - **Registrar devolutiva:** usa a rota `pae.notificacoes.devolutiva`.
  - **Registrar dilação:** usa uma rota nova.
- Seguir as regras de `.claude/skills/frontend/SKILL.MD`: sem rolagem horizontal em 375px e 840px, dark mode por classe, `ActionButton` e formulário em sanfona no mobile.

**Rotas novas** (`routes/modules/pae.php`, middleware `can:pae.protocolos.edit`):

- `POST /pae/protocolo/{paeProtocolo}/ccpae` (emitir CCPAE)
- `POST /pae/notificacoes/{paeNotificacao}/dilacoes` (registrar dilação)

## 10. Testes

**Regra 10:** nenhum arquivo de teste entra no commit, nem os novos nem a atualização do `PaeNotificacaoCommandTest`. Os testes rodam localmente para validar a entrega.

**Unit** (`tests/Unit/Pae/` e `tests/Unit/Support/`):

- `CalendarioDiasUteis`: fim de semana, feriado no meio do intervalo, virada de ano.
- `PrazoProtocolo`: dentro e fora do prazo.
- `PrazoNotificacao`: com e sem dilação.
- `PrazoAnalise`:
  - sem notificação;
  - notificação fechada;
  - aberta;
  - renovações automáticas sobrepostas (união dos intervalos);
  - dilação;
  - situações `sem_data` e `vencido`.
- `VigenciaCcpae`: empreendimento novo e empreendimento com LO anterior.

**Feature** (`tests/Feature/Pae/`):

- Workflow:
  - transição inválida é recusada;
  - CCPAE genérico é recusado pelo guard;
  - a validação para CCPAE pela emissão é aceita e desarquiva o protocolo.
- `atribuir()` a partir de NOVO gera as tramitações do caminho e termina em NOTIFICACAO.
- `PaeCcpaeService::emitir`: registro, colunas planas, vencimento e código duplicado recusado.
- Recálculo de `limite_analise` nos 5 momentos.
- `processarVencimentos`: 3º ciclo vencido gera `ciclos_esgotados` e o status **não** muda. O `test_terceiro_ciclo_vencido_suspende_protocolo` é reescrito para esse comportamento.
- Emissão manual depois do 3º ciclo é aceita; a automática não passa do 3º.
- Backfill da migration: legado recebe a data estimada e o limite, e é idempotente.
- `ParecerConcluidoV1.prazo_em` sai preenchido.

**Frontend:** `npm run build` sem erro. Verificação no navegador da listagem, do modal de CCPAE e da aba de notificações em 375px e 840px, nos temas claro e escuro.

## 11. Fora do escopo do subprojeto A

- **B:** admissibilidade (9 itens do Anexo J), reprovação sumária e comunicação à FEAM e à COMPDEC (Arts. 11 §1, 131, 134 e 138).
- **C:** ficha do Anexo B (só os campos que constam da resolução).
- **D:** calculadora de evacuação do Anexo E.
- **E:** simulados (Anexo C 8.1), PAAP (Anexo D) e DCO anual (Art. 137).
- **F:** painel de sinalização do Art. 139.
- **G:** sigilo (Arts. 72 e 143).
- **Defeitos conhecidos, não tratados aqui:**
  - reaproveitamento do `num_sei` na notificação automática;
  - divisão do `PaeFormularioService`;
  - sequencial O(n) em `proximoSequencialGlobal`;
  - componentes de `Components/Pae/*` fora do Atomic Design.
- **QR code do CCPAE:** diferencial, não é exigência da resolução.

## 12. Correções ao plano v2 que afetam este escopo

| Plano v2 | Correção (texto da resolução) |
|---|---|
| 300 dias contados do "protocolo válido (pós-triagem)" | Contam da notificação da FEAM (Art. 9) |
| "FEAM -> CEDEC: 10 dias úteis" | Os 10 dias úteis são do empreendedor para protocolar na CEDEC (Art. 7 §2). A FEAM tem 65 dias (Art. 6) |
| "30 dias corridos" | O texto diz apenas "dias". Corridos é decisão da CEDEC (seção 2) |
| DCO/DCE anual no "Art. 19, parágrafo único" | DCO anual está no Art. 137, parágrafo único. Fica para o subprojeto E |
| `limite_300_dias` e `dt_dco_venc` como colunas novas | Usa `limite_analise`, que já existe. DCO fica para o E |
| `pae_sla_snapshots` | Descartado: a `PaeTimeline` já registra o histórico |
| "3 ciclos máximos" como regra mantida | Não consta na resolução. Passa a valer só para a renovação automática |

## 13. Riscos

| Risco | Mitigação |
|---|---|
| Backfill estimado gerar "vencidos" em massa no primeiro dia | Marca de "estimado" visível e tooltip. A CEDEC corrige a data real pela edição do protocolo |
| Feriados desatualizados em `config/feriados.php` | Erro limitado a 1 ou 2 dias, e só no aviso do Art. 7. Atualização anual registrada no arquivo |
| Ranking depender do comportamento atual dos eventos | Nomes, versões e campos dos 4 eventos ficam iguais. Só `prazo_em` deixa de ser nulo |
| Octane com estado entre requests | A origem da transição viaja como objeto de valor (`ContextoTransicao`) no argumento, nunca no container nem em propriedade estática |
| Comando movido não ser registrado no container | O teste Feature do comando roda pelo `artisan`. Depois do deploy, `php artisan list` precisa mostrar `pae:verificar-notificacoes` |
