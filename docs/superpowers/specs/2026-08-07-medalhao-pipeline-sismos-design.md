# Medalhao - Kernel de Pipeline de Dados + Fonte Sismica (Fase 1)

Data: 2026-08-07
Modulos alvo: `SDC/app/Modules/Medalhao` (novo), `SDC/app/Modules/Sismos` (novo)
Status: design aprovado, pronto para plano de implementacao
Origem: `gestaocedec/SDC/docs/medalion.md` + inventario de `NewSDC/Python - Data`

## 1. Objetivo

Construir o esqueleto de uma arquitetura em medalhao (Bronze / Silver / Gold)
nativa no Laravel, sem sobrecarregar os workers existentes, e prova-lo ponta a
ponta contra a fonte sismica (USP e UnB).

O kernel precisa ser reusavel: INMET e CEMADEN SALVAR entram em fases seguintes
sem reescrita. Esta fase entrega infraestrutura + um consumidor real.

## 2. Situacao encontrada

### 2.1 O que existe em `Python - Data`

Sete arquivos, tres papeis distintos:

| Arquivo | Papel | Destino |
| --- | --- | --- |
| `raspagem sismos.py` / `.ipynb` | Selenium sobre USP moho + UnB obsis | Substituido por HTTP (secao 3.1) |
| `webscraping salvar.ipynb` cel. 2 `raspar()` | Selenium sobre CEMADEN SALVAR autenticado | Fase 2, depende de spike |
| `webscraping salvar.ipynb` cel. 3 `calculos()` | Pipeline do CINDEC: agregacao + interpolacao | Dividido (secao 4.3) |
| `TESTE PYTHON GOOGLE SHEETS.ipynb` | Exploratorio (Google Sheets, proxy) | Fora de escopo |
| `formatar_dados.py` | Utilitario tkinter de planilha | Fora de escopo |
| `tela_interativa.py` | Dashboard tkinter com atalhos e Google Maps | Fora de escopo |
| `PREVISAO_XLS_COPIA.xlsx` | Planilha de apoio | Fora de escopo |

O `calculos()` tem autoria declarada no proprio codigo: Romero Wanzeler e
Caroline de Sa (meteorologistas do CINDEC), adaptado por Bruno H. Rodrigues
(analista de dados do CINDEC), em 26/05/2026. Qualquer reimplementacao precisa
preservar o comportamento numerico, nao apenas o formato de saida.

O `raspagem sismos.py` nunca rodou como script: `cols[1].text.strip().astype(float)`
chama `.astype()` sobre `str`, o que levanta `AttributeError` imediatamente.
E codigo de notebook exportado, nao rotina em producao.

### 2.2 O modulo Inmet do NewSDC nao persiste nada

`SDC/app/Modules/Inmet` busca a API do INMET a cada request
(`InmetIndexController::__invoke` -> `InmetService::getLeiturasAtuais`) e calcula
media e maxima em PHP na hora (`InmetService::getEstatisticas`). O unico
amortecedor e um `Cache::remember` de 900s em `InmetApiClient`.

`EstacaoMeteorologica` guarda `latitude` e `longitude` como `decimal:7`. Nao ha
coluna geografica, apesar de a extensao `postgis` ja ser habilitada em
`database/migrations/2026_05_05_000001_enable_postgres_extensions.php`.

Nao existe Bronze, Silver nem Gold no projeto hoje.

### 2.3 Infraestrutura relevante

- Postgres com `postgis` habilitada (mesma migration acima), com guarda por
  driver e tolerancia a extensao indisponivel.
- Fila padrao Redis. Um unico worker, com lista ordenada
  `critical,high,high-throughput,webhooks,default,low`
  (`docker/supervisor/laravel-worker.conf`, `--timeout=60`; o entrypoint Swoole
  usa `--timeout=90`). Sem Horizon.
- Armazenamento on-premise por bind mount: `ANEXOS_ROOT=/data/anexos`, com
  subpasta por modulo, permissoes `0664`/`02775` e guarda `.sdc_storage_mounted`
  no entrypoint. O helper `$azureOrLocal` em `config/filesystems.php` escolhe o
  driver por ambiente; sem `AZURE_STORAGE_CONNECTION_STRING` cai no bind mount.
- Frontend com Leaflet 1.9 (`resources/js/Pages/Inmet/MapaInmet.vue`).

Nota: o projeto migrou de Azure para on-premise. O caminho Azure permanece no
`filesystems.php` mas nao e o alvo desta fase.

## 3. Achados que mudam a premissa do `medalion.md`

### 3.1 A raspagem sismica nao precisa existir

O `evlist.js` da propria pagina da USP declara a origem dos dados:

```js
events1 = new Events("event1", CsConfiguration.eventFDSN + '/fdsnws/event/1/query');
// CsConfiguration.eventFDSN === 'https://moho.iag.usp.br'
```

E um FDSN Event Web Service padrao (versao 1.2.4). Verificado em 2026-08-07:

```
GET https://moho.iag.usp.br/fdsnws/event/1/query?limit=5&orderby=time&format=text
HTTP 200

#EventID|Time|Latitude|Longitude|Depth/km|Author|Catalog|Contributor|ContributorID|MagType|Magnitude|MagAuthor|EventLocationName|EventType
usp2026pgvc|2026-08-06T04:31:07.723527|-25.3257|-43.7302|0.0|JAlexandre||USP|usp2026pgvc|mR|2.2259|JAlexandre|Plataforma Continental/SP|earthquake
```

O servico aceita `starttime`, `endtime`, `minlatitude`, `maxlatitude`,
`minlongitude`, `maxlongitude`, `mindepth`, `maxdepth`, `minmag`, `maxmag`,
`magtype`, `limit`, `offset`, `orderby`, `format`. O quadrante MG anotado no
notebook (`-22.9 <= lat <= -14.23`, `-51.04 <= lon <= -39.85`) vira parametro da
query em vez de filtro pos-download.

O UnB (`http://obsis.unb.br/portalsis/?pg=seism`) responde HTTP 200 com 28 KB e o
`<textarea>` de CSV ja presente no corpo. Nao ha renderizacao por JS.

Decisao: as duas fontes sismicas sao consumidas por HTTP. Sem Selenium, sem
WebDriver versionado, sem navegador em container.

### 3.2 O CEMADEN SALVAR e substancialmente mais dificil

`https://salvar.cemaden.gov.br/salvar/restrito/meteorologia/index.jsf`:

- autenticado por `j_username` / `j_password` (form auth de container Java EE,
  `j_security_check`);
- JSF, com tabela `infopcds` de carga incremental por scroll (o
  `ActionChains.move_to_element` sobre o ultimo link e o gatilho; o laco repete
  ate parar de surgir codigo novo);
- pagina de detalhe por estacao (`?id=&idh=&dh=&pe=1&es=1`), com DataTables que
  exibe "Carregando" ate o AJAX concluir, e retry de 3 tentativas.

Nao foi possivel testar: o portal e restrito e exige credencial, possivelmente
so alcancavel de dentro da rede corporativa. Se ele cede a um cliente HTTP com
cookie jar e ViewState, ou se exige navegador de fato, e uma questao aberta.

Decisao: fica fora da fase 1 e recebe um spike time-boxed em paralelo. O
resultado do spike dimensiona a fase 2.

### 3.3 Ha um caso Python legitimo, e ele ja e real

O `calculos()` faz interpolacao espacial por IDW e por Krigagem Ordinaria
(`pykrige.ok.OrdinaryKriging`), com mascara por shapefile (MG, Brasil,
microrregioes), `scipy.ndimage`, `rasterio`, `geopandas` e overlay raster em
folium.

Krigagem ordinaria nao tem equivalente em PHP, e reimplementa-la seria
irresponsavel: e metodo geoestatistico com ajuste de variograma, nao formula
fechada. Este e o ocupante real da porta de ingestao Python — nao um caso
hipotetico.

O inverso tambem vale, e importa mais para esta fase: a maior parte do
`calculos()` **nao** deve ir para Python. Ver secao 4.3.

### 3.4 O Parquet ja e o formato de saida atual

`raspar()` grava `estacoes_salvar.parquet`, `estacoes_salvar_<ts>.parquet`,
`medicoes_salvar.parquet` e `medicoes_salvar_<ts>.parquet`. O `calculos()` grava
`dados_padronizados_<mes>.parquet`, `acumulado_mensal_<mes>.parquet`,
`acumulado_diario_<mes>.parquet`, `serie_horaria_<mes>.parquet` e
`relatorio_municipal_<mes>.parquet`.

Ou seja, "a raspagem gera Parquet" descreve o estado atual, nao uma aspiracao.
Qualquer Parquet que o NewSDC produzir precisa ser legivel por esse ferramental.

### 3.5 As coordenadas das estacoes nao tem proveniencia confiavel

O caminho oficial (inventario SNIRH Hidroweb, `Inventario.zip` -> `.mdb` lido via
`pyodbc`, com join `Estacao`/`Municipio`/`Estado` trazendo `CodigoIBGE`,
latitude, longitude e altitude) esta comentado no notebook. No lugar dele, um
merge com `CODIGOS_SALVAR_MG_v2.xlsx`, mantido a mao.

Em mapa de Defesa Civil, ponto no lugar errado tem consequencia operacional.
Registrado como risco; endereca-se quando o CEMADEN entrar (fase 2), nao aqui —
a fonte sismica traz as proprias coordenadas no payload.

## 4. Decisoes de arquitetura

### 4.1 Dois modulos

```
app/Modules/Medalhao/     kernel do pipeline, agnostico de dominio
app/Modules/Sismos/       dominio sismico, primeiro consumidor do kernel
```

O `Medalhao` nao sabe o que e um sismo. Ele sabe coletar, guardar bruto,
normalizar em lotes, atualizar agregado e arquivar. O `Sismos` fornece as pecas
especificas por injecao.

Com fonte unica seria exagero. Com tres fontes previstas, separar e o que evita
reescrever o kernel na fase 2.

### 4.2 Bronze guarda texto, nao JSONB

O `medalion.md` propoe Bronze em coluna JSONB. Divergimos.

O FDSN devolve texto delimitado por `|` e o obsis devolve CSV. Nenhum dos dois e
JSON. Aplicar `json_encode` antes de gravar ja e transformacao, e a regra da
camada Bronze e nao transformar. Guardar o texto exato preserva a capacidade de
reprocessar contra o byte original, que e a razao de a camada existir.

`meta` continua JSONB, para url, parametros, status HTTP e tempo de resposta —
esses sao metadados da coleta, nao o dado coletado.

### 4.3 Postgres agrega, Python so interpola

Fronteira explicita, para nao se rediscutir a cada fase:

| Trabalho | Onde | Por que |
| --- | --- | --- |
| Acumulado mensal, diario, serie horaria | Postgres | `GROUP BY` e `date_trunc` |
| Janelas moveis de maximo 30min/1h/2h | Postgres | window function sobre intervalo; nao move dado nem carrega em RAM |
| QC fisico (descartar > 300 mm/dia) | Postgres | predicado simples, com registro do descartado |
| Relatorio municipal | Postgres | agregacao por municipio |
| Interpolacao IDW / Krigagem -> raster | Python | `pykrige` sem equivalente em PHP |

As janelas moveis do `_max_rolling_com_periodo` sao o caso mais claro: hoje sao
`groupby().rolling().idxmax()` em pandas, que exige carregar a serie inteira. Em
Postgres e uma window function com `RANGE BETWEEN INTERVAL`, executada onde o
dado ja esta.

Nada disso e implementado nesta fase — sismos nao tem acumulado de chuva. A
fronteira e registrada agora porque define o desenho do kernel.

### 4.4 Worker dedicado

Este e o ponto central do requisito "sem sobrecarga".

O worker atual consome uma lista ordenada e drena `critical` primeiro. Acrescentar
uma fila de ETL nessa lista nao isola nada: um job de coleta que leve minutos
segura o processo e atrasa notificacao e webhook. O `--timeout=60` atual e curto
demais para ETL, e aumenta-lo afrouxaria a garantia das filas criticas.

Decisao: programa supervisor separado, processo proprio:

```
queue:work --queue=medalhao --timeout=300 --tries=3 --sleep=5 --max-time=3600
```

Sem Horizon. Nao esta no stack e o volume nao justifica; se autoscaling for
necessario depois, entra sem mudar codigo de aplicacao.

### 4.5 Sem `Bus::batch` nesta fase

O `medalion.md` recomenda job batching, e ele sera necessario para o CEMADEN
(centenas de estacoes x paginas). A fonte sismica traz dezenas de eventos por
coleta. Batch nesse volume e cerimonia sem ganho.

Decisao: upsert em chunks agora; `Bus::batch` entra junto com a fonte que o
justifica. O contrato `NormalizadorSilver` devolve `iterable`, entao trocar o
consumo por batch depois nao muda as implementacoes de fonte.

## 5. Escopo

### 5.1 Fase 1 (este spec)

Kernel do medalhao + fonte sismica USP e UnB, ponta a ponta, com pagina de mapa.

### 5.2 Fases seguintes (specs proprios)

| Fase | Conteudo | Pre-requisito |
| --- | --- | --- |
| Spike SALVAR | Determinar se o portal cede a HTTP com cookie + ViewState | Credencial + acesso de rede |
| 2 | Ingestao CEMADEN SALVAR + proveniencia de coordenadas | Spike |
| 3 | Migracao do modulo Inmet para o medalhao | Fase 1 |
| 4 | Agregacoes Gold de chuva (mensal/diario/horario, janelas, QC) | Fases 2 e 3 |
| 5 | Superficie interpolada (IDW/krigagem) via worker Python | Fase 4 |

## 6. Arquitetura da fase 1

### 6.1 Schemas e tabelas

Migrations com a mesma guarda de driver usada em
`2026_05_05_000001_enable_postgres_extensions.php`:

```php
if (DB::getDriverName() !== 'pgsql') {
    return;
}
```

`CREATE SCHEMA IF NOT EXISTS bronze` / `silver` / `gold`.

**`bronze.ingestao_bruta`** — generica, compartilhada por todas as fontes:

| Coluna | Tipo | Nota |
| --- | --- | --- |
| `id` | bigserial | |
| `fonte` | varchar(64) | `usp-fdsn`, `unb-obsis` |
| `conteudo_bruto` | text | exatamente como recebido |
| `formato` | varchar(32) | `fdsn-text`, `csv` |
| `hash_conteudo` | char(64) | sha256; indexado com `fonte` |
| `meta` | jsonb | url, params, status, duracao_ms |
| `coletado_em` | timestamptz | |
| `processado_em` | timestamptz null | preenchido pelo Silver |

Indice `(fonte, hash_conteudo)` e `(fonte, coletado_em)`. Resposta identica a
anterior nao gera linha nova nem reprocessamento.

**`silver.sismos`** — tipada e geografica:

| Coluna | Tipo | Nota |
| --- | --- | --- |
| `id` | bigserial | |
| `fonte` | varchar(64) | |
| `evento_id` | varchar(64) | id no catalogo de origem |
| `origem_utc` | timestamptz | |
| `geom` | `geometry(Point,4326)` | `ST_SetSRID(ST_MakePoint(lon, lat), 4326)` |
| `profundidade_km` | numeric(8,3) null | |
| `magnitude` | numeric(5,3) null | |
| `escala_magnitude` | varchar(16) null | `mb`, `mR`, `mL` |
| `modo` | varchar(16) null | manual / automatico |
| `regiao` | text null | |
| `tipo_evento` | varchar(32) null | `earthquake` etc. |
| `autor` | varchar(64) null | |
| `ingestao_id` | bigint FK -> bronze | rastreabilidade ate o bruto |

`UNIQUE (fonte, evento_id)` para o upsert. Indice GiST em `geom`. Indice em
`origem_utc DESC`.

**`gold.sismos_mapa`** — materialized view com `lat`/`lon` ja extraidos
(`ST_Y`/`ST_X`), classe de magnitude derivada e janela de
`config('medalhao.sismos.janela_mapa_dias')`, default 90 dias.

**`gold.sismos_estatisticas`** — materialized view com contagem, magnitude media
e maxima, e `ultima_atualizacao`.

Ambas com indice UNICO. Isso e obrigatorio: `REFRESH MATERIALIZED VIEW
CONCURRENTLY` exige um, e e o `CONCURRENTLY` que evita travar a leitura do mapa
durante a atualizacao.

### 6.2 Contratos

Duas interfaces, nao uma. A separacao segue SRP e e o que permite um ingestor
Python ocupar apenas a primeira metade, sem nunca tocar Silver ou Gold:

```php
namespace App\Modules\Medalhao\Contracts;

interface FonteIngestor
{
    public function chave(): string;          // 'usp-fdsn'
    public function formato(): string;        // 'fdsn-text'
    public function coletar(): PayloadBruto;  // Bronze
}

interface NormalizadorSilver
{
    /** @return iterable<object> DTOs de dominio */
    public function normalizar(PayloadBruto $bruto): iterable;
}
```

`PayloadBruto` e um DTO readonly: `conteudo`, `formato`, `meta`.

`IngestorRegistry` mapeia chave -> par (ingestor, normalizador), populado no
`MedalhaoServiceProvider`. O modulo `Sismos` registra os seus no
`SismosServiceProvider`.

### 6.3 Fluxo

```
Schedule (a cada 15 min)
  └─ artisan medalhao:ingerir sismos
       └─ IngerirFonteJob (um por fonte)            [fila: medalhao]
            ├─ FonteIngestor::coletar()
            ├─ hash igual ao ultimo? -> encerra
            ├─ grava bronze.ingestao_bruta
            └─ NormalizarSilverJob(bronzeId)        [fila: medalhao]
                 ├─ NormalizadorSilver::normalizar()
                 ├─ upsert silver.sismos em chunks de 500
                 ├─ marca processado_em
                 └─ AtualizarGoldJob                [fila: medalhao]
                      └─ REFRESH MAT VIEW CONCURRENTLY (as duas)

Schedule (diario, 04:00)
  └─ artisan medalhao:rollup
       └─ RolloverParquetJob                        [fila: medalhao]
            ├─ le bronze com coletado_em < hoje - retencao_dias (default 30)
            ├─ escreve Parquet particionado
            ├─ verifica o arquivo relendo
            └─ so entao remove as linhas do Postgres
```

`AtualizarGoldJob` usa `WithoutOverlapping` — dois refresh concorrentes da mesma
matview nao trazem beneficio e competem por I/O.

Implementacoes da fase 1:

- `UspFdsnIngestor` — `Http::` contra `moho.iag.usp.br/fdsnws/event/1/query`,
  com bounding box de MG e janela temporal configuraveis.
- `UnbObsisIngestor` — `Http::` contra `obsis.unb.br/portalsis/?pg=seism`,
  extraindo o conteudo do `<textarea>`.

  Duas armadilhas verificadas em 2026-08-07, ambas mascaradas pelo Selenium:
  as quebras de linha chegam como entidade `&#10;` (o `get_attribute("value")`
  do WebDriver decodificava sozinho; com HTTP puro e preciso
  `html_entity_decode` antes de dividir as linhas); e a coluna `Local` traz
  `Brazil` generico, nao o estado — filtrar MG por texto nao funciona, tem de
  ser por bounding box sobre as coordenadas, ja que o portal nao aceita filtro
  no servidor e devolve o catalogo global.
- `FdsnTextNormalizador` e `ObsisCsvNormalizador`.

Ambos os normalizadores iteram linha a linha, sem carregar o payload inteiro em
estruturas intermediarias. Nesse volume `json-machine` (citado no `medalion.md`)
nao se aplica: as respostas nao sao JSON e nao sao grandes.

### 6.4 Parquet

`RolloverParquetJob` escreve em:

```
/data/anexos/MEDALHAO/bronze/fonte=<fonte>/dt=<YYYY-MM-DD>/parte-<n>.parquet
```

Disco novo `medalhao` em `config/filesystems.php`, usando o helper
`$azureOrLocal` existente. Herda mount, permissoes `02775`/setgid e a guarda
`.sdc_storage_mounted`.

A escrita fica atras de uma interface propria:

```php
interface ArquivadorBronze
{
    public function arquivar(string $fonte, CarbonInterface $dia, iterable $linhas): string;
}
```

Isso importa porque nenhuma lib PHP de Parquet e madura: `flow-php/parquet` esta
em 0.x; `codename/parquet` e port do parquet-dotnet. Recomendacao:
`flow-php/parquet`, por ter schema tipado e ser feita para ETL. Com a lib isolada
atras da interface, trocar e alterar um arquivo.

O plano inclui um teste de ida e volta que le o Parquet gerado de volta com
pandas, para provar compatibilidade real com o ferramental do CINDEC — nao basta
o arquivo ser valido, ele precisa ser legivel por quem vai consumi-lo.

A poda so ocorre apos a verificacao. Falha na escrita ou na releitura aborta o
job e mantem o Bronze intacto.

### 6.5 Entrega

`SismosController` le exclusivamente o schema `gold`, via repositorio. Nenhum
calculo em PHP — a diferenca em relacao ao `InmetService::getEstatisticas` atual
e deliberada.

Filtro por bounding box quando o mapa muda de enquadramento:

```sql
SELECT ... FROM gold.sismos_mapa
WHERE ST_Intersects(geom, ST_MakeEnvelope(?, ?, ?, ?, 4326));
```

`resources/js/Pages/Sismos/MapaSismos.vue`, Leaflet, no padrao de
`MapaInmet.vue`, com raio do marcador proporcional a magnitude (como o
`folium.CircleMarker` do notebook). Rota em `routes/modules/sismos.php`.

## 7. Testes

Restricao real: estas migrations nao rodam em sqlite (schemas nomeados, PostGIS,
materialized views). Os testes de Silver e Gold sao pgsql-only e pulam fora dele,
coerente com o setup pgsql:5434 ja usado no projeto.

| Alvo | Tipo | Como |
| --- | --- | --- |
| `UspFdsnIngestor` | unitario | `Http::fake()` com fixture capturada da resposta real |
| `UnbObsisIngestor` | unitario | `Http::fake()` com fixture do HTML do textarea |
| Normalizadores | unitario | fixture -> DTOs, incluindo linha malformada e campo ausente |
| Dedup por hash | feature | duas coletas identicas geram uma linha de bronze |
| Upsert Silver | feature (pgsql) | reingestao do mesmo evento atualiza, nao duplica |
| Refresh Gold | feature (pgsql) | matview reflete o Silver apos o job |
| Bounding box | feature (pgsql) | evento fora do quadrante nao retorna |
| Rollup Parquet | feature | escreve, rele, so entao poda; falha preserva o Bronze |
| Parquet x pandas | integracao | script le o arquivo gerado com pandas |

As fixtures vem de respostas reais ja capturadas, o que mantem a suite offline e
deterministica.

## 8. Riscos e questoes em aberto

**Deduplicacao USP x UnB.** Os dois catalogos publicam o mesmo tremor com
identificadores proprios e coordenadas diferentes. A fase 1 grava as duas origens
sem cruzar.

O ID sugere um atalho que **nao funciona**. Ambos usam SeisComP3 (a coluna do UnB
chama-se `IDSCP3`) e o sufixo do identificador deriva do tempo de origem.
Verificado em 2026-08-07:

| Evento | UnB | USP | Sufixo |
| --- | --- | --- | --- |
| Felixlandia/MG, 31/07 08:14:42 | `unb2026owdm` | `usp2026owdm` | igual |
| Peru-Brazil, 31/07 00:58 | `unb2026ovpc` (00:58:44) | `usp2026ovpb` (00:58:43.238) | difere |
| 30/07 10:17:19 | `unb2026ouma` | ausente do catalogo | — |

Diferenca de 0,8 s no tempo de origem ja altera o codigo. Casar por sufixo
passaria na maioria dos casos e falharia em silencio no resto — pior que nao
deduplicar. A resolucao correta e por proximidade espaco-temporal (no evento
acima, ~9 km e 0,8 s de distancia entre as duas solucoes), o que exige definir
tolerancia e qual catalogo prevalece: decisao de dominio, a ser tomada com quem
entende de sismologia.

A terceira linha mostra que as fontes nao sao redundantes — o UnB publica evento
que a USP nao tem. Manter as duas tem valor mesmo sem dedup.

O `UNIQUE (fonte, evento_id)` permite adicionar a resolucao depois sem migrar
dado.

**Disponibilidade do FDSN.** Servico academico, sem SLA. O pipeline degrada
corretamente: falha de coleta nao altera o Gold, e o mapa segue exibindo a ultima
versao consolidada. E exatamente a propriedade de desacoplamento que motiva o
medalhao.

**Estabilidade do obsis.** A extracao depende da posicao do `<textarea>` na
pagina. Menos estavel que o FDSN. O normalizador falha de forma explicita e
registrada em vez de gravar dado parcial.

**Maturidade das libs de Parquet.** Mitigado pela interface `ArquivadorBronze` e
pelo teste de leitura com pandas.

**Retencao do Bronze.** O numero de dias antes do rollup fica em config, com
default de 30. Nao ha dado historico suficiente para calibrar melhor agora.

## 9. Criterios de verificacao

1. `php artisan migrate` cria os tres schemas e as duas matviews em Postgres, e
   nao falha em ambiente sem PostGIS (retorna cedo).
2. `php artisan medalhao:ingerir sismos` popula `bronze.ingestao_bruta`.
3. Segunda execucao imediata nao cria linha nova de bronze (hash igual).
4. `silver.sismos` recebe os eventos com `geom` valido; `ST_Y(geom)` e
   `ST_X(geom)` batem com o payload de origem.
5. As matviews de `gold` refletem o Silver apos o ciclo completo.
6. A pagina do mapa renderiza os eventos e consulta apenas `gold`.
7. `queue:work --queue=medalhao` roda em processo separado; nenhum job do
   pipeline aparece no worker das filas criticas.
8. `php artisan medalhao:rollup` gera Parquet no bind mount, e o arquivo abre em
   pandas com os tipos esperados.
9. Suite verde, com os testes pgsql-only pulados fora do Postgres.
