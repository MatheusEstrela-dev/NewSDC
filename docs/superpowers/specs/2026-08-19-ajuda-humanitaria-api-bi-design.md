# Ajuda Humanitaria - API Swagger de fornecimento de dados

Data: 2026-08-19
Modulo alvo: `SDC/app/Http/Controllers/Api/V1/AjudaHumanitaria`
Fonte de dados: PostgreSQL do NewSDC (`newsdc_dev_db`, host `localhost:5434`, base `sdc`)
Status: implementado (ver plano 2026-08-19-ajuda-humanitaria-api-bi.md)

## 1. Objetivo

Expor, na API v1 do NewSDC, os dados de Ajuda Humanitaria que hoje sao servidos
pelos endpoints publicos do Laravel legado (`Github/sdc`), com documentacao
Swagger, mantendo **paridade de contrato** com esses endpoints.

O design de 2026-08-05 (`2026-08-05-ajuda-humanitaria-mah-design.md`) deixou
essas APIs explicitamente fora de escopo. Esta spec fecha essa lacuna.

## 2. Decisoes tomadas

| Tema | Decisao |
| --- | --- |
| Recorte | Paridade com os 4 endpoints legados: `pubajudah`, `pubajudahCedec`, `saldocesta`, `listPedidoAh` |
| Fonte | Banco novo (PostgreSQL 5434). O legado nao e consultado em runtime |
| Exposicao | `auth:sanctum` + `CheckUserActive` + `can:humanitaria.*`, seguindo o padrao de PAE e Decretacoes. **Quebra compatibilidade** com quem chama a URL publica hoje |
| Campos sem coluna | Promovidos a colunas reais com backfill (`valor`, `peso`, `singular`, `categoria` em `materiais_ah`; `evento` em `ajuda_h_liberacoes`) |
| ETL faltante | Fora do escopo desta spec (ver secao 6). Os endpoints que dependem dele sao entregues com contrato correto e retorno vazio |

## 3. Situacao do banco novo

Levantamento executado em 2026-08-19 contra `newsdc_dev_db`.

### 3.1 Tabelas com dado real

| Tabela | Linhas | Papel |
| --- | --- | --- |
| `ajuda_h_liberacoes` | 3.582 | Cabecalho da liberacao (municipio, deposito, data, status) |
| `ajuda_h_liberacao_recibos` | 3.363 | Pagamento/recibo da liberacao |
| `ajuda_h_estoque_saldos` | 118 | Saldo por (material, deposito) |
| `ajuda_h_estoque_movimentos` | 118 | Movimento de estoque |
| `ajuda_h_depositos` | 24 | Depositos |
| `materiais_ah` | 187 | Catalogo de material |
| `ajuda_h_legado_raw` | ver 3.3 | Staging do ETL (`tabela`, `pk_legado`, `doc` jsonb) |

### 3.2 Tabelas vazias que o contrato exige

| Tabela | Linhas | Consequencia |
| --- | --- | --- |
| `ajuda_h_liberacao_itens` | **0** | `pubajudah` e `pubajudahCedec` nao tem itens nem quantidades |
| `pedidos_ah` | **0** | `listPedidoAh` sem fonte |
| `pedido_ah_itens` | **0** | idem |
| `prestacao_conta_itens` | 0 | Nao usado pelos 4 contratos |

### 3.3 Staging: o que foi extraido do legado

| `tabela` | Linhas |
| --- | --- |
| `aju_estoque` | 4.510 (118 com `saldo <> 0` -- confere com `ajuda_h_estoque_saldos`) |
| `aju_liberacao` | 3.582 |
| `aju_pagamento` | 3.364 |
| `aju_municipio` | 853 |
| `aju_produto` | 752 |
| `aju_item_transf` | 207 |
| `aju_unidade` | 187 |
| `aju_transferencia` | 70 |
| `aju_fonte` | 36 |
| `aju_deposito` | 24 |
| `aju_fornecedores` | 10 |
| `aju_unidade_descr` | 3 |

**`aju_item` esta declarada em `MapaTabelasLegado` e tem 0 linhas na staging.**
A ausencia dos itens de liberacao e uma falha de **extracao**, nao de
refinamento. `aju_transf` e `aju_cfornecedor` estao no mesmo estado.

A implementacao apurou a causa raiz: o dump usado na carga
(`SDC/database/data/aju_humanitaria.sql`, 5,9 MB) **nao contem INSERT de
`aju_item`**, nem de `aju_h_pedido_pedid` / `aju_h_pedido_itens`. Nao e ajuste de
script de extracao: destravar `pubajudahCedec` e `listPedidoAh` exige um dump novo
da base legada, com essas tabelas.

`aju_h_pedido_pedid` e `aju_h_pedido_itens` (fonte do `listPedidoAh`) **nao
constam no mapa de carga** -- nunca foram consideradas.

`aju_produto` tem 752 linhas na staging mas **nao existe tabela destino** no
schema novo; o array `produtos` do `pubajudah` nao tem onde morar.

## 4. Mapeamento campo a campo

### 4.1 `GET /api/v1/ajuda-humanitaria/liberacoes` (paridade `pubajudah`)

Legado: `AjudaHumanitariaController::apiAllDataAjudah`, `AjudaLiberacaoTransformer`,
`AjudahLiberacaoResource`.

Query params (identicos ao legado): `ano_comeco` (obrigatorio, 4 digitos, min
1900), `ano_fim` (opcional, max ano corrente), `evento` (opcional, enum
`AJUDA HUMANITARIA|CEDEC|CHUVA|COVID-19|OUTROS|SECA`).

Resposta: objeto agrupado por ano, com `meta.totais`.

| Campo da resposta | Fonte no banco novo | Situacao |
| --- | --- | --- |
| `id_liberacao` | `ajuda_h_liberacoes.codigo_legado` | OK (ver 5.1) |
| `data_liberacao` | `ajuda_h_liberacoes.data_libera` | OK |
| `hora_liberacao` | `payload_legado->>'hora_libera'` | OK (sempre nulo na amostra) |
| `mes` | derivado de `data_libera` | OK |
| `evento` | **nova coluna** `ajuda_h_liberacoes.evento` | Migration + backfill |
| `situacao` | `ajuda_h_liberacoes.status` mapeado `0=Aberto, 1=Pago, 2=Cancelado` | OK |
| `unidade.id_municipio` | `municipios.id` | OK |
| `unidade.codmundv` | `municipios.codigo_ibge` | OK, equivalencia confirmada (5.2) |
| `unidade.nome` | `municipios.nome` | OK |
| `items_quant` | `SUM(ajuda_h_liberacao_itens.qtd)` | **Bloqueado** (tabela vazia) |
| `items[].id_item` | `ajuda_h_liberacao_itens.id` | **Bloqueado** |
| `items[].quantidade` | `ajuda_h_liberacao_itens.qtd` | **Bloqueado** |
| `items[].produtos[]` | sem tabela destino | **Bloqueado** (ver 5.3) |
| `meta.totais.total_registros` | `count(*)` | OK |
| `meta.totais.total_pagas` | `count(status = 1)` | OK |
| `meta.totais.total_aberto` | `count(status = 0)` | OK |
| `meta.totais.total_canceladas` | `count(status = 2)` | OK |

Com a carga atual, o endpoint responde cabecalho e totais corretos, com
`items: []` e `items_quant: 0`.

### 4.2 `GET /api/v1/ajuda-humanitaria/liberacoes/cedec` (paridade `pubajudahCedec`)

Legado: `apiDataAjudaCedec`. Lista plana, sem paginacao, filtro
`aju_item.situacao IN (0, 1)`.

| Campo | Fonte no banco novo | Situacao |
| --- | --- | --- |
| `id_municipio` | `ajuda_h_liberacoes.municipio_id` | OK |
| `Codmundv` | `municipios.codigo_ibge` | OK, equivalencia confirmada (5.2) |
| `municipio` | `municipios.nome` | OK |
| `dataLibera` | `ajuda_h_liberacoes.data_libera` | OK |
| `quantidade` | `ajuda_h_liberacao_itens.qtd` | **Bloqueado** |
| `id_material` | `materiais_ah.codigo_legado` | OK |
| `material` | `coalesce(materiais_ah.singular, materiais_ah.nome)` | OK (5.4) |
| `evento` | nova coluna `evento` | Migration + backfill |
| `deposito` | `ajuda_h_depositos.nome` | OK |
| `status` | `ajuda_h_liberacao_itens.status` | **Bloqueado** |

Como o filtro e o recorte de linhas vem de `ajuda_h_liberacao_itens`, este
endpoint retorna **lista vazia** ate a carga de itens existir.

### 4.3 `GET /api/v1/ajuda-humanitaria/estoque/saldo-cesta` (paridade `saldocesta`)

Legado: `saldoCesta`. Filtros: `aju_unidade.categoria = 'CESTA BASICA'` e
`aju_estoque.saldo <> 0`; agrupado por deposito.

| Campo | Fonte no banco novo | Situacao |
| --- | --- | --- |
| `id_deposito` | `ajuda_h_estoque_saldos.deposito_id` | OK |
| `nome` | `ajuda_h_depositos.nome` | OK |
| `total_saldo` | `SUM(ajuda_h_estoque_saldos.saldo)` | OK |
| `singular` | `coalesce(materiais_ah.singular, materiais_ah.nome)` | OK (5.4) |
| `valor` | **nova coluna** `materiais_ah.valor` | Migration + backfill (fonte na staging) |
| `peso` | **nova coluna** `materiais_ah.peso`, aplicando `floor()` | Migration + backfill (fonte na staging) |
| filtro `categoria` | **nova coluna** `materiais_ah.categoria` | Migration + backfill por nome (5.5) |

Este e o unico dos quatro endpoints com dado completo hoje: 118 saldos, 24
depositos, 187 materiais.

### 4.4 `GET /api/v1/ajuda-humanitaria/pedidos/consolidado` (paridade `listPedidoAh`)

Legado: `PedidoAhController::listPedidoAh`, com dois modos mutuamente
exclusivos:

- `?decreto_id=<n>`: filtra `num_decreto`, exige `tramit IN ('finalizado','atendido')`, agrupa por municipio
- `?bi=1`: sem filtro de status, agrupa por `num_decreto` + `descricao_item`

| Campo | Fonte no banco novo | Situacao |
| --- | --- | --- |
| `status` | `pedidos_ah.status` (enum `StatusPedidoAh`) | **Bloqueado** (tabela vazia) |
| `descricao_item` | `pedido_ah_itens.descricao_item` | **Bloqueado** |
| `tp_item` | `pedido_ah_itens.tipo` (`P`/`L`, tipo `char`) | **Bloqueado** |
| `municipio` | `municipios.nome` | OK |
| `num_decreto` | `pedidos_ah.numero_decreto` | **Bloqueado** |
| `total_qtd` | `SUM(pedido_ah_itens.qtd)` | **Bloqueado** |

`descricao_item` e coluna propria de `pedido_ah_itens`, como no legado: o texto
que o municipio escreveu e preservado, e nao ha join com `materiais_ah`.

Mapeamento de status legado -> enum novo, para o modo `decreto_id`:
`'atendido'` -> `StatusPedidoAh::Atendido` (6), `'finalizado'` ->
`StatusPedidoAh::Finalizado` (9). O legado gravava `tramit` como texto; o
modulo novo usa `status` inteiro como fonte unica (RN-13 do design de 08-05).

Endpoint entregue com contrato e filtros corretos, retornando vazio enquanto
`pedidos_ah` estiver sem dado.

## 5. Divergencias que exigem decisao ou verificacao

### 5.1 Identidade exposta: `id` novo ou `codigo_legado`

O contrato legado publica `id_liberacao`, que e a PK do legado. Consumidores de
BI podem ter esse valor persistido. A resposta usa `codigo_legado` para preservar
a identidade historica; o `id` novo nao e exposto neste contrato de paridade.

### 5.2 `Codmundv` equivale a `codigo_ibge` -- RESOLVIDO

No legado, `cedec_municipio.Codmundv` e o codigo IBGE **com digito
verificador** (7 digitos). A implementacao confirmou a equivalencia por duas
vias: `RefinarLegadoAjuCommand::refinarLiberacoes()` ja casa
`municipios.codigo_ibge = cedec_municipio."Codmundv"` para carregar as 3.582
liberacoes, e o teste `test_codmundv_confere_com_codigo_ibge_do_municipio`
verifica a igualdade e o comprimento de 7 digitos na resposta da API. Nao foi
preciso passar por `cedec_municipio` no contrato.

### 5.3 Array `produtos` sem tabela destino

`AjudahProdutoResource` publica `id_produto`, `cod_produto`, `nome`, `origem`,
`origem_n`. A staging tem `aju_produto` (752 linhas) com `id_produto`,
`codProd`, `nome`, `origem` -- **`origem_n` nao existe na origem extraida**. Nao
ha tabela destino no schema novo. Opcoes: criar `ajuda_h_produtos`, ou publicar
`produtos: []` documentado no Swagger. Esta spec adota a segunda, por manter o
escopo no fornecimento de dados; a primeira e trabalho de ETL (secao 6).

### 5.4 `singular` promovido a coluna -- RESOLVIDO

Legado: `aju_unidade.nome = 'CESTA BASICA'` e `aju_unidade.singular = 'CESTA'`.
Os contratos de `pubajudahCedec` (`material`) e `saldocesta` (`singular`) usam o
**singular**.

A inspecao do dump mostrou que `aju_unidade` **traz a coluna `singular`**, o que
tornou a divergencia solucionavel em vez de documentavel: `singular` entrou como
coluna de `materiais_ah` no mesmo lote da secao 7.1, com backfill.

Ressalva: o dado e esparso -- apenas **5 dos 187 materiais** tem `singular`
preenchido no legado (entre eles a cesta basica, com `'CESTA'`). Onde falta, a
API publica `coalesce(singular, nome)`: paridade real onde o legado tinha o
valor, rotulo utilizavel no resto, nunca nulo.

### 5.5 `categoria` nao tem fonte de backfill

Os 187 documentos `aju_unidade` da staging **nao contem a chave `categoria`** --
a extracao nao trouxe essa coluna. Sem ela, o filtro `categoria = 'CESTA BASICA'`
do `saldocesta` nao pode ser reproduzido por backfill.

Tratamento aplicado: a coluna e criada e o backfill do refino marca
`'CESTA BASICA'` no material cujo `nome` e exatamente esse -- na base atual, um
unico material (`id_unidade = 1`), que e o recorte efetivo que o legado
praticava. O endpoint filtra pela coluna, sem regra especial em codigo. Quando a
area confirmar a lista completa de materiais da categoria, so o backfill muda.

### 5.6 Quebra de compatibilidade na autenticacao

`pubajudah`, `pubajudahCedec` e `saldocesta` sao publicos hoje. Os novos
endpoints exigem token Sanctum. Consumidores atuais (Power BI, integracoes
externas) precisam de token pessoal emitido pelo modulo de Permissionamento. O
legado permanece no ar; nao ha desligamento previsto nesta spec.

## 6. Fora de escopo

- Extracao de `aju_item` (itens de liberacao) e o refinamento correspondente
- Inclusao de `aju_h_pedido_pedid` / `aju_h_pedido_itens` no mapa de carga
- Criacao de `ajuda_h_produtos` e carga de `aju_produto`
- Desligamento dos endpoints publicos do legado
- Escrita: os quatro endpoints sao somente leitura

Sem os dois primeiros itens, `pubajudahCedec` e `listPedidoAh` respondem vazio e
`pubajudah` responde sem itens. Isso e consequencia declarada do recorte, nao
defeito da implementacao.

## 7. Arquitetura

### 7.1 Schema

Migration consolidada na migration principal do modulo
(`2026_08_05_100000_create_ajuda_humanitaria_mah_tables.php`), conforme a regra
de consolidacao de migrations:

- `materiais_ah`: `valor` (`decimal(10,2)`, nullable), `peso` (`decimal(10,2)`, nullable), `categoria` (`varchar`, nullable, indexada)
- `ajuda_h_liberacoes`: `evento` (`varchar`, nullable, indexada junto com `data_libera`)

Mais um ajuste de indice: `ajuda_h_lib_itens_liberacao_idx` passa de
`(liberacao_id)` para `(liberacao_id, status)`, pelo motivo da secao 7.4.

O backfill **estende as etapas que `legado:aju:refinar` ja tem**, sem comando
novo: a etapa `materiais` grava `valor` e `peso` de `aju_unidade` (casando por
`materiais_ah.codigo_legado = doc->>'id_unidade'`) e marca `categoria`; a etapa
`liberacoes` grava `evento`. `payload_legado` permanece intacto, para nao perder
o rastro da origem.

### 7.2 Camadas

```
routes/api.php  (prefixo v1/ajuda-humanitaria, middleware sanctum + can)
  -> Api/V1/AjudaHumanitaria/LiberacaoApiController      (indice, cedec)
  -> Api/V1/AjudaHumanitaria/EstoqueApiController        (saldo-cesta)
  -> Api/V1/AjudaHumanitaria/PedidoConsolidadoController (consolidado)
       -> Modules/AjudaHumanitaria/Services/*ApiService  (consulta, agregacao e forma do JSON)
```

Os quatro contratos sao listas planas de paridade, sem relacionamento a carregar
nem campo condicional. A forma do JSON fica no metodo de formatacao do proprio
service; introduzir classes `Resource` para envelopar array associativo seria
cerimonia sem ganho. Os `Resource` existentes do modulo (`PedidoAhResource`)
seguem servindo as telas Inertia.

Cada controller carrega apenas as anotacoes `@OA` do proprio recurso; os
schemas compartilhados vao para `Api/Schemas.php`, como o projeto ja faz. Tag
Swagger nova: `Ajuda Humanitaria`, registrada em `SwaggerController`.

A agregacao fica em service, nao em controller: os quatro contratos tem
agrupamentos distintos (por ano, plano, por deposito, por municipio) e cada um
merece um metodo testavel isoladamente.

### 7.3 Permissoes

Slugs existentes, sem criar novos:

| Endpoint | Permissao |
| --- | --- |
| `liberacoes`, `liberacoes/cedec` | `humanitaria.saldo.view` |
| `estoque/saldo-cesta` | `humanitaria.saldo.view` |
| `pedidos/consolidado` | `humanitaria.pedidos.view` |

### 7.4 Erros e limites

- Validacao de query params via FormRequest, devolvendo 422 no formato do projeto
- `throttle` no grupo, seguindo o padrao de `global-search`
- `statement_timeout:10000` herdado do grupo `v1`
- `pubajudah` e `pubajudahCedec` nao paginam no legado; a paridade mantem isso, e a protecao real e o `statement_timeout` mais o filtro obrigatorio de ano em `pubajudah`. `pubajudahCedec` **nao tem filtro obrigatorio** -- com a carga de itens completa, sera a consulta mais pesada dos quatro e precisa de indice em `(liberacao_id, status)`

### 7.5 Testes

- Feature test por endpoint: contrato (chaves e tipos), autenticacao (401 sem token), autorizacao (403 sem permissao), validacao (422)
- Teste de agregacao por service, com dado semeado, cobrindo o agrupamento e o mapa de `situacao`
- Teste que garante retorno vazio, e nao erro, quando `ajuda_h_liberacao_itens` esta vazia
- Verificacao de `Codmundv` vs `codigo_ibge` como teste sobre a base semeada (5.2)

## 8. Verificacao de entrega

1. `php artisan l5-swagger:generate` sem erro, e os quatro endpoints visiveis na UI
2. Resposta de `estoque/saldo-cesta` com os 118 saldos consolidados por deposito, com `valor` e `peso` preenchidos
3. Resposta de `liberacoes?ano_comeco=2022` com `meta.totais` conferindo com `SELECT count(*) ... GROUP BY status`
4. `liberacoes/cedec` e `pedidos/consolidado` respondendo 200 com lista vazia
5. Suite de testes do modulo verde
