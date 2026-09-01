# Inmet no Medalhao (Fase 3)

**Data:** 2026-09-01
**Fase:** 3 do roteiro registrado em `2026-08-07-medalhao-pipeline-sismos-design.md`, secao 5.2
**Pre-requisito:** Fase 1, entregue e mergeada na `dev` em `5e98c912`

## 1. Objetivo

Migrar o modulo Inmet para o kernel Medalhao, dando a ele serie historica, geometria
PostGIS e agregacao materializada. O Inmet passa a ser o segundo consumidor do
kernel, provando que a Fase 1 aceita fonte nova sem crescer.

Junto, extrair o mapa Leaflet duplicado entre Inmet e Sismos para um componente
unico.

## 2. Situacao encontrada

Levantada por leitura do codigo e por sondagem da API real em 2026-09-01.

### 2.1 O modulo nunca funcionou

`InmetApiClient::fetchFromApi` monta a URL assim:

```php
$url = self::TOKEN_URL . "/{$today}/{$today}/" . self::API_TOKEN;
```

A rota real do INMET exige o **codigo da estacao antes do token**. A URL montada
pelo app nao existe no roteador do INMET:

```
GET /token/estacao/2026-09-01/2026-09-01/<TOKEN>
-> 404 HttpException: E_ROUTE_NOT_FOUND
```

O `catch (\Exception)` engole o erro, loga um warning e devolve `[]`. O
`Cache::remember` de 900s entao guarda o vazio. **A pagina do Inmet exibe zero
estacoes hoje, silenciosamente, e sempre exibiu.**

### 2.2 A API exige User-Agent de navegador

Sem `User-Agent`, o servidor aceita o handshake TLS e corta a conexao na leitura
da resposta (`curl 56: unexpected eof while reading`). O certificado e valido
(`*.inmet.gov.br`, Let's Encrypt, TLSv1.2); o handshake completa. Somente a
resposta e cortada.

Com `User-Agent` de navegador, a mesma chamada responde 200. `Http::get()` do
Laravel nao envia User-Agent por padrao, entao o defeito 2.1 e este se somam.

Medido em 4 tentativas consecutivas, sem intermitencia.

### 2.3 Nao existe endpoint de todas as estacoes

O inventario e uma chamada; as leituras sao **uma chamada por estacao**:

| Chamada | Resultado |
| --- | --- |
| `/estacoes/T` | 200, 150 KB, 674 estacoes automaticas no Brasil |
| `/estacao/{d}/{d}/{codigo}` (sem token) | 204, vazio |
| `/token/estacao/{d}/{d}/{codigo}/{token}` | 200, ~11,6 KB (dia inteiro de uma estacao) |
| `/token/estacao/{d}/{d}/{token}` | 404, rota inexistente |

MG tem **68 estacoes automaticas, 61 operantes**.

### 2.4 Agregacao em PHP no request

`InmetService::getEstatisticas` calcula media, maxima e contagens em PHP a cada
request, sobre a colecao inteira — o que a constraint global da Fase 1 proibe na
camada de entrega.

### 2.5 Outros achados

- `const API_TOKEN` esta hardcoded no fonte de `InmetApiClient`.
- `LeituraMeteorologicaDTO::fromInmetArray` faz `(float) ($data['VL_LATITUDE'] ?? 0)`.
  Estacao sem coordenada vai para lat 0, lon 0 — Golfo da Guine. Em mapa de
  Defesa Civil, ponto no lugar errado tem consequencia operacional.
- `estacoes_meteorologicas` existe, tem 0 linhas, e `findAllEstacoes`,
  `findEstacaoByCodigo`, `findEstacoesByUf`, `createEstacao`, `updateEstacao` e
  `deleteEstacao` nao tem chamador. Codigo morto.
- `MapaInmet.vue` (543 linhas) e `MapaSismos.vue` (274 linhas) duplicam
  `L.map`, o mesmo tile OSM e `L.circleMarker`. O de Inmet nao tem
  `onBeforeUnmount`: a instancia do mapa nao e destruida ao navegar.

## 3. Decisoes de arquitetura

### 3.1 O kernel deixa de conhecer dominio

`NormalizarSilverJob::handle` tem hoje:

```php
if ($grupo === 'sismos') {
    AtualizarGoldSismosJob::dispatch();
}
```

Tres linhas acima do docblock que afirma que o kernel nao conhece dominio. Sai
para config, no padrao que `persistidores` ja usa:

```php
'refresh_gold' => [
    'sismos' => AtualizarGoldSismosJob::class,
    'inmet'  => AtualizarGoldInmetJob::class,
],
```

O job despacha `config("medalhao.refresh_gold.{$grupo}")` quando existir. Com
isso o Inmet entra sem editar o kernel, e a Fase 2 (CEMADEN) tambem nao editara.
E a unica mudanca desta fase em codigo da Fase 1.

### 3.2 Coleta concorrente, um payload de Bronze por ciclo

O contrato `FonteIngestor::coletar(): PayloadBruto` devolve **um** payload. O
ingestor busca as 68 estacoes com `Http::pool` e consolida num JSON unico.

Medido: 12 chamadas concorrentes completaram em **menos de 1 segundo**, 12 de 12
com conteudo, 136 KB somados. As 68 cabem com folga nos 300s de timeout do worker
`medalhao`. Coleta sequencial foi descartada por isso: ~5s por estacao daria ~340s
e estouraria o worker, alem de prender a fila que os sismos compartilham.

**Falha parcial:** estacao que falhar e registrada em `meta.falhas` e a coleta
segue com o que veio. Bronze e historico bruto; ciclo parcial serve e o proximo
recoleta. Aborta apenas se nada vier, caso que o kernel ja trata com "coleta sem
conteudo".

### 3.3 Estacao e dimensao, leitura e fato

`estacoes_meteorologicas` deixa de ser tabela orfa e passa a ser a dimensao:
recebe `geom geography(Point,4326)` e upsert por `codigo`, alimentada pelo
inventario `/estacoes/T`.

`silver.leituras_inmet` guarda so o fato, com chave natural
`(codigo_estacao, medido_em)`. Nao repete nome nem coordenada da estacao — ao
contrario de `silver.sismos`, onde repetir se justifica porque o evento sismico
nao tem entidade estavel por tras. Estacao tem.

Consequencia: corrigir a coordenada de uma estacao corrige o historico inteiro
num update, em vez de exigir backfill.

**Municipio da estacao.** O inventario do INMET nao tem campo de municipio: traz
`DC_NOME`, que e nome de estacao ("BELO HORIZONTE - PAMPULHA"), e `SG_ESTADO`.
Como a coluna `municipio` da dimensao e `NOT NULL`, ela e resolvida pelo
centroide mais proximo entre os 853 municipios de MG ja semeados.

E aproximacao, e a limitacao esta registrada de proposito: `municipios` tem
`latitude`/`longitude`, nao geometria de area, entao nao ha contencao por
poligono. Estacao proxima de divisa pode resolver para o vizinho. Verificado
para A521: resolve Belo Horizonte a 5,3 km, contra Contagem a 10,3 km — margem
confortavel no caso tipico. Gravar o nome da estacao no campo de municipio foi
descartado: em relatorio de Defesa Civil, campo de municipio errado vaza para
decisao.

### 3.4 Recorte geografico por UF, nao por bbox

Os sismos usam bbox porque as fontes devolvem o mundo e o `Local` vem generico.
O inventario do INMET traz `SG_ESTADO` confiavel, entao o recorte e
`SG_ESTADO = 'MG'` — mais preciso e mais barato que bbox. O parametro `uf` do
request sai: o recorte e do pipeline, nao da requisicao.

A bbox de MG continua existindo, mas so com um papel: enquadrar o mapa na
entrega. Ela nao filtra nada nesta fonte. Sao dois usos distintos que o spec da
Fase 1 nao precisava separar porque lá coincidiam.

### 3.5 Custo de Bronze aceito conscientemente

Cada chamada devolve o dia inteiro da estacao. Coletando de hora em hora, o
payload do ciclo cresce ao longo do dia e boa parte se repete entre ciclos. O
dedup por hash nao ajuda: o conteudo muda a cada hora, porque leituras novas sao
apendadas.

Estimativa: ~790 KB por ciclo no fim do dia, ~19 MB/dia de Bronze. A retencao de
30 dias e o rollup Parquet com compressao snappy absorvem isso. O upsert do
Silver por `(codigo_estacao, medido_em)` e idempotente, entao reingerir o mesmo
dia repetidamente e inofensivo.

Se o volume incomodar, a saida e retencao propria por fonte no Bronze — fora do
escopo desta fase.

## 4. Arquitetura

### 4.1 Arquivos

**Modulo `Inmet`:**

| Arquivo | Responsabilidade |
| --- | --- |
| `Ingestores/InmetApiIngestor.php` | Inventario + 68 leituras via `Http::pool`, consolidadas |
| `Normalizadores/InmetJsonNormalizador.php` | JSON consolidado -> DTOs |
| `Repositories/InmetRepository.php` | `upsertLote`: dimensao de estacao + Silver; leitura do Gold |
| `Jobs/AtualizarGoldInmetJob.php` | `REFRESH MATERIALIZED VIEW CONCURRENTLY` das duas matviews |
| `DTOs/LeituraMeteorologicaDTO.php` | Existente; coordenada deixa de cair em 0 |
| `DTOs/EstacaoDTO.php` | Novo; dimensao vinda do inventario |
| `Services/InmetApiClient.php` | Token vai para env; User-Agent obrigatorio; URL corrigida |
| `Services/InmetService.php` | Metodos mortos removidos; agregacao em PHP removida |
| `Controllers/InmetIndexController.php` | Le apenas `gold` |

**Kernel (unica mudanca):** `Jobs/NormalizarSilverJob.php` e `config/medalhao.php`.

**Frontend:** `Components/Mapa/MapaLeaflet.vue` novo; `MapaInmet.vue` e
`MapaSismos.vue` passam a consumi-lo.

### 4.2 Migrations

Padrao `2026_09_01_NNNNNN_descricao.php`, todas com a guarda
`if (DB::getDriverName() !== 'pgsql') { return; }`.

1. `add_geom_to_estacoes_meteorologicas` — coluna `geom`, indice GIST. A dimensao
   **permanece no schema `public`**, nao migra para `silver`: ela e cadastro do
   dominio, referenciada pela aplicacao inteira, e nao um artefato do pipeline.
   O `gold.inmet_mapa` faz join entre schemas, o que o Postgres resolve sem custo.
2. `create_silver_leituras_inmet` — fato, unique `(codigo_estacao, medido_em)`.
3. `create_gold_inmet_views` — `gold.inmet_mapa` (ultima leitura por estacao,
   com `geom` da dimensao) e `gold.inmet_estatisticas` (as cinco metricas que o
   controller calcula em PHP hoje). Indice unico em cada, exigido pelo
   `REFRESH ... CONCURRENTLY`.

### 4.3 Fluxo

```
Schedule (horario)
  -> medalhao:ingerir inmet
    -> IngerirFonteJob            fila medalhao
       InmetApiIngestor::coletar()
         /estacoes/T  +  Http::pool das 68
         -> PayloadBruto (JSON consolidado, meta com falhas)
       -> bronze.ingestao_bruta
    -> NormalizarSilverJob        fila medalhao
       InmetJsonNormalizador -> DTOs
       InmetRepository::upsertLote
         upsert dimensao (codigo)
         upsert silver.leituras_inmet (codigo_estacao, medido_em)
    -> AtualizarGoldInmetJob      via config refresh_gold
       REFRESH CONCURRENTLY das duas matviews
```

Cadencia horaria, nao de 15 minutos como os sismos: a estacao automatica do INMET
publica de hora em hora, entao coletar mais que isso so multiplica I/O.

### 4.4 Entrega

`InmetIndexController::__invoke` le `gold.inmet_mapa` e `gold.inmet_estatisticas`
e devolve as props `estacoes`, `estatisticas` e `bbox`. Nenhuma agregacao em PHP.
Mesmo nivel de protecao de rota que hoje.

`MapaLeaflet.vue` recebe `pontos` (lat, lon, cor, raio, popup), `centro`, `zoom` e
um slot de legenda. Monta o mapa em `onMounted` e o destroi em `onBeforeUnmount` —
o que corrige o vazamento do Inmet de graca.

## 5. Testes

TDD, com fixture real gravada em disco a partir da sondagem desta data.

| Teste | Prova |
| --- | --- |
| `InmetApiIngestorTest` | User-Agent enviado; URL com codigo antes do token; falha parcial registrada em `meta` e coleta prossegue |
| `InmetJsonNormalizadorTest` | JSON consolidado -> DTOs; estacao sem coordenada e descartada, nao vai para lat 0 |
| `InmetRepositoryTest` | Upsert idempotente na dimensao e no fato; `ST_Y`/`ST_X` batem com o payload |
| `GoldInmetTest` | Matviews refletem o Silver apos refresh; estatisticas conferem com o calculo manual |
| `InmetIndexControllerTest` | Pagina renderiza do Gold; nenhuma query toca `silver` |
| `RefreshGoldPorConfigTest` | Kernel despacha o job do grupo por config, e nao despacha para grupo sem entrada |

Testes pgsql-only pulam com `markTestSkipped` fora do Postgres. Arquivos de teste
nao entram nos commits (regra de ouro 10).

## 6. Criterios de verificacao

1. `php artisan medalhao:ingerir inmet` popula `bronze.ingestao_bruta` com uma
   linha, e `meta` registra quantas estacoes responderam.
2. `silver.leituras_inmet` recebe leituras das estacoes de MG; segunda execucao do
   mesmo dia nao duplica linha.
3. A dimensao `estacoes_meteorologicas` sai de 0 para as 61 operantes, com `geom`
   valido conferido por `ST_Y`/`ST_X` contra o inventario.
4. As matviews de `gold` refletem o Silver apos o ciclo.
5. A pagina do Inmet mostra estacoes — pela primeira vez — e consulta apenas
   `gold`.
6. `InmetService` nao tem mais metodo sem chamador nem calculo de agregacao.
7. O token do INMET nao aparece em nenhum arquivo versionado.
8. `MapaInmet.vue` e `MapaSismos.vue` nao instanciam Leaflet diretamente; ambos
   consomem `MapaLeaflet.vue`, e navegar para fora nao deixa mapa vivo.
9. `NormalizarSilverJob` nao menciona nome de grupo nenhum.
10. Suite verde no escopo desta fase, com os pgsql-only pulados fora do Postgres.

## 7. Fora de escopo

- Agregacoes de chuva mensal/diario/horario com janelas e QC (Fase 4).
- Superficie interpolada IDW/krigagem via worker Python (Fase 5).
- Ingestao CEMADEN SALVAR (Fase 2).
- Pagina unica com camadas ligaveis de sismos e chuva: o componente extraido nesta
  fase e o pre-requisito, mas a unificacao de navegacao fica para depois.
- Retencao de Bronze por fonte, discutida em 3.5.
- Estacoes convencionais (`/estacoes/M`): so as automaticas (`T`) entram.

## 8. Riscos

**O token e compartilhado e hardcoded ha tempo indeterminado.** Ele sai para env
nesta fase, mas nao ha como saber se segue valido amanha nem quem mais o usa. Se
expirar, a coleta para e o Bronze fica sem linha nova — visivel no log, nao
silencioso como hoje.

**O User-Agent e uma dependencia nao documentada.** Nada garante que o INMET
mantenha esse comportamento. O teste do ingestor fixa a expectativa de que o
header e enviado, entao uma mudanca do lado deles aparece como coleta vazia com
log, e nao como pagina vazia sem explicacao.

**Coordenada do inventario nao tem verificacao independente.** Diferente do
CEMADEN (secao 3.5 do spec da Fase 1), aqui a fonte e o proprio INMET, que opera
as estacoes — proveniencia aceitavel. Estacao sem coordenada e descartada em vez
de plotada em zero.
