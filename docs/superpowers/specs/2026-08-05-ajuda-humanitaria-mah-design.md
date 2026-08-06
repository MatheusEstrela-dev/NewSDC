# Ajuda Humanitaria (MAH) - Isolamento das Regras de Negocio do Legado

Data: 2026-08-05
Modulo alvo: `SDC/app/Modules/AjudaHumanitaria`
Status: design aprovado, pronto para plano de implementacao

## 1. Objetivo

Isolar as regras de negocio vigentes do processo de Pedido de Material de Ajuda
Humanitaria (MAH) e encaixa-las no NewSDC, dentro do conceito de dominio proprio
do modulo `AjudaHumanitaria`, seguindo SOLID e o Atomic Design ja praticado no
projeto.

## 2. Situacao encontrada

### 2.1 O modulo atual do NewSDC e mock

`SDC/app/Modules/AjudaHumanitaria` contem um dominio inventado, sem
correspondencia com o legado: `Beneficiario`, `Abrigo`, `Auxilio`, `Doacao`,
`Estoque`, `MembroFamilia`, com regras sem origem rastreavel (periodo minimo de
30 dias entre auxilios do mesmo tipo, cadastro familiar exigindo dois membros).
Apenas `Beneficiario` tem controller, rota e pagina.

Defeitos do mock:

- `AjudaHumanitariaServiceProvider` faz bind de
  `Domain\Repositories\BeneficiarioRepositoryInterface` para
  `Infrastructure\Persistence\EloquentBeneficiarioRepository`, e nenhuma das
  duas classes existe. Resolver esse binding lanca excecao. A intencao de
  inversao de dependencia foi declarada e nunca construida.
- Migrations de 12 tabelas criadas sem uso real.

Decisao: descartar o mock e construir o dominio do zero.

### 2.2 Duas implementacoes legadas, com autoridades diferentes

| Sistema | Local | Tabelas | Papel |
| --- | --- | --- | --- |
| Legado PHP procedural | `Github/gestaocedec/mod_ajuda` | `aju_h_pedido_*` | Implementacao completa e vigente (~7.5k linhas) |
| Legado Laravel | `Github/sdc/app/{Http/Controllers,Models}/Ajuda` | `aju_pedido_*` | Port parcial e incompleto |

O proprio Laravel consulta as tabelas do gestaocedec via conexao `sdc` em
`PedidoAhController::listPedidoAh`, o que confirma qual base esta viva.

Regras que existem apenas no gestaocedec:

- `status_prest` e `status_retirada` separados do `status` do pedido, com
  `motivo_recusa`
- agenda de retirada de material (`aju_h_agendamento`)
- `aju_h_pedido_itens_original`, preservando a quantidade pedida contra a
  aprovada
- prestacao de contas com validacao de saldo de entrega
- beneficiarios por prestacao de contas, com data de entrega
- lista de material configuravel (`aju_unidade.pedido_h`)

Decisao: gestaocedec e a autoridade; o Laravel do `sdc` complementa com
nomenclatura, labels de status, gate por municipio e validacoes de formulario.

### 2.3 Defeitos do legado documentados para nao replicar

| Defeito | Local | Tratamento |
| --- | --- | --- |
| `AjudaPrestConta::lancarPrestContaItens()` chamada e nunca definida; a transicao para Atendido quebra | `sdc` `AjudaPedidoTramitController.php:52-66` | Implementada corretamente como RN-15, vinda de `iniciaPrestContas` |
| `AjudaPedidoItens::getPedidoItens()` chamada e nunca definida | idem | Coberta por `ItemPedidoService` |
| `compdecVerificaPedido` sempre retorna verdadeiro (`count($dados) > 0` sobre array de uma chave) | `H_pedido_pedidajuda_hModel.php:1583` | Substituida pela regra efetiva em uso (RN-03) |
| `AjudaPedidoConfig` aponta para a tabela `aju_pedido_anexos` em vez da tabela de configuracao | `sdc` `AjudaPedidoConfig.php` | Configuracao passa a ser `parametros_ah` |
| `status` e `tramit` gravados em duplicidade e divergentes entre si (`status()` mapeia 2 para `analise_coord`; o helper mapeia 2 para `Analise Diretor DLOG`) | `sdc` `AjudaPedidoController.php:533`, `mah_helper_function.php:35` | RN-13: `status` como fonte unica, fase derivada |
| Lista de material regredida para 4 itens fixos em codigo, duplicados em `create` e `edit` | `sdc` `AjudaPedidoController.php:179` e `:313` | RN-07: catalogo configuravel |
| `analise_drd` desativado por comentario, restando como vestigio | `h_pedido_an_tec/cadastro.php:71` | Nao entra como estado; REDEC permanece como perfil de consulta regional |

### 2.4 Regras que o legado declara mas nao aplica

Consulta ao banco `gestaocedec_local` mostra que tres regras existem no codigo e
sao violadas nos dados. O modulo novo passa a aplica-las de fato, o que e uma
mudanca de comportamento a comunicar a area.

| Regra | Violacoes no legado | Efeito no modulo novo |
| --- | --- | --- |
| RN-01, numero unico por ano | 2 pares `numero`+`ano` duplicados | Constraint `unique` rejeita |
| RN-03, um pedido em edicao por municipio | 3 municipios com mais de um | Guarda bloqueia a abertura |
| RN-18, entrega nao excede o material | 2 prestacoes com entrega acima | Guarda bloqueia o lancamento |

Como a base nova comeca vazia, nenhum dado historico e invalidado por isso. Caso
a migracao de historico venha a ser feita, esses registros precisarao de
tratamento.

Confirmacao positiva no mesmo levantamento: dos 892 pedidos que chegaram a
Atendido ou Finalizado, **todos** tem itens tipo `L` e **todos** tem prestacao de
contas aberta, o que confirma a RN-15 na pratica.

## 3. Decisoes de escopo

| Tema | Decisao |
| --- | --- |
| Dominio | MAH (Pedido) e o dominio real de Ajuda Humanitaria; o mock e removido |
| Recorte | Pedido completo (itens, parecer, tramitacao, anexos, agendamento, prestacao de contas) mais ponte somente-leitura para o saldo de material da base legada |
| Autoridade das regras | `gestaocedec/mod_ajuda`, complementado pelo Laravel do `sdc` |
| Dados | Base nova comeca vazia; o legado segue no ar para consulta do historico. Migracao, se necessaria, e spec propria |
| Entrega | Backend mais telas Inertia/Vue |
| Estrutura | Dominio proprio do modulo, montado sobre `Controllers`, `Enums`, `Models`, `Services` que ja existem |

Fora de escopo: migracao de historico, estoque nativo (deposito, produto,
movimentacao, liberacao), APIs publicas de BI (`pubajudah`, `pubajudahCedec`,
`saldocesta`, `listPedidoAh`), e os demais modulos que o legado agrupa sob
"Ajuda" (Cisterna, Colete, PMDA, TDAP), que ja tem modulo proprio no NewSDC.

## 4. Catalogo de regras

Rastro: `GC` = `Github/gestaocedec`, `SDC` = `Github/sdc`.

### 4.1 Numeracao e abertura

| # | Regra | Origem |
| --- | --- | --- |
| RN-01 | `numero` = `max(numero)` do ano corrente + 1, comecando em 1. Identificador exibido: `numero/ano` | GC `H_pedido_pedidajuda_hModel.php:1058` (`gerarNumero`) |
| RN-02 | Pedido nasce em status 0 / fase `edicao_compdec`, `ano` = ano corrente, `data_entrada_sistema` = data do formulario com hora atual | GC `:344` (`gravar`) |
| RN-03 | Municipio nao abre pedido novo se ja tem um em edicao (status 0) | GC `:1669` (`buscaStatus`) |
| RN-04 | Obrigatorios: COBRADE, populacao atendida, esforcos realizados (max. 1000 caracteres), municipio | SDC `AjudaPedidoController.php:211` |
| RN-05 | Dados do coordenador pre-preenchidos a partir da equipe COMPDEC com funcao "Coordenador" | SDC `AjudaPedidoController.php:142` |
| RN-06 | Decreto SE/ECP vigente: tipo, numero e vigencia. Contatos de coordenador e de prefeito (nome, telefone, celular, e-mail) | GC `:347` |

### 4.2 Itens do pedido

| # | Regra | Origem |
| --- | --- | --- |
| RN-07 | Material disponivel para pedido e definido por flag configuravel pelo CEDEC, nao por lista fixa em codigo | GC `:1229` (`MaterialPedido`), `:1725` (`PermissaoMaterial`) |
| RN-08 | Item tem quantidade e quantidade de familias atendidas, com discriminador de tipo: `P` = pedido pelo municipio, `L` = liberado pelo CEDEC | GC `:736` (`item_pedido`) |
| RN-09 | O que foi pedido e preservado quando o CEDEC reduz a quantidade liberada | GC `:807` (`item_pedido_original`) |

### 4.3 Parecer tecnico

| # | Regra | Origem |
| --- | --- | --- |
| RN-10 | Parecer tem data, texto, situacao (favoravel ou contrario) e a etapa a que pertence | GC `H_pedido_an_tecajuda_hModel.php:142` |
| RN-11 | Avanco de Analise DLOG para frente exige ao menos um parecer favoravel | SDC `AjudaPedidoController.php:322` |

### 4.4 Maquina de estados

```
0 Edicao Compdec ──> 1 Analise DLOG ──> 2 Analise Diretor DLOG ──> 3 Aprovado
                          │                                            │
                          v (devolve para correcao)                    v
                     0 Edicao Compdec              4 Aguard. Disponibilidade
                                                                       │
                                                                       v
                                              5 Aguard. Retirada ──> 6 Atendido
                                                                  (Prest. Contas)
                                                                       │
                                                                       v
                                                              9 Finalizado

Saidas: 7 Cancelado, 8 Reprovado
```

| Status | Label | Fase derivada |
| --- | --- | --- |
| 0 | Edicao Compdec | `edicao_compdec` |
| 1 | Analise DLOG | `analise_dlog` |
| 2 | Analise Diretor DLOG | `analise_coord` |
| 3 | Aprovado | `aprovado` |
| 4 | Aguardando Disponibilidade Mat. | `aguard_disp` |
| 5 | Aguardando Retirada Mat. | `aguard_ret` |
| 6 | Atendido | `atendido` |
| 7 | Cancelado | `cancelado` |
| 8 | Reprovado | `reprovado` |
| 9 | Processo Finalizado | `finalizado` |

| # | Regra | Origem |
| --- | --- | --- |
| RN-12 | Transicoes permitidas dependem do status atual; nao e lista fixa. Matriz definida em 4.4.1 | SDC `AjudaPedidoController.php:329-382`, `AjudaPedidoTramitController.php:16` (`getTramitList`) |
| RN-13 | `status` e a fonte unica de verdade; a fase e derivada dele | Decisao de design, corrige divergencia do legado |
| RN-14 | Toda transicao grava log com status anterior, novo, observacao, usuario e timestamp | GC `:1794` (`logTramita`) |
| RN-15 | Entrada em Atendido copia os itens tipo `L` para a prestacao de contas | GC `:1610` (`iniciaPrestContas`), `:1635` (`lancaMaterialPrest`) |

#### 4.4.1 Matriz de transicoes (derivada do log real)

As duas fontes de codigo do legado se contradizem. `AjudaPedidoController::edit`
(SDC `:329-382`) monta uma lista de destinos por status;
`AjudaPedidoTramitController::getTramitList` (SDC `:16`) monta outra, diferente.
Nenhuma das duas descreve o processo: sao artefatos de interface escritos em
momentos distintos.

A resposta nao esta no codigo e sim no dado. A tabela `aju_h_pedido_tramit_log`
do banco `gestaocedec_local` registra **1.969 transicoes reais**. A matriz abaixo
foi extraida delas.

Transicoes observadas, por frequencia:

| De | Para | Ocorrencias |
| --- | --- | ---: |
| 2 Analise Diretor | 5 Aguard. Retirada | 650 |
| 5 Aguard. Retirada | 6 Atendido | 417 |
| 1 Analise DLOG | 0 Edicao Compdec | 280 |
| 2 Analise Diretor | 6 Atendido | 208 |
| 2 Analise Diretor | 1 Analise DLOG | 132 |
| 2 Analise Diretor | 3 Aprovado | 54 |
| 5 Aguard. Retirada | 1 Analise DLOG | 39 |
| 2 Analise Diretor | 7 Cancelado | 32 |
| 6 Atendido | 1 Analise DLOG | 27 |
| 2 Analise Diretor | 4 Aguard. Disponib. | 20 |
| 5 Aguard. Retirada | 7 Cancelado | 20 |
| demais (3 e 4 para frente e para tras, 6 para 5, 6 para 7) | | 40 |

O achado central: **o processo nao e linear**. Da analise do Diretor o pedido e
despachado direto para a etapa que precisa, e `2 para 5` sozinho responde por um
terco de todas as transicoes. Qualquer etapa pos-analise pode voltar para
reanalise. Uma matriz linear `2-3-4-5-6` bloquearia 1.016 das 1.969 transicoes
registradas, ou 52% do que a area faz.

Matriz normativa deste modulo:

| De | Para | Condicao |
| --- | --- | --- |
| 0 Edicao Compdec | 1 Analise DLOG | ao menos um item tipo `P` |
| 0 Edicao Compdec | 7 Cancelado | - |
| 1 Analise DLOG | 2 Analise Diretor | ao menos um parecer favoravel (RN-11) |
| 1 Analise DLOG | 0, 8, 7 | - |
| 2 Analise Diretor | 3, 4, 5, 6 | despacho direto; para 6, exige itens `L` |
| 2 Analise Diretor | 1, 8, 7 | - |
| 3 Aprovado | 4, 5, 6, 1, 2, 7 | para 6, exige itens `L` |
| 4 Aguard. Disponib. | 5, 6, 1, 2, 7 | para 6, exige itens `L` |
| 5 Aguard. Retirada | 6, 4, 1, 2, 7 | para 6, exige itens `L` |
| 6 Atendido | 9 Finalizado | somente via homologacao (RN-19) |
| 6 Atendido | 5, 4, 1, 2, 7 | reabertura, observada 33 vezes |
| 7, 8, 9 | - | terminais |

Duas exclusoes deliberadas em relacao ao log:

- **Auto-transicoes** (`2 para 2`, `4 para 4`, `5 para 5`, `6 para 6`), 31
  ocorrencias, ficam de fora: sao re-salvamento de parecer na mesma etapa, nao
  mudanca de estado.
- **`0 para 1` nao aparece no log** porque `envia_pedido` grava o status sem
  chamar `logTramita`. A transicao existe no processo; e o registro dela que
  falta. No modulo novo ela passa a ser logada como qualquer outra.

Uma unica trava e acrescentada ao que o legado permitia: nao se envia pedido
vazio para analise. Nenhuma transicao registrada e bloqueada por ela.

Cores por status, para o badge, conforme `getCorStatus` (GC `:1290`):

| Status | Fundo | Fonte |
| --- | --- | --- |
| 0 | `#F3E2A9` | `#2E2E2E` |
| 1 | `#D8D8D8` | `#000000` |
| 2 | `#2E64FE` | `#FFFFFF` |
| 3 | `#FE642E` | `#151515` |
| 4 | `#9F81F7` | `#FFFFFF` |
| 5 | `#FFD700` | `#000000` |
| 6 | `#90EE90` | `#2E2E2E` |
| 7 | `#B40404` | `#FFFFFF` |
| 8 | `#6E6E6E` | `#FFFFFF` |

Os valores acima sao o mapeamento semantico do legado. Na implementacao devem
ser convertidos para tokens Tailwind do tema do NewSDC, respeitando modo claro
e escuro, e nao aplicados como hexadecimal cru.

### 4.5 Prestacao de contas e retirada

| # | Regra | Origem |
| --- | --- | --- |
| RN-16 | Prazo da prestacao = data de aprovacao mais N dias, com N configuravel globalmente | GC `:1748` (`prazo_presta_conta`), `config_ajuda.php:36` |
| RN-17 | Beneficiario da entrega: nome, RG, comunidade, quantidade e data de entrega | GC `H_pedido_benefajuda_hModel.php:463` |
| RN-18 | A soma das quantidades entregues aos beneficiarios nao pode exceder a quantidade de material daquele item da prestacao; o saldo e material menos entregue | GC `H_pedido_benefajuda_hModel.php:498` (`verificaRestanteBenef`), `H_pedido_prestajuda_hModel.php` (`percBenef`, `QtdMaterialPrest`) |
| RN-19 | Homologacao da prestacao leva o processo a status 9 / finalizado | GC `h_pedido_prestController.php:170` (`homologa`) |
| RN-20 | REDEC nao acessa prestacao de contas | GC `h_pedido_prest/index.php:27` |
| RN-21 | Agendamento de retirada: slot de horario, status (pendente, aprovado, recusado), motivo da recusa, usuario e data de aprovacao. Aprovar dispara e-mail ao municipio. **Ver ressalva abaixo** | GC `:443` (`update_status_agenda`), `:478`, `:2008` (`gravarAgendamento`), `:2089` (`buscar_horarios`) |

**Ressalva sobre a RN-21.** O codigo do gestaocedec escreve em
`aju_h_agendamento`, mas **essa tabela nao existe no banco**: nao ha nenhuma
tabela com "agend" no nome em `gestaocedec_local`. Os 417 pedidos que
transitaram de Aguardando Retirada para Atendido o fizeram sem qualquer
agendamento. A regra e, portanto, **codigo sem lastro em producao** — feature
inacabada, ou implantada em outro ambiente.

Consequencia de projeto: a tabela `pedido_ah_agendamentos` e o enum
`StatusAgendamento` permanecem no schema, por serem aditivos e inofensivos, mas
**nenhuma guarda de transicao depende deles**. Uma guarda exigindo agendamento
aprovado para atingir Atendido tornaria o modulo inoperante. Antes de ativar a
funcionalidade, confirmar com a area se o agendamento e um processo real.

### 4.6 Anexos, autorizacao e saldo

| # | Regra | Origem |
| --- | --- | --- |
| RN-22 | Anexo do pedido: PDF, maximo 2 MB | SDC `AjudaPedidoController.php:457` |
| RN-23 | Perfis: COMPDEC (escreve o proprio pedido), Analista DLOG, Diretor DLOG, REDEC (consulta por regiao) | GC `:1339` (`listaAnalistaPedidoAjuda`), tabela `aju_h_permissao` |
| RN-24 | COMPDEC ve e edita apenas pedidos do proprio municipio; REDEC filtra por regiao | SDC `AuthServiceProvider.php:139` (gate `mah`), GC `listaPedidosTodos` |
| RN-25 | Saldo de material por deposito lido da base legada, ignorando saldo zero | SDC `AjudaHumanitariaController::saldoCesta` |

Sobre RN-23 e RN-24: o legado usa tabela de permissao propria com flags por
usuario (`analista_drd`, `analista_dlog`, `analista_coord`). No NewSDC isso passa
para `spatie/laravel-permission` e `config/permissions.php`, sem tabela paralela.

## 5. Arquitetura

### 5.1 Dominio proprio do modulo

O dominio e construido por cima das pastas que o modulo ja tem, sem importar
convencao de outro modulo. As regras ficam em classes puras, sem Eloquent e sem
facades, testaveis sem banco.

```
SDC/app/Modules/AjudaHumanitaria/
  AjudaHumanitariaServiceProvider.php     binds reais e colecao de guardas
  Domain/
    PedidoAhWorkflow.php                  RN-11, RN-12
    Contracts/GuardaPedido.php
    Contracts/ResultadoGuarda.php
    Guards/MunicipioPodeAbrirPedido.php   RN-03
    Guards/ExigeParecerFavoravel.php      RN-11
    Guards/SaldoEntregaBeneficiarios.php  RN-18
    Guards/PrazoPrestacaoContas.php       RN-16
    Repositories/PedidoAhRepositoryInterface.php
    Repositories/PrestacaoContaRepositoryInterface.php
    Repositories/MaterialAhRepositoryInterface.php
    Repositories/SaldoMaterialRepositoryInterface.php
  Infrastructure/Persistence/
    EloquentPedidoAhRepository.php
    EloquentPrestacaoContaRepository.php
    EloquentMaterialAhRepository.php
    LegadoSaldoMaterialRepository.php     conexao legacy, somente leitura
  Enums/
    StatusPedidoAh.php                    0..9, label, cor, fase, transicoes
    FasePedidoAh.php
    TipoItemPedido.php                    Pedido, Liberado
    SituacaoParecer.php                   Favoravel, Contrario
    EtapaParecer.php                      AnaliseDlog, AnaliseDiretor
    TipoDecreto.php                       SE, ECP
    StatusAgendamento.php                 Pendente, Aprovado, Recusado
    StatusPrestacaoConta.php              Pendente, EmLancamento, Homologada
  Models/
    PedidoAh.php  PedidoAhItem.php  PedidoAhParecer.php  PedidoAhTramite.php
    PedidoAhAgendamento.php  PrestacaoConta.php  PrestacaoContaItem.php
    PrestacaoContaEntrega.php  MaterialAh.php  ParametroAh.php
  DTOs/
    PedidoAhDTO.php  ItemPedidoDTO.php  TransicaoPedidoDTO.php
    ParecerDTO.php  EntregaBeneficiarioDTO.php  AgendamentoDTO.php
  Services/
    PedidoAhService.php  NumeracaoPedidoService.php  ItemPedidoService.php
    ParecerService.php  TramitacaoService.php  PrestacaoContasService.php
    AgendamentoRetiradaService.php  SaldoMaterialService.php
  Controllers/  Requests/  Resources/  Policies/  Mail/
```

`StatusPedidoAh` segue o padrao do enum de estado ja existente no projeto
(`app/Modules/Tdap/Enums/EstadoProcesso.php`): `label()`,
`transicoesPermitidas()`, `podeTransitarPara()`, `options()`.

### 5.2 SOLID aplicado

| Principio | Aplicacao |
| --- | --- |
| Responsabilidade unica | `TramitacaoService` e o unico ponto que altera status. `NumeracaoPedidoService` apenas numera. `ParecerService` apenas emite parecer |
| Aberto/fechado | Novo estado ou transicao entra em `StatusPedidoAh` e `PedidoAhWorkflow`; nenhum service muda. Nova guarda implementa `GuardaPedido` e e registrada no provider |
| Substituicao de Liskov | Toda guarda satisfaz `GuardaPedido::verificar(PedidoAh): ResultadoGuarda`; `TramitacaoService` itera a colecao sem conhecer as implementacoes |
| Segregacao de interface | Interfaces por caso de uso, nao repositorio unico. Quem le saldo nao carrega metodo de escrita de pedido |
| Inversao de dependencia | Controllers e Services dependem das interfaces em `Domain/Repositories`; Eloquent fica em `Infrastructure/Persistence` |

`SaldoMaterialRepositoryInterface` atras de `LegadoSaldoMaterialRepository` e o
retorno concreto da inversao: quando o estoque virar nativo, troca-se o bind.

### 5.3 Efeitos colaterais da tramitacao

`TramitacaoService` consulta `PedidoAhWorkflow`, aplica as guardas, grava o
tramite (RN-14) e dispara:

- entrada em 3 (Aprovado): grava `data_aprovacao`, base do prazo da RN-16
- entrada em 6 (Atendido): abre a prestacao de contas copiando os itens tipo `L`
  (RN-15)
- entrada em 9 (Finalizado): somente via homologacao da prestacao (RN-19)

## 6. Schema

Banco: PostgreSQL (`pgsql`, conexao default do `.env`). Chaves estrangeiras em
`municipios` e `dec_cobrade`, como nos demais modulos.

```
pedidos_ah
  numero, ano                       unique(numero, ano)          RN-01
  municipio_id -> municipios        regiao_id
  cobrade_id -> dec_cobrade         pop_atendida
  decreto_se_ecp_vig, tipo_decreto, numero_decreto, vigencia_decreto  RN-06
  esforcos_realizados
  nome/tel/cel/email _coordenador   nome/tel/cel/email _prefeito
  status (smallint 0..9)                                         RN-13
  analista_id, diretor_id -> users
  data_entrada_sistema, data_hora_envio, data_aprovacao
  created_by, timestamps, softDeletes
  index (municipio_id, status), index (ano, status)

pedido_ah_itens
  pedido_ah_id, material_ah_id, codigo, descricao_item,
  qtd, qtd_familia_atendida, tipo (P|L)
  index (pedido_ah_id, tipo)                                     RN-08, RN-09

pedido_ah_pareceres
  pedido_ah_id, user_id, data_parecer, parecer, situacao, etapa   RN-10

pedido_ah_tramites
  pedido_ah_id, status_anterior, status_novo, observacao,
  user_id, created_at                                            RN-14

pedido_ah_agendamentos
  pedido_ah_id, municipio_id, data_retirada, horario, status,
  motivo_recusa, usuario_aprovacao_id, data_aprovacao            RN-21

prestacoes_conta
  pedido_ah_id (unique), status, data_limite,
  homologado_por, homologado_em                              RN-16, RN-19
prestacao_conta_itens
  prestacao_conta_id, material_ah_id, codigo_material,
  nome_material, qtd, total_familia_atendida                     RN-15
prestacao_conta_entregas
  prestacao_conta_item_id, nome_beneficiario, rg, comunidade,
  qtd, data_entrega                                        RN-17, RN-18

materiais_ah
  nome, descricao, unidade_medida,
  disponivel_para_pedido (bool), codigo_legado (nullable)         RN-07

parametros_ah
  linha unica: prazo_prestacao_contas_dias                        RN-16
```

Anexos (RN-22) via `spatie/laravel-medialibrary`, que o projeto ja usa, sem
tabela nem storage proprio.

### 6.1 Migrations

Ja existem 12 migrations do mock com prefixo `2025_12_28_1200xx`. Elas sao
reescritas no schema novo, em vez de empilhar arquivos datados novos, e as que
nao tem correspondencia sao removidas: `doacoes`, `itens_doacao`,
`movimentacoes_financeiras`, `membros_familia`, `beneficiario_abrigo`,
`itens_auxilio`.

As migrations permanecem em `SDC/database/migrations`, porque nenhum modulo do
projeto carrega migration de dentro de si.

### 6.2 Tres desvios deliberados do legado

| # | Desvio | Motivo |
| --- | --- | --- |
| 1 | Sem tabela de itens originais. O discriminador `P`/`L` ja preserva o pedido contra o liberado; os itens `P` sao congelados quando o pedido sai do status 0 | O legado mantem tres representacoes do mesmo item, que podem divergir entre si |
| 2 | Fase derivada de `status` | Elimina a divergencia entre `status` e `tramit` observada no legado |
| 3 | `materiais_ah` pertence ao NewSDC, com `codigo_legado` de ponte | Devolve a configurabilidade da RN-07 mantendo o casamento com o saldo do deposito legado da RN-25 |

### 6.3 Ponte de saldo legado

`LegadoSaldoMaterialRepository` usa a conexao `legacy` ja configurada em
`SDC/config/database.php:67` (MySQL 3306), somente leitura, com cache curto,
seguindo o padrao de `app/Modules/Rat/Services/LegadoRatService.php`. Nenhuma
escrita na base legada.

Se a conexao estiver indisponivel, a tela do status 4 degrada exibindo saldo
indisponivel, sem lancar excecao.

## 7. Permissoes e autorizacao

Em `SDC/config/permissions.php`, substituindo o bloco
`humanitaria.beneficiarios.*`:

```
humanitaria.pedidos.view
humanitaria.pedidos.create
humanitaria.pedidos.edit
humanitaria.pedidos.delete
humanitaria.pedidos.print
humanitaria.pedidos.export
humanitaria.pedidos.tramitar
humanitaria.pedidos.parecer
humanitaria.pedidos.liberar_itens
humanitaria.prestacao.view
humanitaria.prestacao.lancar
humanitaria.prestacao.homologar
humanitaria.agendamento.view
humanitaria.agendamento.aprovar
humanitaria.materiais.manage
humanitaria.parametros.manage
```

`PedidoAhPolicy` cobre o escopo por registro (RN-24): COMPDEC restrito ao
proprio municipio, REDEC restrito a regiao e sem acesso a prestacao de contas
(RN-20). A permissao define o que o usuario pode fazer; a policy define sobre
qual registro.

## 8. Frontend

Atomic Design conforme o codigo-fonte do projeto: Page (Inertia) -> Template ->
Organisms -> Molecules -> Atoms.

```
Pages/AjudaHumanitaria/Pedidos/{Index,Create,Edit,Show}.vue
Templates/AjudaHumanitaria/{PedidoAhIndex,PedidoAhForm,PedidoAhShow}Template.vue

Atoms/AjudaHumanitaria/PedidoAhStatusBadge.vue
Molecules/AjudaHumanitaria/PedidoAhCard.vue
Organisms/AjudaHumanitaria/
  PedidoAhFiltersSection.vue
  PedidoAhGrid.vue
  TramitacaoTimeline.vue
  Statistics/PedidoAhStatisticsCards.vue
  Print/PrintPedidoAhModal.vue
  Tabs/{DadosPedido,Itens,Pareceres,Anexos,Tramitacao,PrestacaoContas}Tab.vue
  Modals/{Tramitar,Parecer,LiberarItens,AgendarRetirada,LancarEntrega}Modal.vue
```

Reaproveitamento obrigatorio, sem recriar equivalente:

| Necessidade | Componente existente |
| --- | --- |
| Cabecalho de pagina | `Organisms/PageHeader.vue` |
| Container de listagem, vazio, alternancia de visao | `Organisms/ListContainer.vue`, `Molecules/ListEmptyState.vue`, `Molecules/ViewModeToggle.vue` |
| Paginacao | `Molecules/Navigation/Pagination.vue` |
| Tabela responsiva | `Organisms/Table/ResponsiveTable.vue`, `Molecules/Table/{SortableHeader,TableDataRow,TableHeaderRow,TableMobileCard}.vue` |
| Acoes de linha | `Atoms/Button/ActionButton.vue`, `Atoms/Button/PermissionButton.vue` |
| Filtros | `Molecules/Filter/{FilterSection,FilterField,FilterActions,ActiveFilters,CobradeFilter}.vue` |
| Formulario | `Molecules/Form/{FormField,FormSelect,FormDateField,FormTextarea,FormActions,ToggleField}.vue`, `Organisms/FormSection.vue` |
| Cards de estatistica | `Molecules/Statistics/{StatCard,StatCardsGrid}.vue` |
| Upload de anexo | `Molecules/Upload/{DropZone,FileUploadItem}.vue` |
| Exportacao | `Organisms/ExportCsvModal.vue`, composable `useExport` |
| Carregamento | `Molecules/Skeleton/{TableSkeleton,StatsSkeleton,FormSkeleton}.vue` |
| Icone do modulo | `Support/moduleIcons` |

`Molecules/Table/TableActions.vue` nao deve ser usado: esta em extincao pelo PR
de refatoracao do `ActionButton`.

As abas espelham o legado (Dados do Pedido, Materiais, Documentos,
Despachos/Analises), somando Tramitacao e Prestacao de Contas.
`LiberarItensModal` exibe pedido e liberado lado a lado, materializando o desvio
1 da secao 6.2.

## 9. Testes

Unitarios, sem banco, onde a regra vive:

- `PedidoAhWorkflow`: matriz completa de transicoes por status, papel e presenca
  de parecer favoravel (RN-11, RN-12)
- `StatusPedidoAh`: label, fase derivada e transicoes (RN-13)
- Cada guarda isoladamente (RN-03, RN-11, RN-16, RN-18)

De feature, com banco:

- numeracao anual, inclusive sob criacao concorrente (RN-01)
- bloqueio de segundo pedido em edicao para o mesmo municipio (RN-03)
- entrada em Atendido gerando a prestacao com os itens tipo `L` (RN-15)
- entrega de beneficiario estourando o saldo do item (RN-18)
- homologacao levando o processo a 9 (RN-19)
- escopo da policy por municipio e por regiao (RN-24, RN-20)
- anexo recusando arquivo nao PDF e acima de 2 MB (RN-22)
- `LegadoSaldoMaterialRepository` com conexao indisponivel degradando sem
  excecao (RN-25)

## 10. Remocao do mock

Arquivos a remover:

```
app/Modules/AjudaHumanitaria/Models/
  Beneficiario.php  Abrigo.php  Auxilio.php  Estoque.php
  ItemAuxilio.php  MembroFamilia.php  MovimentacaoEstoque.php
app/Modules/AjudaHumanitaria/Enums/
  StatusBeneficiario.php  SituacaoVulnerabilidade.php
  TipoCadastroBeneficiario.php  StatusAbrigo.php  TipoLocalAbrigo.php
app/Modules/AjudaHumanitaria/Controllers/BeneficiarioController.php
app/Modules/AjudaHumanitaria/Services/
  BeneficiarioService.php  AjudaHumanitariaStatsService.php
resources/js/Pages/AjudaHumanitaria/Beneficiarios/BeneficiarioIndex.vue
resources/js/Templates/AjudaHumanitaria/BeneficiarioIndexTemplate.vue
resources/js/Components/Organisms/AjudaHumanitaria/
  BeneficiarioFiltersSection.vue  BeneficiarioGrid.vue
  BeneficiarioStatsCards.vue  Print/PrintBeneficiarioModal.vue
resources/js/Components/Molecules/AjudaHumanitaria/BeneficiarioCard.vue
database/seeders/AjudaHumanitariaSeeder.php
```

Alteracoes: bloco `humanitaria.beneficiarios.*` em `config/permissions.php`
substituido pelo da secao 7; `routes/modules/ajuda-humanitaria.php` reescrito;
`AjudaHumanitariaServiceProvider` com binds reais.

## 11. Faseamento

O escopo e grande para um unico plano de implementacao: dez tabelas, camada de
dominio, oito services, quatro paginas e cerca de vinte componentes. A sugestao
e quebrar em tres planos sequenciais, cada um verificavel por si:

| Fase | Conteudo | Verificacao |
| --- | --- | --- |
| 1. Dominio e schema | Enums, `PedidoAhWorkflow`, guardas, contratos, models, migrations reescritas, remocao do mock | Testes unitarios do workflow e das guardas passam sem banco; migrations sobem e descem |
| 2. Aplicacao | Repositories, services, DTOs, requests, resources, controllers, rotas, permissoes, policy, ponte de saldo legado | Testes de feature das RN-01, 03, 15, 18, 19, 22, 24, 25 passam |
| 3. Interface | Paginas, templates, organisms, molecules e atoms; abas e modais | `npm run build` passa; fluxo completo exercitado na aplicacao |

Cada fase e um plano proprio em `docs/superpowers/plans`.

## 12. Criterios de verificacao

1. As 25 regras tem implementacao rastreavel e teste correspondente
2. `PedidoAhWorkflow` e as guardas rodam sem banco e sem framework
3. Nenhum caminho altera `status` fora de `TramitacaoService`
4. `php artisan test --filter=AjudaHumanitaria` passa
5. `npm run build` passa
6. Nenhuma referencia remanescente a `Beneficiario`, `Abrigo`, `Auxilio`,
   `Doacao` ou `MembroFamilia` no modulo, nas rotas, nas permissoes ou no
   frontend
7. Fluxo completo exercitado na aplicacao: abertura pelo COMPDEC, envio,
   parecer, liberacao com corte de quantidade, aprovacao, agendamento,
   atendimento, lancamento de entrega e homologacao
