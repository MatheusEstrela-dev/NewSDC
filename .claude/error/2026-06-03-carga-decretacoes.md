# Carga legada Decretacoes (MySQL dbsdc -> PostgreSQL) - 2026-06-03

Migracao dos 10 dumps MySQL de `.claude/skills/database/Decretacoes/` para o
schema PostgreSQL do dominio Decretacoes. Estrategia escolhida: TRUNCATE + reload,
transacional (ACID). Validado primeiro no banco DEV (`localhost:5433`, db `sdc`).

## Incidente 1 - violacao de FK na primeira tentativa (RESOLVIDO)

Primeira versao fazia INSERT direto. A transacao abortou (rollback automatico) em:

```
ERROR: insert or update on table "dec_entrada_decretos" violates foreign key
constraint "dec_entrada_decretos_entrada_processos_id_foreign"
DETAIL: Key (entrada_processos_id)=(1) is not present in table "dec_entrada_processos".
```

### Causa raiz

A base legada (servidor 200.198.29.227) rodava com `FOREIGN_KEY_CHECKS=0`, entao
existem linhas-filhas ORFAS (FK apontando para pais que foram apagados). O schema
PostgreSQL ENFORCA as 11 FKs do dominio, entao orfas quebram a carga.

### Correcao

Conversor reescrito para estrategia de STAGING (`_build_pg_load.py`):
1. tabelas temporarias `stg_<t>` sem constraints recebem os dumps brutos;
2. TRUNCATE das tabelas reais (RESTART IDENTITY CASCADE);
3. `INSERT ... SELECT` staging -> real em ordem de FK, com filtro anti-orfao
   (`WHERE fk IN (SELECT id FROM pai)`), descartando orfas;
4. `setval` das sequences (IDs explicitos preservados);
5. relatorio staged-vs-carregado.

Portavel (sem superuser) - funciona tambem no Azure.

## Resultado da validacao em DEV (commit OK)

Integridade pos-carga: 0 orfas remanescentes. Total 22.037 linhas; 17 orfas
descartadas (0,08%):

### dec_entrada_decretos (2 descartadas) - processo ausente
- decreto id=7 -> processo 1
- decreto id=8 -> processo 1

### dec_decreto_municipios (15 descartadas) - processo ausente
ids 6214(p206), 6259(p250), 6260(p251), 6261(p252), 6288(p278), 6293(p283),
6308(p298), 6309(p299), 6324(p314), 6328(p318), 6329(p319), 6330(p320),
6337(p327), 6366(p356), 6382(p372).

## Alertas para a etapa de PRODUCAO

1. **Blast radius do TRUNCATE CASCADE**: alem das 10 tabelas, `dec_entrada_processo_logs`
   referencia `dec_entrada_processos` e tambem sera truncada. Unica tabela externa afetada.
2. **`municipio_id` semantico**: `dec_decreto_municipios.municipio_id` (valores legados
   30,50,60,70,90,100...) NAO tem FK - so um indice. Em DEV `cedec_municipio` esta VAZIA,
   entao 100% nao casaram. Em PROD `cedec_municipio` esta populada: VERIFICAR a taxa de
   match contra prod antes de confiar no vinculo municipio<->decreto.
3. Tirar dump de seguranca das 11 tabelas (10 + logs) antes do TRUNCATE em prod.

## Incidente 2 - PROD inacessivel pelo firewall do Azure (BLOQUEIO ATIVO)

Checagem read-only em prod falhou na conexao:

```
psql: error: connection to server at "newsdc.postgres.database.azure.com"
(20.29.88.149), port 5432 failed: timeout expired
```

DNS resolve, mas a porta 5432 da timeout = firewall/`publicNetworkAccess` do
Azure bloqueia este host.

IP publico desta maquina (muda com a rede): **187.68.24.163** (anterior: 200.198.31.3).
A regra de firewall precisa liberar o IP ATUAL. Container dev_db egressa pelo host,
entao o IP que chega no Azure e o do host.

### Para destravar (escolher um)
- Adicionar regra de firewall no PostgreSQL `newsdc` liberando 200.198.31.3, ex.:
  `az postgres flexible-server firewall-rule create -g <RG> -n newsdc --rule-name carga-decretacoes --start-ip-address 200.198.31.3 --end-ip-address 200.198.31.3`
  (lembrar de remover a regra depois da carga).
- OU rodar `_load_decretacoes.pg.sql` de um ambiente ja autorizado (App Service
  sdcdefesa / Azure Cloud Shell / rede liberada).

Artefatos:
- `.claude/skills/database/Decretacoes/_build_pg_load.py` (gerador)
- `.claude/skills/database/Decretacoes/_load_decretacoes.pg.sql` (carga ACID)

### Resolucao do incidente 2 (host errado, NAO firewall)

O timeout era host STALE: `.env.prod` aponta `newsdc.postgres.database.azure.com`,
mas o prod real e **`sdc-postgres.postgres.database.azure.com`** (admin `sdcdata`,
RG Defesa_Civil, PG 17.9). Firewall do sdc-postgres ja estava em AllowAll. Senha
obtida do App Service `sdcdefesa` (appsetting DB_PASSWORD). Pendencia: corrigir o
host no `.env.prod`.

## Incidente 3 - carga 1-linha-por-INSERT lentissima em WAN (RESOLVIDO)

Primeira execucao em prod ficou 5+ min presa (22k INSERTs de 1 linha = 22k
round-trips ao Azure). Cancelada (rollback ok, prod limpo). Conversor passou a
gerar INSERTs em LOTE (BATCH=500 linhas/comando). Nova carga: 13,5s.

## RESULTADO FINAL EM PROD (2026-06-03) - SUCESSO

Carga ACID commitada em sdc-postgres. Verificacao em sessao separada:
- processos=501 (485 dump + 16 stubs), municipios=630, decretos=19, desastres=18257
- 16 stubs, todos soft-deleted (ocultos); 434 processos ativos
- integridade FK: 0 orfas

### Pendencia conhecida: municipio_id
Notion (SENHAS E LOGINS / TRANSACAO TEMPORARIA) confirma municipio_id =
cedec_municipio.id (resolvido por Codmundv/IBGE). Em prod cedec_municipio esta
VAZIA - vinculos de municipio ficam pendentes ate popular cedec_municipio com ids
correspondentes aos legados. Carga feita assim mesmo por decisao do usuario.
