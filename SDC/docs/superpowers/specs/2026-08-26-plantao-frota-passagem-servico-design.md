# Modulo PLANTAO — Frota de Viaturas e Passagem de Servico

**Data:** 2026-08-26
**Status:** Design aprovado pelo usuario, pendente de revisao final do spec
**Branch:** `feat/plantao-frota-passagem` (worktree `.claude/worktrees/plantao-frota-passagem`, base `origin/dev`)
**Destino:** `NewSDC/SDC` — `app/Modules/Plantao`
**Escopo desta release:** subsistemas A (Frota) e B (Passagem de servico)

---

## 1. Contexto

### 1.1 O problema

A passagem de servico do plantao CEDEC acontece hoje inteiramente por texto
colado em grupo de WhatsApp. O plantonista que assume digita a mao, a cada turno,
o estado de cada viatura: nivel de combustivel, hodometro, alteracoes e ultimo
condutor. Nada disso tem lastro em banco. Nao ha historico consultavel, nao ha
responsabilizacao sobre avaria, e o mesmo dado e redigitado duas ou tres vezes
por dia.

O formato exato do texto praticado hoje esta no Apendice B.

### 1.2 O que existe no NewSDC hoje

O modulo `app/Modules/Plantao` e fino. A tabela `plantoes`
(`database/migrations/2026_02_27_201302_create_plantoes_table.php`) tem apenas:

| coluna | observacao |
|---|---|
| `plantonista_id` | FK `users`, obrigatoria |
| `plantonista_nome` | espelho do nome |
| `data` | date |
| `periodo` | enum `PeriodoPlantao` |
| `status` | enum `StatusPlantao` (`ATIVO`, `FINALIZADO`) |
| `observacoes` | text nullable |

Arquivos do modulo: `Controllers/PlantaoIndexController.php`,
`Controllers/PlantaoExportController.php`, `Controllers/NoticiasIndexController.php`,
`DTOs/PlantaoListDTO.php`, `Enums/PeriodoPlantao.php`, `Enums/StatusPlantao.php`,
`Models/Plantao.php`, `Services/PlantaoService.php`, `PlantaoServiceProvider.php`.

O model `Plantao` ja implementa `Rastreavel` com `TrilhaDeAcoes` — a trilha de
acoes e a notificacao ao dono ja funcionam e devem ser preservadas.

### 1.3 Nao existe entidade de veiculo no sistema

Levantamento importante: **nao ha nenhuma tabela de veiculo no NewSDC.** A
`rat_veiculos` foi criada e depois derrubada — `database/migrations/2026_05_19_100000_drop_unused_rat_tables.php:40`
executa `Schema::dropIfExists('rat_veiculos')` com o comentario `// no model`.

Consequencia: campo limpo. Nao ha duplicacao de identidade de veiculo a resolver,
nem refatoracao do modulo RAT a fazer. A frota nasce nova dentro do Plantao.

### 1.4 Conflito entre codigo e operacao (resolvido)

`Enums/PeriodoPlantao.php` declara hoje `DIURNO` como `07:00hs as 19:00hs` e
`NOTURNO` como `19:00hs as 07:00hs`. A operacao real, conforme os relatorios,
usa **06h as 16h** e **16h as 02h** — dois turnos de 10h que nao fecham 24h.

Decisao: corrigir os labels do enum. A lacuna 02h-06h e coberta por sobreaviso
(viatura exclusiva), nao por plantao presencial. Os valores gravados no banco
(`DIURNO`, `NOTURNO`) permanecem, logo nao ha migracao de dados.

---

## 2. Decisoes travadas

| Questao | Decisao |
|---|---|
| Fatiamento | Release 1 = A (Frota) + B (Passagem). C (Reservas) e D (Painel de postos) ficam para releases seguintes, cada uma com spec propria. |
| Evento a registrar | **Os dois.** Movimentacao individual de viatura (saida e retorno por condutor) e a fonte da verdade; o snapshot do turno e derivado dela e apenas confirmado ou corrigido pelo plantonista. |
| Abrangencia da frota | Somente CEDEC / Predio Alterosas. Sem vinculo com REDEC, sem escopo de permissao por lotacao. |
| Saida do relatorio | Botao Copiar para WhatsApp gerando o texto identico ao praticado hoje. Sem PDF nesta release. |
| Turnos | Enum corrigido para 06h-16h e 16h-02h, valores de banco intactos. |
| Identidade dos militares | FK para `users` com coluna espelho do nome, seguindo o padrao ja usado por `plantonista_id` mais `plantonista_nome`. |
| Passagem de servico | **Aceite formal das duas partes.** Quem sai declara o estado; quem assume confere e aceita ou aponta divergencia. |

---

## 3. Schema

Quatro tabelas novas mais uma alteracao. Regra de ouro 9: cada tabela nova tem
UMA migration, e ajustes posteriores durante esta release sao consolidados nela,
nao empilhados.

### 3.1 `plantao_viaturas` — cadastro da frota

| coluna | tipo | nota |
|---|---|---|
| `id` | id | |
| `prefixo` | string(20) | modelo operacional, ex. SW4 |
| `placa` | string(10), unique | ex. QMV-2241 |
| `marca` | string(50) nullable | |
| `modelo` | string(100) | |
| `localizacao` | string(40) | enum `LocalizacaoViatura` |
| `exclusiva_sobreaviso` | boolean, default false | a flag do QMV-2241 |
| `status` | string(30) | enum `StatusViatura` |
| `hodometro_atual` | unsignedInteger nullable | **cache** |
| `nivel_combustivel` | string(20) nullable | **cache**, enum `NivelCombustivel` |
| `ultimo_condutor_id` | FK `users` nullable, nullOnDelete | **cache** |
| `ultimo_condutor_nome` | string nullable | espelho |
| `observacoes` | text nullable | |
| `ativo` | boolean, default true | |
| | timestamps mais softDeletes | |

Indices: `placa` (unique), `ativo`, `status`, `deleted_at`.

**Sobre os campos de cache.** `hodometro_atual`, `nivel_combustivel`,
`ultimo_condutor_id` e `ultimo_condutor_nome` sao derivados da ultima
movimentacao. Ficam materializados na linha da viatura porque a tela de indice
lista a frota inteira com esses valores, e derivar por subquery a cada render
custaria caro sem ganho. **`MovimentacaoViaturaService` e o unico ponto do
sistema autorizado a escrever esses quatro campos** — nenhum controller, request
ou outro service os toca. Isso mantem uma unica fonte de verdade e satisfaz DRY
sem pagar o preco da derivacao.

### 3.2 `plantao_viatura_movimentacoes` — o uso individual

| coluna | tipo | nota |
|---|---|---|
| `id` | id | |
| `viatura_id` | FK `plantao_viaturas`, cascadeOnDelete | |
| `plantao_id` | FK `plantoes` nullable, nullOnDelete | turno em que a saida ocorreu |
| `condutor_id` | FK `users`, restrictOnDelete | |
| `condutor_nome` | string | espelho |
| `saida_em` | datetime | |
| `saida_hodometro` | unsignedInteger | |
| `saida_combustivel` | string(20) | enum `NivelCombustivel` |
| `destino` | string(160) nullable | |
| `motivo` | string(160) nullable | |
| `retorno_em` | datetime nullable | |
| `retorno_hodometro` | unsignedInteger nullable | |
| `retorno_combustivel` | string(20) nullable | |
| `alteracoes` | text nullable | avaria ou alteracao constatada no retorno |
| `status` | string(20) | enum `StatusMovimentacao`: `EM_TRANSITO`, `RETORNADA` |
| | timestamps mais softDeletes | |

Indices: `viatura_id`, `plantao_id`, `condutor_id`, `status`, e o composto
`(viatura_id, status)` para a checagem de movimentacao aberta.

### 3.3 `plantao_viatura_snapshots` — estado congelado no fechamento do turno

| coluna | tipo | nota |
|---|---|---|
| `id` | id | |
| `plantao_id` | FK `plantoes`, cascadeOnDelete | |
| `viatura_id` | FK `plantao_viaturas`, restrictOnDelete | |
| `prefixo` | string(20) | **espelho** |
| `placa` | string(10) | **espelho** |
| `hodometro` | unsignedInteger | |
| `nivel_combustivel` | string(20) | |
| `alteracoes` | text nullable | vazio renderiza como Sem alteracoes |
| `ultimo_condutor_id` | FK `users` nullable, nullOnDelete | |
| `ultimo_condutor_nome` | string nullable | espelho |
| `anotacao` | string(160) nullable | ver 3.3.1 |
| `em_condicoes` | boolean, default true | viaturas em condicoes de atendimento |
| | timestamps | |

Constraint: `unique(plantao_id, viatura_id)`.

**Como `em_condicoes` e definido.** No momento em que `encerrar()` monta o
snapshot, o campo e **pre-preenchido** como verdadeiro quando o `status` da
viatura e `DISPONIVEL` ou `EM_TRANSITO`, e falso quando e `MANUTENCAO`,
`CEDIDA` ou `INDISPONIVEL`. O plantonista pode **sobrescrever** a linha na tela
de encerramento — uma viatura pode estar formalmente disponivel e, na conferencia
fisica, nao estar em condicoes. Depois de gravado, o valor do snapshot e
independente do status atual da viatura, porque o snapshot e historico.

**Por que espelhos de `prefixo` e `placa`.** O snapshot e registro historico. Se
a placa de uma viatura mudar, ou a viatura for baixada, o relatorio de um turno
passado precisa continuar fiel ao que foi de fato declarado naquele dia. Mesmo
raciocinio que justifica `plantonista_nome` no schema existente.

#### 3.3.1 O campo `anotacao` e o subsistema C

No relatorio praticado hoje aparecem anotacoes como `(Exclusiva Sobreaviso)` e
`- Reservada 26/08 - Ten Menon`.

- `(Exclusiva Sobreaviso)` **tem modelagem propria**: e a flag booleana
  `exclusiva_sobreaviso` na viatura, e o relatorio a renderiza a partir dela.
- `Reservada 26/08 - Ten Menon` **nao tem modelagem nesta release.** Reserva de
  viatura e o subsistema C, fora de escopo. Nesta release e **texto livre** no
  campo `anotacao` do snapshot, digitado pelo plantonista.

Isso e divida tecnica assumida e declarada: a Release 2 (subsistema C) troca
`anotacao` por uma entidade `plantao_viatura_reservas` com periodo, solicitante e
deteccao de conflito, e o relatorio passa a derivar o texto dela. O campo
`anotacao` permanece para observacao genuinamente livre.

### 3.4 `plantoes` — ALTER, sem migracao de dados

| coluna nova | tipo | nota |
|---|---|---|
| `plantonista_saida_id` | FK `users` nullable, nullOnDelete | quem entrega o servico |
| `plantonista_saida_nome` | string nullable | espelho |
| `localizacao` | string(60) nullable | ex. Predio Alterosas |
| `ocorrencias_destaque` | text nullable | bloco de ocorrencias ou acoes de destaque do turno anterior |
| `encerrado_em` | datetime nullable | quando quem sai declarou o estado |
| `aceito_em` | datetime nullable | quando quem assume aceitou |
| `aceito_por_id` | FK `users` nullable, nullOnDelete | |
| `divergencia` | text nullable | preenchido apenas quando ha divergencia apontada |

Nenhuma coluna existente e alterada ou removida. `observacoes` continua sendo a
observacao livre do turno; `ocorrencias_destaque` e o bloco especifico do
relatorio, semanticamente distinto.

---

## 4. Maquina de estados da passagem de servico

O aceite formal das duas partes cria um estado intermediario. O fluxo completo:

```
[Sgt Deivison, turno 06h-16h, status ATIVO]
        |
        | encerra o turno
        | PassagemServicoService::encerrar()
        |   - monta um snapshot por viatura ativa da frota
        |   - cada snapshot vem PRE-PREENCHIDO pela ultima movimentacao
        |     (hodometro, combustivel, ultimo condutor)
        |   - Deivison confirma ou corrige cada linha
        |   - grava encerrado_em
        v
[turno 06h-16h: PENDENTE_ACEITE]
        |
        | Sgt Leandro assume o turno 16h-02h e confere
        | PassagemServicoService::aceitar() ou ::apontarDivergencia()
        |
        +--- ACEITA ------------------> [06h-16h: FINALIZADO]
        |                               aceito_em, aceito_por_id
        |
        +--- APONTA DIVERGENCIA ------> [06h-16h: FINALIZADO_COM_DIVERGENCIA]
        |                               aceito_em, aceito_por_id, divergencia
        |                               mais a divergencia propagada como
        |                               alteracao na viatura envolvida
        v
[novo turno 16h-02h: ATIVO]
  plantonista_id        = Leandro (quem assume)
  plantonista_saida_id  = Deivison (quem entregou, preenchido automaticamente)
  herda o snapshot aceito como estado inicial
```

### 4.1 `StatusPlantao` apos a mudanca

| valor | label | novo? |
|---|---|---|
| `ATIVO` | Ativo | existente |
| `PENDENTE_ACEITE` | Pendente de aceite | **novo** |
| `FINALIZADO` | Finalizado | existente |
| `FINALIZADO_COM_DIVERGENCIA` | Finalizado com divergencia | **novo** |

Os valores `ATIVO` e `FINALIZADO` ja gravados no banco continuam validos. Nenhum
backfill necessario.

### 4.2 O primeiro turno e o turno sem antecessor

Abrir um turno **nao exige** que exista turno anterior aceito. Quando nao ha
antecessor — primeiro turno do sistema, ou lacuna na escala — `abrirTurno()`
grava `plantonista_saida_id` e `plantonista_saida_nome` como nulos, e o snapshot
inicial e montado a partir do estado corrente das viaturas em vez de ser herdado.
No relatorio, a linha `Saindo de servico:` e omitida quando nula.

O que **e** exigido: nao pode existir outro turno `ATIVO` para a mesma data e
periodo (regra da secao 10). Um turno anterior em `PENDENTE_ACEITE` nao bloqueia
a abertura do novo — bloquear travaria a operacao por causa de burocracia. O
aceite pendente aparece como banner ate ser resolvido.

### 4.3 Risco declarado: o fluxo pode travar

Um handshake de duas partes trava se quem sai nao encerrar. Mitigacao nesta
release, sem inventar automacao:

- A tela de indice mostra `PassagemHandshakeBanner` com a pendencia em destaque
  para quem tem `plantao.passagem.aceitar`.
- Quem assume pode encerrar o turno anterior em nome de quem saiu, registrando
  em `divergencia` que o encerramento foi feito por terceiro. O turno vai para
  `FINALIZADO_COM_DIVERGENCIA` — o sistema nao esconde a falha do ritual, ele a
  registra.

Nao havera job de encerramento automatico. Encerrar sozinho um turno que ninguem
conferiu produziria dado falso com aparencia de dado verdadeiro.

---

## 5. Enums

Todos em `app/Modules/Plantao/Enums`, seguindo o padrao existente do modulo:
metodo `label()` e metodo estatico `toSelectArray()`.

| Enum | Casos | Nota |
|---|---|---|
| `PeriodoPlantao` | `DIURNO`, `NOTURNO`, `EXTRAORDINARIO` | **alterado**: labels passam a 06:00hs as 16:00hs e 16:00hs as 02:00hs |
| `StatusPlantao` | mais `PENDENTE_ACEITE` e `FINALIZADO_COM_DIVERGENCIA` | **alterado** |
| `NivelCombustivel` | `VAZIO`, `QUARTO_1`, `QUARTO_2`, `QUARTO_3`, `QUARTO_4` | labels de 0/4 a 4/4; expoe `percentual(): int` para o gauge |
| `StatusViatura` | `DISPONIVEL`, `EM_TRANSITO`, `MANUTENCAO`, `CEDIDA`, `INDISPONIVEL` | `DISPONIVEL` e `EM_TRANSITO` contam como em condicoes de atendimento |
| `LocalizacaoViatura` | `PREDIO_ALTEROSAS`, `OFICINA`, `CEDIDA`, `OUTRO` | |
| `StatusMovimentacao` | `EM_TRANSITO`, `RETORNADA` | |

**Armadilha do Tailwind (registrada no kernel).** Tailwind nao escaneia
`app/**/*.php`. Nenhum enum devolve classe de cor CSS. As cores do gauge de
combustivel e dos badges de status vivem nos arquivos `.vue`, mapeadas a partir
do valor do enum. Enum devolve dado, nunca classe.

---

## 6. Camada de servicos

Uma responsabilidade por servico (SRP). Todos em
`app/Modules/Plantao/Services`, estendendo `App\Modules\Shared\BaseService` como
o `PlantaoService` existente.

| Servico | Responsabilidade | Nao faz |
|---|---|---|
| `PlantaoService` | Listagem, filtros e estatisticas de turno. **Permanece como esta**, apenas ganha os novos status nos contadores. | Nao ganha logica de frota nem de passagem. |
| `ViaturaService` | CRUD da frota, listagem, filtros, estatisticas. | Nao escreve os campos de cache de estado. |
| `MovimentacaoViaturaService` | Registrar saida, registrar retorno. **Unico ponto que escreve `hodometro_atual`, `nivel_combustivel`, `ultimo_condutor_id`, `ultimo_condutor_nome` e `status` da viatura.** | Nao monta snapshot nem renderiza relatorio. |
| `PassagemServicoService` | `encerrar()`, `aceitar()`, `apontarDivergencia()`, `abrirTurno()`. Monta os snapshots pre-preenchidos. | Nao renderiza texto. |
| `RelatorioPassagemService` | Monta o payload do relatorio e renderiza o texto a partir da view. | Nao consulta regra de negocio; recebe o plantao ja carregado. |

Transacoes: `encerrar()` e `abrirTurno()` escrevem N snapshots mais o turno em
uma unica transacao. `registrarRetorno()` escreve a movimentacao mais o cache da
viatura em uma unica transacao.

---

## 7. Controllers, rotas e permissoes

### 7.1 Controllers

Single-action com `__invoke`, seguindo o padrao ja estabelecido no modulo.

| Controller | Rota |
|---|---|
| `ViaturaIndexController` | `GET /plantao/viaturas` |
| `ViaturaStoreController` | `POST /plantao/viaturas` |
| `ViaturaUpdateController` | `PUT /plantao/viaturas/{viatura}` |
| `ViaturaDestroyController` | `DELETE /plantao/viaturas/{viatura}` |
| `MovimentacaoSaidaController` | `POST /plantao/viaturas/{viatura}/saida` |
| `MovimentacaoRetornoController` | `POST /plantao/movimentacoes/{movimentacao}/retorno` |
| `PassagemEncerrarController` | `POST /plantao/{plantao}/encerrar` |
| `PassagemAceitarController` | `POST /plantao/{plantao}/aceitar` |
| `RelatorioPassagemController` | `GET /plantao/{plantao}/relatorio`, devolve JSON com o texto |

Rotas em `routes/modules/plantao.php`, dentro do grupo `prefix('plantao')` e
`name('plantao.')` ja existente. Rotas estaticas antes das parametrizadas, para
nao capturar `/viaturas` como `{plantao}`. Rodar `npm run prebuild` para
regenerar `ziggy.js` — o kernel exige checar `ziggy.js` em nova rota Inertia.

FormRequests em `app/Modules/Plantao/Requests`, um por acao de escrita.

### 7.2 Permissoes

Novos slugs em `config/permissions.php`, preservando `plantao.turnos.*`:

```
plantao.viaturas.view
plantao.viaturas.create
plantao.viaturas.edit
plantao.viaturas.delete
plantao.passagem.encerrar
plantao.passagem.aceitar
plantao.passagem.relatorio
```

Atribuicao aos perfis seguindo o mesmo criterio ja aplicado a
`plantao.turnos.*` nos blocos de papel do arquivo. O curinga `plantao.*` do
perfil administrador ja cobre os novos slugs automaticamente.

---

## 8. O relatorio e a Regra de Ouro 2

### 8.1 A tensao

O texto praticado hoje depende de emojis como marcadores de campo (viatura,
combustivel, hodometro, alteracoes, condutor). A Regra de Ouro 2 proibe emoji
**dentro do codigo**.

### 8.2 A resolucao

O template do relatorio vive em
`resources/views/plantao/passagem-servico.txt.blade.php`. E uma view — recurso de
apresentacao, conteudo — nao logica de aplicacao. Nenhum emoji entra em arquivo
`.php` de service, enum, controller, model ou request, e nenhum entra em `.vue`.
`RelatorioPassagemService` monta um array de dados e chama o render da view; ele
nao conhece nenhum emoji.

O caractere unicode fica no template porque e ali que o formato de saida e
definido, e porque a fidelidade ao texto que a tropa ja usa e o requisito
central desta entrega.

### 8.3 Conteudo fixo do rodape

Os telefones de diesel RMBH, o link do BI Rede GTA e os contatos DTT e Plantao
GMG sao constantes operacionais, nao dado de turno. Vao para `config/plantao.php`
sob a chave `relatorio.rodape`. Isso permite corrigir um telefone sem tocar em
template nem em service.

Na release do subsistema D esses contatos passam a ser dado gerenciado. Ate
entao, config.

### 8.4 Copia para a area de transferencia

API de clipboard do navegador, com fallback por textarea oculta mais
`execCommand` para contexto sem HTTPS ou navegador antigo. Feedback por toast
usando os atomos de Toast existentes.

---

## 9. Frontend

Atomic design, reaproveitando obrigatoriamente a casca padrao registrada no
kernel. Nada de header, secao ou card novo.

### 9.1 Casca reutilizada, nao reimplementada

| Elemento | Componente |
|---|---|
| Header de pagina | `Components/Organisms/PageHeader.vue`, variante gradient, icone via `moduleIcon('plantao')` |
| Secao de filtro e formulario | `Components/Molecules/CollapsibleSection.vue`, namespace `plantao` |
| Cards de estatistica | `Molecules/Statistics/StatCard.vue` dentro de `StatCardsGrid.vue`; card e filtro rapido |
| Campos | `Molecules/Form/*` (`FormField`, `FormSelect`, `FormTextarea`, `FormDateField`, `ToggleField`, `FormActions`) |
| Paginacao | `Molecules/Navigation/Pagination.vue`, prop achatada |
| Lista vazia | `Molecules/ListEmptyState.vue` |
| Acoes | `Atoms/Button/ActionButton.vue` no slot de acoes do header |

### 9.2 Componentes novos

**Atoms** — `Components/Atoms/Plantao/`

| Componente | Papel |
|---|---|
| `CombustivelGauge.vue` | Barra vertical de nivel, inspirada no BI Rede GTA: trilho cinza, preenchimento proporcional, percentual ao centro, faixa de status na base. Classes Tailwind literais no arquivo. |
| `HodometroBadge.vue` | Hodometro formatado com separador de milhar. |

**Molecules** — `Components/Molecules/Plantao/`

| Componente | Papel |
|---|---|
| `ViaturaSnapshotCard.vue` | Um bloco de viatura do relatorio: prefixo, placa, anotacao, gauge, hodometro, alteracoes, ultimo condutor. |
| `PassagemHandshakeBanner.vue` | Banner de pendencia de aceite, com acao de conferir. |

**Organisms** — `Components/Organisms/Plantao/`

| Componente | Papel |
|---|---|
| `ViaturasGrid.vue` e `ViaturasTable.vue` | Grade e tabela da frota, alternadas pelo `ViewModeToggle` que a tela de plantao ja usa. |
| `ViaturaFormModal.vue` | Cadastro e edicao de viatura. |
| `MovimentacaoModal.vue` | Registrar saida e registrar retorno. |
| `EncerrarTurnoModal.vue` | Lista os snapshots pre-preenchidos para confirmacao ou correcao linha a linha. |
| `AceitarPassagemModal.vue` | Conferencia com aceite ou apontamento de divergencia. |
| `RelatorioPassagemPanel.vue` | Pre-visualizacao do texto mais botao Copiar para WhatsApp. |

**Templates e Pages**

- `Templates/Plantao/ViaturasIndexTemplate.vue` mais `Pages/Plantao/ViaturasIndex.vue`
- `Templates/Plantao/PlantaoIndexTemplate.vue` — estendido com o banner de
  pendencia e o painel de relatorio. Nao reescrito.

### 9.3 Armadilhas do kernel a respeitar

- `SelectInput` le `value`/`id` e `label`/`name`/`text`. Backend que devolva
  `nome` precisa ser mapeado para o par value e label antes de chegar ao select.
- Atributo solto como `inputmode` ou `step` passado a `FormField` cai na div
  raiz. O campo de hodometro precisa de `inputmode` declarado como prop e
  repassado, nao jogado como atributo.
- Prop closure no Inertia e avaliada em toda visita completa. O filtro da frota
  usa reload parcial com `only` para nao recalcular estatistica.

---

## 10. Regras de negocio e validacoes

| Regra | Onde |
|---|---|
| Hodometro de retorno maior ou igual ao de saida | `MovimentacaoRetornoRequest` mais guarda no servico |
| Hodometro de saida maior ou igual ao `hodometro_atual` da viatura | `MovimentacaoSaidaRequest` |
| Uma viatura nao pode ter duas movimentacoes `EM_TRANSITO` | guarda no `MovimentacaoViaturaService`, consultando o indice composto |
| Viatura em `MANUTENCAO`, `CEDIDA` ou `INDISPONIVEL` nao pode sair | guarda no servico |
| Placa unica na frota | constraint no banco mais regra unique no request |
| Um unico turno `ATIVO` por data e periodo | guarda no `PassagemServicoService` |
| Nao encerrar turno que nao esta `ATIVO` | guarda no servico |
| Nao aceitar turno que nao esta `PENDENTE_ACEITE` | guarda no servico |
| Quem aceita nao pode ser quem encerrou | guarda no servico; o aceite formal perde sentido se for a mesma pessoa |
| Snapshot exige uma linha por viatura ativa | guarda no `encerrar()` |

Validacao dupla, em request e em servico, porque o servico tambem e chamado por
teste e por futuro comando artisan, onde nao ha request.

---

## 11. Testes

TDD: teste antes da implementacao, em cada fase.

**Feature tests** — `tests/Feature/Plantao/`

- Encerrar turno gera um snapshot por viatura ativa, pre-preenchido pela ultima
  movimentacao de cada uma.
- Encerrar turno move o status para `PENDENTE_ACEITE` e grava `encerrado_em`.
- Aceitar move para `FINALIZADO` e grava `aceito_em` e `aceito_por_id`.
- Apontar divergencia move para `FINALIZADO_COM_DIVERGENCIA` e grava o texto.
- Quem encerrou nao consegue aceitar o proprio turno.
- Abrir turno preenche `plantonista_saida_id` a partir do turno anterior.
- Abrir turno sem antecessor grava `plantonista_saida_id` nulo e monta o snapshot
  a partir do estado corrente das viaturas.
- Turno anterior em `PENDENTE_ACEITE` nao bloqueia a abertura do novo turno.
- Segundo turno `ATIVO` na mesma data e periodo e rejeitado.
- `em_condicoes` vem falso para viatura em `MANUTENCAO` e pode ser sobrescrito
  pelo plantonista; o valor gravado no snapshot nao muda depois se o status da
  viatura mudar.
- Retorno com hodometro menor que a saida e rejeitado.
- Segunda saida da mesma viatura sem retorno e rejeitada.
- Saida de viatura em manutencao e rejeitada.
- Cada rota nova respeita a permissao declarada.

**Unit tests** — `tests/Unit/Plantao/`

- `NivelCombustivel::percentual()` para todos os casos.
- `RelatorioPassagemService` renderiza o texto **caractere a caractere** igual ao
  Apendice B, para um plantao de fixture. Este e o teste que protege o requisito
  central da entrega.

---

## 12. Fora de escopo, explicito

| Item | Onde entra |
|---|---|
| Reserva de viatura como entidade (periodo, solicitante, conflito) | Release 2, subsistema C. Nesta release e texto livre em `snapshots.anotacao`. |
| Painel de postos organicos e disponibilidade de combustivel | Release 3, subsistema D. Ver Apendice A e o bloqueio de origem de dados. |
| Frota fora do CEDEC, lotacao por REDEC, escopo de permissao por unidade | Nao planejado. |
| PDF formal de passagem de servico com assinaturas | Nao planejado. |
| Encerramento automatico de turno por job | Recusado por decisao de design, ver 4.3. |
| Integracao com o modulo RAT | Nao ha o que integrar: `rat_veiculos` nao existe mais. |

---

## Apendice A — Modelo do BI Rede GTA (levantado, nao implementado)

Levantamento feito para a Release 3. **O Power BI nao e raspavel**: o embed serve
apenas o shell JavaScript, e uma requisicao HTTP a URL publica devolve somente o
spinner de carregamento. O modelo abaixo foi extraido de capturas de tela
fornecidas pelo usuario.

Fonte: SUBLOG / SCL / DCTR / Coordenacao de Abastecimento.
Contato de origem: `gta@planejamento.mg.gov.br`.

Entidade **POC (Posto Organico Compartilhado)**:

| Campo | Valores observados |
|---|---|
| sigla da unidade | 57BPM, ROTAMPM, PCMG, CSM, CMI, BMVES, BMSSP, BMITA, 3BBM, 34BPM, 22BPM, 1BPM, 1BBM, 16BPM, 13BPM, 9BPM, 9BBM, 8BPM, 63BPM a 52BPM, 59CIAPM |
| municipio | filtro (Belo Horizonte, Sao Lourenco, All) |
| status do posto | Liberado |
| gasolina, alcool, diesel | por combustivel: rotulo disponivel ou indisponivel, percentual do tanque (0%, 2%, 33%, 50%, 64%, 70% observados), barra vertical, faixa Sem combustivel disponivel quando zerado |
| tem frentista | booleano |
| endereco | texto livre |
| contatos | telefone |
| horario de funcionamento | tres campos: seg a sex, almoco, final de semana, com nota livre (24h, necessita acionamento ao CMI) |
| ultima atualizacao | timestamp de origem |

**Ligacao operacional com esta release.** As SW4 da frota CEDEC sao a diesel. O
plantonista precisa saber qual POC tem diesel disponivel — e exatamente por isso
que o relatorio atual carrega a lista manual de telefones de diesel da RMBH. Numa
das capturas o diesel do ROTAMPM em Belo Horizonte esta em 0%, indisponivel.

**Bloqueio a resolver antes de planejar a Release 3.** O BI nao expoe API. As
opcoes sao: obter feed de dados com a SUBLOG/GTA, lancamento manual por posto no
NewSDC, ou manter o link externo e internalizar apenas a lista de contatos.
Nenhuma e decisao de codigo — precisa de definicao do usuario.

---

## Apendice B — Formato exato do relatorio a reproduzir

Texto praticado hoje, que o botao Copiar para WhatsApp deve gerar. Os campos
entre chaves sao dinamicos; o restante e literal.

```
Serviço de Plantão ({data} - {periodo_curto})

Assumido por: {plantonista_nome}
Saindo de serviço: {plantonista_saida_nome}

Viaturas em condições de atendimento:
Localização: {localizacao}

🚐 {prefixo} - {placa}{anotacao}
⛽ Combustível: {nivel}
📊 Hodômetro: {hodometro}
📝 Alterações: {alteracoes|Sem alterações}
👨‍✈️ Último condutor: {ultimo_condutor_nome}

[... repete por viatura ...]

Contatos para abastecimento com Diesel (RMBH):
{rodape.contatos_diesel}

LINK VERIFICAÇÃO DE COMBUSTÍVEL POSTOS ORGÂNICOS. A Ferramenta possibilita a
verificação dos níveis de combustíveis em cada Posto Orgânico Compartilhado-POC,
em tempo real. Desta forma, tanto na Capital quanto nas DSP no interior.

{rodape.link_bi}

DTT: {rodape.dtt}
Plantão GMG: {rodape.gmg}

{ocorrencias_destaque|Não houve.}
```

Notas de renderizacao:

- `{periodo_curto}` e a forma abreviada do label do periodo, por exemplo
  `16h às 02h`, nao o label completo do enum.
- `{anotacao}` sai entre parenteses e precedida de espaco quando
  `exclusiva_sobreaviso` e verdadeiro ou quando `anotacao` esta preenchida; sai
  vazio caso contrario.
- `{alteracoes}` vazio renderiza literalmente `Sem alterações`.
- `{ocorrencias_destaque}` vazio renderiza literalmente `Não houve.`; quando
  preenchido, e precedido pela linha
  `Ocorrências ou ações de destaque do turno anterior:`.
- Apenas viaturas com `em_condicoes` verdadeiro entram na listagem.
