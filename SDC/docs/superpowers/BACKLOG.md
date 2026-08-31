# Backlog

Itens levantados durante o trabalho e nao implementados. Cada um traz o contexto
suficiente para virar spec depois, sem precisar reconstruir a conversa.

---

## Notificacao deve acompanhar o usuario do registro

**Levantado em:** 2026-08-27, durante a validacao da release de Plantao
**Origem:** observacao do usuario sobre o painel de notificacoes

### O que foi pedido

A notificacao de sistema deve acompanhar tambem o **usuario do registro**, nao so
quem executou a acao, para ficar mais pratica de usar.

### O que existe hoje

O painel de notificacoes mostra o **ator** da acao. Exemplos reais capturados:

- "Plano PMDA 14622026082 foi enviado para analise por **COMPDEC Porteirinha (teste)**"
- "Plano PMDA 12102026082 foi aprovado por **Analista CEDEC (teste)**"
- "Plano PMDA 12082026082 foi devolvido para alteracao por **Analista CEDEC (teste)**"

O contrato de rastreabilidade vive em `app/Modules/Notificacoes`:
`Contracts/Rastreavel` e o trait `Support/TrilhaDeAcoes`. Cada model rastreavel
declara `donosNotificacao(): array`, que hoje devolve os ids de quem deve receber.
Exemplo em `app/Modules/Plantao/Models/Plantao.php`: devolve `[plantonista_id]`.

### Ambiguidade a resolver antes de virar spec

O pedido admite duas leituras, e elas levam a implementacoes diferentes:

1. **Notificar tambem o dono do registro.** Hoje, dependendo do modulo, quem
   recebe pode nao incluir quem criou o registro. A mudanca seria acrescentar o
   autor/dono a lista de destinatarios de `donosNotificacao()`.

2. **Exibir tambem o dono do registro no texto.** O texto atual nomeia so o ator.
   A mudanca seria mostrar ambos — algo como "enviado por X, do plano de Y" —
   para o leitor saber de quem e o registro sem abrir.

A segunda leitura e mais provavel pela justificativa dada ("para ficar mais
pratica", ou seja, praticidade de leitura), mas isso e inferencia. **Perguntar ao
usuario qual das duas antes de planejar.**

### Onde mexer, em qualquer das leituras

- `app/Modules/Notificacoes/Contracts/Rastreavel.php` — contrato
- `app/Modules/Notificacoes/Support/TrilhaDeAcoes.php` — trait com a logica comum
- `app/Modules/Notificacoes/Models/` — persistencia da notificacao
- `resources/js/Templates/Notificacoes/` — apresentacao no painel
- Cada model rastreavel que implemente `donosNotificacao()`

E mudanca **transversal**: afeta todo modulo que emite notificacao (PMDA, PAE,
Plantao, RAT, Decretacoes, Demandas, Cisterna, Tdap, Treinamento). Merece spec
propria e nao cabe como ajuste pontual.

---

## Reservas de viatura (subsistema C da release de Plantao)

**Origem:** fora de escopo declarado da Release 1, secao 12 do spec
`docs/superpowers/specs/2026-08-26-plantao-frota-passagem-servico-design.md`

Hoje a anotacao "Reservada 26/08 - Ten Menon" e **texto livre** no campo
`anotacao` da tabela `plantao_viatura_snapshots`. A Release 2 substitui por
entidade propria com periodo, solicitante e deteccao de conflito de agenda.

A secao 3.3.1 do spec registra que nada no codigo deve depender do formato desse
texto — condicao verificada na revisao final da branch.

---

## Painel de postos organicos e disponibilidade de combustivel (subsistema D)

**Origem:** fora de escopo declarado da Release 1

O modelo de dados do BI "Rede GTA" esta levantado no **Apendice A** do spec da
Release 1, extraido de capturas de tela.

**Bloqueio que precede qualquer planejamento:** o Power BI nao expoe API — o
embed serve apenas o shell JavaScript, verificado por requisicao. As opcoes sao
obter o feed de dados com a SUBLOG/GTA, lancamento manual por posto no NewSDC, ou
manter o link externo e internalizar apenas a lista de contatos. **Nenhuma e
decisao de codigo.**

Ligacao operacional que justifica o item: as SW4 da frota CEDEC sao a diesel, e o
plantonista precisa saber qual POC tem diesel — hoje suprido pela lista manual de
telefones da RMBH no rodape do relatorio.

---

## Permuta de plantao (subsistema B da escala)

**Origem:** fora de escopo declarado da Release 1 da escala (2026-08-28)

Pedido de troca de turno entre dois plantonistas, com aprovacao da chefia e
reescrita da escala publicada. E o subsistema que mais gera trabalho: maquina de
estados propria, duas partes, autorizacao, e trilha de quem aprovou.

Hoje a troca e feita pelo montador em `EscalaItemUpdateController`, que ja avisa
quem entra e quem sai. A permuta acrescenta o pedido partindo do plantonista, em
vez de partir de quem monta.

Base pronta: `plantao_escala_itens` tem `status` com o caso `SUBSTITUIDO`, que
nasceu para este fluxo e hoje nao e atribuido por nada.

---

## Gerador automatico de ciclo (subsistema D da escala)

**Origem:** fora de escopo declarado da Release 1 da escala

Preencher o mes inteiro a partir de um padrao (12x36, 5x1) em vez de vaga a
vaga.

**Cuidado que precede o planejamento:** a proposta original assumia ciclo 12x36,
que NAO e a escala do CEDEC. Os turnos reais sao 06-16 e 16-02 (10h) mais 08-20
e 20-08 (12h), e agora sao cadastraveis em `plantao_tipos_turno` -- um gerador
tem que ler a tabela, nunca embutir o ciclo.

---

## Exportacao da escala em PDF/Excel (subsistema E)

**Origem:** fora de escopo declarado da Release 1 da escala

O modulo ja tem `PlantaoExportController` e a infraestrutura de exports
assincronos (`exports:cleanup` no scheduler). O trabalho e a montagem do
documento, nao o encanamento.

---

## Turno EXTRAORDINARIO nao gera lembrete

**Origem:** consequencia aceita do desenho da Release 1 da escala

`EXTRAORDINARIO` tem `hora_inicio` e `hora_fim` nulas -- por definicao nao tem
horario -- entao `escalavel = false` e ele nunca entra na escala nem no
lembrete. Continua servindo para abrir turno fora de escala pelo botao normal.

Se um dia for preciso escalar turno extraordinario com hora marcada, o caminho e
cadastrar um tipo novo com horario, nao afrouxar a regra.

---
## Dividas menores da release de Plantao

Trazidas da triagem da revisao final de branch. Nenhuma bloqueou o merge.

| Item | Onde | Nota |
|---|---|---|
| `placa` unique sem exclusao de soft-deleted | `plantao_viaturas` | Apos soft-delete de uma viatura a placa fica bloqueada para novo cadastro. Mitigado hoje por `ativo=false` ser a baixa suave. Morde so se houver reuso de placa. |
| `:icon` no `Button` colide com o `:class` interno do icone | `Atoms/Button/Button.vue` | Corrigir exige alterar componente compartilhado com 20+ usos. Cosmetico (tamanho do icone depende da ordem do CSS gerado). Vale divida propria. |
| `StatusPlantao::encerrado()` e codigo morto | `Enums/StatusPlantao.php` | Docblock promete uma guarda que nao foi implementada. Remover o metodo ou implementar a regra. |
| `toSelectArray()` identico em 6 enums | `app/Modules/Plantao/Enums/` | Padrao pre-existente do projeto, nao desta release. Divida global. |
| Sem teste do bloco catch/traduz de `abrirTurno()` | `PassagemServicoService` | So a constraint crua do banco e testada. Bloco de 4 linhas, risco baixo. |
| Filtros da tela da frota inline no template | `ViaturasIndexTemplate.vue` | Sem segundo consumidor; extrair organismo seria YAGNI hoje. |
