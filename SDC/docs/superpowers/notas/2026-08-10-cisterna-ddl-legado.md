# Cisterna — verificacao do dump de producao e pendencias da area

**Data:** 2026-08-14
**Fonte:** `database/data/Cisternas.sql` — 24 MB, 28.417 linhas, exportado de `200.198.29.227` (MySQL 8.0.31, Linux) via HeidiSQL 12.10
**Spec:** [2026-08-10-cisterna-migracao-legado-design.md](../specs/2026-08-10-cisterna-migracao-legado-design.md) secao 4.6
**Plano:** [2026-08-10-cisterna-modulo-backend-etl.md](../plans/2026-08-10-cisterna-modulo-backend-etl.md) Task 1

Entregavel da Task 1. As Tasks 15 a 18 consomem este documento.

---

## 1. Banco de trabalho

O dump traz `CREATE DATABASE dbsdc` e `USE dbsdc`: importar direto contamina o banco de dev do legado. Carregado num banco isolado:

```bash
MY=/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin

sed -e 's/^CREATE DATABASE IF NOT EXISTS `dbsdc`.*$//' \
    -e 's/^USE `dbsdc`;$//' \
    database/data/Cisternas.sql > /c/tmp/cisternas_isolado.sql

"$MY/mysql.exe" -u root -h 127.0.0.1 -e \
  "DROP DATABASE IF EXISTS cisterna_analise; CREATE DATABASE cisterna_analise CHARACTER SET utf8mb4;"

"$MY/mysql.exe" -u root -h 127.0.0.1 --force cisterna_analise < /c/tmp/cisternas_isolado.sql
```

`--force` e necessario: `sinc_cisterna_old` tem PKs duplicadas **no proprio dump**. Os erros aparecem somente nessa tabela, que nao e portada.

Para rodar o ETL sem VPN: `LEGADO_CISTERNA_DB_DATABASE=cisterna_analise` no `.env`.

## 2. Volumes conferidos

| Tabela | Linhas | Engine | AUTO_INCREMENT |
|---|---|---|---|
| `sinc_cisterna` | 8.105 | MyISAM | 9205 |
| `sinc_cisterna_com` | 885 | InnoDB | 897 |
| `sinc_cisterna_rel_compdec` | 858 | MyISAM | 892 |
| `sinc_cisterna_rel_fornecedor` | 856 | MyISAM | 918 |
| `sinc_cisterna_rel_cedec` | 675 | MyISAM | 687 |
| `sinc_cisterna_ordem_servico` | 7 | MyISAM | 8 |
| `sinc_cisterna_notificacoes` | 7 | MyISAM | 21 |
| `sinc_cisterna_lotes` | 3 | MyISAM | 6 |
| `sinc_cisterna_relatorio_cedec` | 2 | InnoDB | 19 |
| `sinc_cisterna_old` | 2.577 | — | 2579 | 

**Conferir antes de rodar o ETL:** se producao ja passou de `AUTO_INCREMENT=9205` em `sinc_cisterna`, houve carga nova depois da exportacao. Reexportar.

## 3. Ambiente do NewSDC

| Verificacao | Resultado |
|---|---|
| Os 55 `codmundv` do legado casam com `municipios.codigo_ibge` | **55 de 55**, zero orfao |
| `cedec_municipio.at_cisterna` populado | **0 de 854** — zerado. Corrigido pela Task 18 |
| Postgres do NewSDC | Docker `newsdc_dev_db`, exposto em `127.0.0.1:5434` |

## 4. Efeito colateral desta verificacao

O `dbsdc` (banco de dev do legado) recebeu as tabelas de producao na primeira importacao, feita antes de remover o `USE dbsdc`. Foi **completado de proposito** depois: a `sinc_cisterna_com` tinha ficado com 225 de 885 linhas porque a estrutura antiga pre-existia e nao tinha as colunas de timestamp.

Estado atual do `dbsdc`: 8.105 / 885 / 3 / 7 / 7 / 856 / 858 / 675 / 2 — espelhando producao. Util para rodar o legado local lado a lado com o modulo novo.

---

## 5. Pendencias que exigem a area

### 5.1 Os CPFs colidentes — **a recomendacao mudou depois de olhar os nomes**

Producao tem 492 CPFs repetidos em 1.003 linhas. Desses, 485 estao marcados `aprovado=5` (Duplicado): o legado nao impedia a duplicata, marcava. Esses seguem como tombstone, aceitos pelo indice unico parcial.

Restam **26 CPFs colidindo entre registros ativos**. Comparando os nomes (normalizados, `similar_text`), eles se dividem em **duas naturezas diferentes**, e tratar as duas igual seria errado:

**Categoria A — mesma pessoa, cadastro em duplicidade: 22 casos.**
Similaridade >= 80%. Exemplos: `Geraldo Gomes de Queiroz` / `Geraldo Gomes Queiroz`; `IVANDEIDE PEREIRA DE ARAUJO` / `IVANEIDE PEREIRA ARAUJO`; `Marinalva Gomes de Araújo` / `Marinalva Gomes de Araujo` (so o acento).
**Tratamento:** importa o registro mais completo como esta e marca o concorrente como `duplicado`, com a observacao apontando o outro id. E a convencao que o legado ja usava.

**Categoria B — CPF atribuido a pessoas DIFERENTES: 4 casos.**

| CPF | Similaridade | Registro 1 | Registro 2 | Municipio |
|---|---|---|---|---|
| `05924079659` | 30,8% | #4201 DOUGLAS SOARES BARBOSA | #4202 ISABEL ALVES SEPO | ALMENARA |
| `07913521619` | 24,0% | #4236 DATIVIA MACHADO DOS SANTOS | #4251 NAIARA DE SOUZA OLIVEIRA | BERIZAL |
| `07131806684` | 40,9% | #6910 JOSE ALVES GOMES | #6911 MARIA DO ROSARIO JESUS ALVES | VEREDINHA |
| `04720939678` | 76,4% | #324 SONIA MERCIA BORGES DA CRUZ | #325 SONIA MERCIA BORGES RIBEIRO | BOCAIUVA |

Sao **erro de digitacao de CPF**, nao duplicidade de cadastro. Marcar Isabel Alves Sepo como duplicata de Douglas Soares Barbosa **apagaria uma beneficiaria real** da lista ativa — o oposto do que se quer.

**Tratamento:** o segundo registro **nao e importado**. Entra no `cisterna_etl_log` com `acao = error`, os dois nomes no motivo e o payload preservado. Sao 4 casos: a area corrige o CPF errado e o registro entra numa segunda passada do refino.

O caso `04720939678` (76,4%) e ambiguo: pode ser a mesma pessoa com sobrenome de casada diferente (`DA CRUZ` -> `RIBEIRO`). Fica na categoria B de proposito — **na duvida, nao decidir automaticamente.**

> **O limiar de 80% e heuristica, nao verdade.** Dois casos passaram como categoria A e merecem olhada humana: `Maria Cardoso de Jesus` / `MAIARA CARDOSO DE JESUS` (UBAI) e `Maria Eliete Lemos Pereira` / `Maria Eliane Lemos Pereira` (UBAI) — podem ser mae e filha, ou irmas, nao a mesma pessoa. **Pedido a area: revisar os 26, nao apenas os 4.**

### 5.2 `cisterna_id = 8088` — tres relatorios, um com numero fora de faixa

Tres relatorios de fornecedor com `num_instalacao` 35, 35 e **50000**, em 18 e 19/11/2025. O `50000` esta fora de qualquer faixa plausivel (o codigo do legado dizia impor teto de 1.800). Provavelmente teste.

**Pergunta:** descartar o registro com `50000`?

### 5.3 `sinc_cisterna_relatorio_cedec` — 2 linhas, sem codigo

Tabela **orfa**: nao esta em nenhum model, controller ou rota do legado. A rota `cisterna.relatorio_cedec.store` grava em `sinc_cisterna_rel_cedec`, que e outra tabela.

E um checklist de fiscalizacao bem mais rico que o `rel_cedec` em uso: **26 itens de conformidade**, cada um com `_conforme tinyint` **e** `_obs text`, agrupados por bloco (canteiro, reservatorio, calha, tubos, protecao, bomba, gerais), mais `pendencias`, dados e assinatura do representante, e 6 colunas `*_uploads`. InnoDB com `CURRENT_TIMESTAMP` — estrutura recente.

**Decisao tomada (D23): nao portada nesta entrega.** Com 2 linhas e sem codigo, portar seria adivinhar.

**Pergunta:** e o futuro do formulario da CEDEC ou tentativa abandonada? Se e o futuro, o `cisterna_itens_conferidos` do modelo novo ja acomoda (item + conferido + observacao) — basta ampliar o enum `ItemInstalacao` e acrescentar um agrupador de bloco.

### 5.4 Os 55 municipios habilitados

O refino vai marcar `at_cisterna = 1` nos 55 municipios que tem beneficiario no legado, porque o flag chegou zerado no Postgres.

**Pergunta:** sao exatamente esses 55 que devem ficar habilitados, ou existe municipio no programa que ainda nao tem cadastro e precisa aparecer no select?

Lista dos 55 codigos IBGE:

```
3101706 3104452 3104502 3106200 3106655 3107307 3108503 3109402 3112703 3113503
3115474 3117836 3119500 3120300 3120870 3125606 3126604 3127339 3127800 3128253
3129608 3129657 3130655 3132008 3132107 3135100 3135209 3135605 3136520 3136959
3139250 3141801 3142700 3142908 3143450 3144656 3146552 3149150 3150570 3151206
3152131 3152204 3156601 3157377 3157609 3162450 3168002 3170008 3170529 3170651
3170800 3170909 3171030 3171071 3171600
```

### 5.5 Perdas de dado que a migracao nao pode reverter

Nao sao decisoes, sao constatacoes — a area precisa saber:

- **Centavos da renda.** `renda` e `float(10,0)` no legado: zero casas decimais. Nenhuma das 8.105 linhas tem centavos. Perdido **na origem**, nao na migracao.
- **67 cadastros com `PR?PRIA`.** `moradia` e `varchar(7)` em utf8mb3, e "PRÓPRIA" nao cabe. O refino mapeia para `propria`.
- **34 cadastros com o responsavel gravado na coluna do booleano.** `atendPipa` e `varchar(36)` e recebeu `prefeitura`, `respAtPrefeitura`, `respAtExercito`, `defesa civil`, `outros` em vez de sim/nao. O refino le como "atendido = sim" e registra o valor original no log.
- **`0` como valor de enum.** 162 linhas em `moradia` e 14 em `coberturaTelhado` tem literalmente `'0'`. Viram null.

### 5.6 As fotos do imovel estao no Google Drive — **isto precisa de decisao**

Verificado nos dados: as colunas `img_frontal`, `img_lat_direito`, `img_lat_esquerdo`, `img_fundo`, `img_local_ins_p1/p2` e `img_op1..4` **nao guardam caminho de arquivo**. Guardam o rotulo da foto — literalmente `FRENTE`, `FUNDO`, ou `0`. O legado gravava ali `$request->obs_frontal`, a observacao digitada.

O arquivo em si esta no Google Drive, nas colunas `*_lk`:

```
img_frontal_lk = https://drive.google.com/open?id=1ERSWmB1hnY44s-Iw...
```

**5.808 das 8.105 linhas (72%) tem link do Drive.** Apenas 2 registros tem `anexo_deficiencia` e 3 tem `anexo_mulher` em disco local.

Em contraste, as fotos de **vistoria** estao em disco e sao migraveis: 827 no fornecedor, 249 no COMPDEC, mais 736 e 658 assinaturas.

**O que o ETL faz:** preserva o rotulo como observacao e a URL do Drive em `custom_properties.origem_legado`, e conta no log quantas ficaram so como link. **Nao baixa do Drive** — isso exigiria credencial da conta, tratar arquivo restrito versus compartilhado e lidar com rate limit. E decisao de infraestrutura, nao de porte de modulo.

**Perguntas para a area:**

1. As fotos do imovel precisam estar no NewSDC, ou o link para o Drive basta?
2. Se precisam: alguem tem acesso a conta do Drive para uma extracao em massa? Sao ~5.800 arquivos.
3. Os arquivos de vistoria (os que **estao** em disco) precisam ser copiados do servidor do legado para ca. Quem faz e quando?

Enquanto isso nao se resolve, o modulo novo funciona sem as fotos do imovel: o cadastro, o fluxo de vistoria e os relatorios nao dependem delas.

### 5.7 Os 8.105 cadastros importados ficam sem autor — e a trilha do sino fica sem destinatario

`cisterna_beneficiarios.created_by` existe para a trilha de acoes do modulo Notificacoes: `donosNotificacao()` devolve `[created_by]`, e e quem recebe o aviso quando o registro muda. Com `created_by` nulo, a trilha e gravada mas **nao tem para quem tocar**.

Situacao do dado de origem:

| | |
|---|---|
| `sinc_cisterna.user_id` preenchido | 2.299 de 8.105 — **5.806 (72%) ja sao nulos no proprio legado** |
| Usuarios distintos que cadastraram | 43 |
| Faixa de id no legado | 1 a 729 |
| Usuarios no legado `sdc` | 977 |
| Usuarios no NewSDC | **55**, ids 1 a 55 |

**Os ids nao mapeiam, e usa-los seria pior que deixar nulo.** Id acima de 55 quebraria a FK; id de 1 a 55 atribuiria o cadastro a **outra pessoa**, e a trilha avisaria quem nao tem nada com aquilo.

Tentativa de casar por outro criterio, sobre os 43 usuarios:

```
casam por CPF:   0
casam por EMAIL: 0
```

**Zero.** Sao contas institucionais de COMPDEC municipal (`defesacivil@janauba.mg.gov.br`, `comdec@taiobeiras.mg.gov.br`, `GAMELEIRA2733`) que ainda nao existem no NewSDC — os 55 usuarios de la sao outro conjunto.

**Decisao:** o refino grava `created_by = null` em tudo que vem do legado. Nao ha alternativa correta hoje.

**Isso nao perde a informacao.** O `user_id` de origem continua no `cisterna_legado_raw.doc`, e `legacy_id` liga o registro novo a linha crua. Quando as contas COMPDEC forem criadas no NewSDC, um comando de reconciliacao consegue preencher `created_by` sem reimportar nada — nao e preciso coluna nova nem decisao agora.

**O que precisa ser dito a area:** para os 8.105 registros importados, a trilha de acoes nao vai notificar ninguem ate essa reconciliacao acontecer. Registro criado **no NewSDC** funciona normalmente, porque ai o `created_by` e preenchido pelo observer.
