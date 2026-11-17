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

---

## Painel de Capacitacoes, pagina 2: distribuicao de kits de material didatico

**Levantado em:** 2026-11-17, na abertura do painel de indicadores das Capacitacoes
**Origem:** `SDC/docs/plans/kpis-treinmento-dash.md` (pagina 2), pedido da Diretoria de Educacao (DEPDC)

### O que foi pedido

Painel "PAINEL DE MONITORAMENTO - DEPDC/CEDEC-MG" da distribuicao de material
didatico nas acoes de capacitacao. Filtros: ano, periodo, municipio, REDEC e
curso/evento. Cards: total de kits, municipios atendidos, REDECs atendidas,
eventos atendidos, media de kits por evento e por municipio. Mapa de MG (circulo
= kits), kits por REDEC, kits por curso/evento, evolucao mensal com comparacao
entre anos, quantidade e composicao percentual por item (blocos, pastas,
panfletos Estiagem, Enxurradas, COMPDEC e NUPDEC) e tabela detalhada. Indicador
estrategico: media de kits distribuidos por participante.

### O que existe hoje

Nada registra essa distribuicao. O modulo Estoque tem `estoque_kits` e
movimentacoes com `destino_municipio_id`, mas os kits de la sao de ajuda
humanitaria; blocos, pastas e panfletos nao estao cadastrados.

### A decidir antes da spec

1. O material didatico passa a ser controlado no Estoque (almoxarifado da DEPDC,
   saida com destino = municipio e vinculo a turma) ou num lancamento proprio das
   Capacitacoes (por turma/evento, quantidade por item)?
2. A lista de itens e fixa ou cadastravel?
3. Evento fora do SDC (palestra, seminario) recebe kit: entra como turma ou como
   evento avulso?

A pagina 1 (Capacitacoes) ja esta no ar com municipio da turma e REDEC; o
painel de kits reaproveita esses filtros e o mapa.

---

## Painel de Capacitacoes: importar o historico da DEPDC

**Levantado em:** 2026-11-17, na abertura do painel de indicadores
**Origem:** os exemplos do pedido (2024: 5 capacitacoes, 2025: 26; 1a REDEC: 2.350
participantes) vem de planilha, nao do SDC

### O que existe hoje

O painel conta so inscricoes concluidas no SDC (`inscricoes.concluido_em`), com
municipio de realizacao da turma e sexo declarado na inscricao. Capacitacoes
feitas antes do modulo, ou com lista de presenca em papel, nao aparecem.

### A decidir antes da spec

1. Formato das planilhas da DEPDC (linha por evento com totais por sexo, ou
   linha por participante?).
2. Importar como turmas concluidas com participantes agregados (sem pessoa) ou
   como um lancamento agregado proprio que o painel soma as turmas do SDC?
3. Ate que ano voltar.

---

## Meus cursos: secoes retrateis

**Status:** FEITO em 2026-11-17 (branch `feat/capacitacoes-indicadores`): `Molecules/Treinamento/SecaoRecolhivel.vue` sobre o `useCollapsibleSection` existente; todas comecam abertas e o navegador lembra o que foi fechado. A Area do Gestor ficou de fora (uma secao so).

**Levantado em:** 2026-11-17
**Origem:** pedido do usuario sobre `/treinamentos/aluno` (print das tres secoes)

### O que foi pedido

As secoes "Meus cursos", "Meus certificados" e "Cursos disponiveis" da tela do
aluno passam a abrir e fechar (retrateis), para o aluno com muitos cursos nao
precisar rolar a pagina inteira ate a secao que quer.

### O que existe hoje

`resources/js/Pages/Treinamento/Aluno/MeusCursos.vue` monta as tres secoes com o
`SecaoHeader` (titulo + contador), sempre abertas.

### A decidir antes da spec

1. Estado inicial: todas abertas, ou so "Meus cursos" aberta?
2. Lembrar o que o aluno fechou (por navegador) ou sempre reabrir?
3. O `SecaoHeader` ganha a opcao de recolher (vale para outras telas que o usam)
   ou a tela do aluno tem o seu?

---

## Validador de cursos por QR Code do participante

**Status:** FEITO em 2026-11-17 (branch `feat/capacitacoes-indicadores`): bloco "Minha credencial" em Meus cursos (QR fixo, regeravel, vinculado ao CPF), tela "Validar participante" com leitor, abas de pesquisa por CPF e numero do certificado e painel de situacao (certificados validos e cursos em andamento); permissao `treinamento.participantes.validar`; cada consulta registrada em `treinamento.credencial_consultas`.

**Levantado em:** 2026-11-17
**Origem:** logistica de evento: a equipe da CEDEC precisa conferir na hora quais
cursos uma pessoa ja fez (ex.: pre-requisito para atuar ou participar)

### O que foi pedido

Cada participante tem um QR Code proprio. Num evento, a equipe da CEDEC le o QR
com o celular e ve as capacitacoes da pessoa (concluidas, em andamento e
certificados validos), sem precisar buscar nome ou CPF numa lista.

**Ponto de entrada:** botao "Ler QR" com icone no cabecalho da tela de gestao de
Capacitacoes (`/treinamentos`), ao lado do botao Exportar. O uso principal sera
no CELULAR, em evento, entao o desenho parte do mobile:

- No mobile o botao fica visivel e facil de tocar (alvo de toque do guia de
  responsividade), nao escondido num menu de "mais acoes"; Grade/Tabela e
  Exportar podem recolher antes dele.
- O leitor abre a camera traseira em tela cheia, com retorno imediato (som ou
  vibracao) e o resultado em cartao legivel com uma mao; botao para ler o
  proximo sem voltar para a lista.
- Sem rolagem horizontal em 375px e 840px, como o resto do sistema.
- Fallback para digitar o codigo quando a camera falhar ou for negada.

### O que existe hoje

- QR por INSCRICAO (`inscricoes.qr_code_token`), usado para a presenca na turma
  (`Controllers/Admin/PresencaQrController.php`, `PresencaRosterController.php`).
  Ele identifica uma inscricao, nao a pessoa.
- Validacao publica de UM certificado pelo codigo (`/validar-certificado/{codigo}`,
  `ValidarCertificadoController`).
- Nao ha QR por pessoa nem tela que liste as capacitacoes de alguem a partir de
  um codigo.

### Decidido (2026-11-17)

- **Quem le:** so usuarios da CEDEC autenticados com autorizacao do modulo de
  Capacitacoes. Nao ha leitura publica: fora do sistema o QR nao mostra nada.
  Na spec, criar a permissao propria (ex.: `treinamento.participantes.validar`)
  em `config/permissions.php` e no papel de gestao, em vez de reaproveitar outra.
- **Quem tem QR:** so SERVIDORES (`User`, banco sdc). Cidadao do portal fica de
  fora, o que elimina o problema das duas identidades em bancos diferentes.
- **QR fixo por servidor:** mais simples de gerenciar. O que comprova cada curso
  concluido e o certificado ja registrado (`certificados`, com codigo de
  verificacao); o QR so identifica a pessoa e a tela lista as capacitacoes e os
  certificados dela. Prever revogar/regerar o token se o QR vazar.
- **Sem check-in:** a leitura so consulta. Check-in em evento e outra
  funcionalidade, fora deste item.

### Ainda a decidir antes da spec

1. Onde o servidor pega o QR: "Meus cursos" (bloco proprio ou junto de "Meus
   certificados")? Tambem no perfil do usuario?
2. O que a tela de resultado mostra alem dos certificados validos: cursos em
   andamento? certificados cancelados?
3. Registrar quem consultou quem (trilha de acesso a dado pessoal)?
