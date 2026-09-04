# Camadas geoespaciais por upload de KML/KMZ

## 1. O problema

A Defesa Civil recebe alertas de risco em KML/KMZ de fontes externas -- o caso
que motivou este documento e `ALERTA-RISCO-GEOLOGICO-MODERADO-28022026.kml`, com
seis poligonos cobrindo mesorregioes de MG. Hoje esses arquivos vivem fora do
sistema: abrem no Google Earth, e ninguem consegue responder no SDC a pergunta
que importa em plantao -- **esta chovendo dentro da area de alerta, e onde?**

O sistema ja tem os dois lados da resposta separados. Tem 890 estacoes de chuva
com leitura recente (60 INMET + 830 CEMADEN) e tem PostGIS 3.6.3 com indice GIST
em toda geometria. Falta o recorte: a area de alerta.

## 2. Situacao encontrada

Levantado em 2026-09-03, contra o banco de dev e o arquivo real.

**O PostGIS desta base le KML nativamente.** `ST_GeomFromKML` esta disponivel e
funciona para `Polygon`, `LineString` e `Point`. Isso decide a arquitetura: o
parse de geometria acontece no banco, e o PHP nunca faz conta geografica.

**Um campo de geometria basta para os tres dominios.** Verificado inserindo
poligono, linha e ponto num unico `geometry(Geometry,4326)`, com um unico indice
GIST servindo o cruzamento espacial dos tres (3 de 3 bateram no envelope de
teste). Isso importa porque hidrologia e o caso que quebraria um modelo
so-de-poligono: rio e linha, regua fluviometrica e ponto, bacia e poligono.

**O arquivo real e pequeno e simples.** 41 KB, 6 Placemarks, 772 vertices no
total, sem `innerBoundaryIs` (sem buracos) e sem `ExtendedData` (sem atributos).
Os nomes dos Placemarks sao todos a string `"0"`; o unico nome util e o do
`Document` (`ALERTA MODERADO2802`). Nao ha metadado de emissao nem de validade
dentro do arquivo -- apenas no nome do arquivo (`28022026`).

**Dois tratamentos de geometria sao obrigatorios, nao defensivos.** Ambos
comprovados:

- `ST_GeomFromKML` devolve `POLYGON Z` para as coordenadas do arquivo, que
  trazem altitude (`-43.937,-20.390,0`). A insercao direta num campo 2D **falha**
  com `SQLSTATE[22023] Geometry has Z dimension but column does not`. Sem
  `ST_Force2D` o import quebra no primeiro poligono do arquivo que motivou o
  recurso.
- Poligono auto-interseccionado e invalido (`ST_IsValid` = false) e
  `ST_MakeValid` o corrige. Nenhum dos 6 poligonos deste arquivo e invalido, mas
  KML de origem externa nao oferece garantia.

**O cruzamento ja produz resposta operacional.** Com os 6 poligonos carregados
num schema descartavel:

| Metrica | Valor |
|---|---|
| Feicoes inseridas | 6 de 6, todas validas |
| Area por poligono | 1.962 a 88.237 km2 |
| Municipios atingidos | 282 |
| Estacoes CEMADEN na area | 378 |
| Chuva 24h na area | media 0,49 mm, maxima 15,65 mm (190 estacoes com leitura) |

## 3. Decisoes de arquitetura

### 3.1 Reusar a camada Bronze por push, e nao criar um segundo padrao

O upload grava o arquivo cru em `bronze.ingestao_bruta` com
`fonte = 'geo-upload'` e despacha o `NormalizarSilverJob` que ja existe. Dali em
diante o pipeline e o mesmo dos sismos e da chuva.

Uma `fonte` so para os tres dominios, e nao `geo-upload-geologico` e irmaos: o
dedup e por (fonte, hash), e o mesmo arquivo nao deve entrar duas vezes so
porque foi rotulado com outro dominio. O dominio e atributo da camada, nao da
fonte.

O roteamento usa as duas entradas de config que o kernel ja consulta, sem linha
nova no `NormalizarSilverJob`:

```php
'persistidores' => ['geoespacial' => GeoCamadaRepository::class],
'refresh_gold'  => ['geoespacial' => AtualizarGoldGeoJob::class],
```

O grupo e `geoespacial` -- o mesmo nome que um ingestor devolveria em `grupo()`
se houvesse ingestor.

Isso ganha tres coisas sem codigo novo: **dedup por hash** (re-subir o mesmo
arquivo e detectado, em vez de duplicar areas no mapa), **arquivamento em
Parquet** do arquivo original, e **`GoldAtualizado`** atualizando a tela quando o
processamento termina.

A alternativa -- tabelas proprias e arquivo num disco Flysystem -- nao exigiria
mexer no kernel, mas criaria um segundo jeito de "dado externo entrar no
sistema", que e exatamente o que o medalhao existe para evitar.

**Custo:** o `IngestorRegistry::registrar()` exige um `FonteIngestor`, e upload
nao tem o que coletar -- `coletar()` e contrato de *pull* agendado. O kernel
passa a aceitar registro de normalizador sem ingestor, ou seja, uma fonte
so-push. E adicao pequena e honesta; forcar um `coletar()` que nunca e chamado
seria pior.

### 3.2 O Octane nunca parseia

O request HTTP faz tres coisas: valida, grava o cru no Bronze, despacha job.
Nenhum parse, nenhuma geometria, nenhum ZIP aberto no worker do Octane. O
trabalho roda na fila `medalhao`, que ja e processo separado
(`docker/supervisor/medalhao-worker.conf`).

A entrega para o mapa segue a mesma disciplina das telas existentes: o GeoJSON e
montado por `ST_AsGeoJSON` e materializado na camada Gold, entao o request de
leitura nao serializa poligono -- so le linha pronta.

### 3.3 Um dominio em coluna, nao um modulo por dominio

`dominio` e coluna (`hidro`, `geologico`, `meteorologico`), nao tabela nem
modulo. O que varia entre dominios e legenda, cor e vocabulario, e isso vive em
`config/geoespacial.php`, no mesmo padrao de `medalhao.persistidores`: dominio
novo entra por config, sem migration.

Generalizar o modelo e de graca (provado na secao 2). Implementar tres dominios
nao e -- cada um tem fonte, severidade e legenda proprias. **A primeira fatia
entrega apenas o geologico.**

## 4. Arquitetura

### 4.1 Fluxo

```
Upload HTTP  ->  valida + grava cru no Bronze  ->  despacha job   [Octane, sem parse]
                                                        |
                                    fila medalhao (processo separado)
                                                        |
                        ZipArchive (se KMZ)  ->  recorta fragmentos de geometria
                                                        |
                    ST_MakeValid(ST_Force2D(ST_GeomFromKML(...)))   [no Postgres]
                                                        |
                        silver.geo_camadas + silver.geo_feicoes
                                                        |
                    REFRESH CONCURRENTLY das matviews do Gold
                                                        |
                              GoldAtualizado -> Reverb -> tela
```

### 4.2 Camada Silver

```sql
silver.geo_camadas
  id            bigserial PK
  dominio       varchar(20)  NOT NULL   -- hidro | geologico | meteorologico
  nome          varchar(255) NOT NULL
  arquivo_nome  varchar(255) NOT NULL
  emitido_em    date         NULL
  valido_ate    date         NULL
  nivel         varchar(40)  NULL       -- moderado | alto | ...
  hash_arquivo  char(64)     NOT NULL UNIQUE
  ingestao_id   bigint       NULL REFERENCES bronze.ingestao_bruta(id)
  created_at / updated_at

silver.geo_feicoes
  id            bigserial PK
  camada_id     bigint NOT NULL REFERENCES silver.geo_camadas(id) ON DELETE CASCADE
  nome          varchar(255) NULL
  propriedades  jsonb NOT NULL DEFAULT '{}'
  geom          geometry(Geometry,4326) NOT NULL   -- GIST
```

`hash_arquivo` unico e o que impede a mesma camada entrar duas vezes. `CASCADE`
porque feicao sem camada nao tem significado.

`propriedades jsonb` existe porque `ExtendedData` varia por fonte. O arquivo
atual nao tem nenhum, mas avisos meteorologicos carregam atributos, e sem o
jsonb cada fonte nova pediria migration.

`geometry(Geometry,4326)` e nao `geometry(MultiPolygon,4326)`: e o que absorve
linha e ponto quando hidro entrar.

### 4.3 Camada Gold

- `gold.geo_feicao_mapa` -- uma linha por feicao com `ST_AsGeoJSON(geom)` pronto,
  mais dominio, nome e nivel da camada, para o mapa ler sem join nem
  serializacao.
- `gold.geo_camada_municipios` -- cruzamento com `municipios`, uma linha por par
  camada/municipio.

Ambas com indice UNICO (obrigatorio para `REFRESH ... CONCURRENTLY`) e GIST na
geometria.

### 4.4 Parse e seguranca

Upload e superficie de ataque. Tres guardas, todas obrigatorias:

1. **Zip bomb.** KMZ e ZIP. Le-se o primeiro `.kml` do arquivo, com limite de
   tamanho **descomprimido** verificado antes de extrair.
2. **XXE.** O XML vem de fora. O parse nao passa `LIBXML_NOENT` nem
   `LIBXML_DTDLOAD`, e passa `LIBXML_NONET`. Entidade externa lendo arquivo do
   servidor nao e risco aceitavel aqui.
3. **Tipo e tamanho.** Validacao de extensao e de tamanho maximo no Request,
   antes de qualquer leitura de conteudo.

O PHP recorta os fragmentos `<MultiGeometry>`, `<Polygon>`, `<LineString>` e
`<Point>` e entrega string ao Postgres. Nao interpreta coordenada, nao calcula
area, nao valida topologia -- isso e trabalho do PostGIS.

### 4.5 Tela

- Pagina `Geoespacial`: upload, lista de camadas com dominio/emissao/nivel, e o
  cruzamento da camada selecionada (municipios atingidos, estacoes na area,
  chuva na area).
- Seletor de camadas no mapa da Meteorologia, desenhando o poligono sobre os
  pontos de chuva -- e ali que a pergunta operacional vive.

`MapaLeaflet.vue` hoje so desenha marcadores de ponto. Passa a aceitar uma prop
de poligonos (GeoJSON), mantendo a mesma disciplina atual: o componente recebe
geometria pronta e cuida do escape do popup.

## 5. Testes

O arquivo real e o caso de teste principal, com numeros ja medidos:

| Caso | Esperado |
|---|---|
| Import do KML de 28/02 | 6 feicoes, todas `ST_IsValid` |
| Cruzamento com municipios | 282 municipios |
| Cruzamento com estacoes | 378 estacoes CEMADEN na area |
| Chuva agregada na area | media e maxima nao nulas, com contagem de estacoes com leitura |

Casos que devem falhar ou ser tratados:

| Caso | Esperado |
|---|---|
| KML com altitude, sem `ST_Force2D` | falha `22023` -- e a razao do wrapper existir |
| Poligono auto-interseccionado | `ST_MakeValid` corrige, import segue |
| Re-upload do mesmo arquivo | recusado pelo hash, sem duplicar feicao |
| KMZ | extrai o `.kml` interno e importa igual |
| Arquivo que nao e KML | recusado na validacao, sem gravar Bronze |
| KMZ com razao de compressao absurda | recusado antes de extrair |

## 6. Criterios de verificacao

1. Subir o arquivo de 28/02 pela tela produz 6 feicoes e a camada aparece no
   mapa da Meteorologia.
2. A tela mostra municipios atingidos, estacoes na area e chuva na area, com os
   numeros conferindo com consulta direta ao banco.
3. Re-subir o mesmo arquivo nao cria camada nova.
4. Nenhum parse de XML ou ZIP ocorre em processo do Octane -- verificavel pelo
   log da fila.
5. O `GoldAtualizado` e transmitido ao fim do import, e a tela reflete a camada
   nova sem F5.
6. As regras `.dark` conferidas no **CSS compilado**, com seletor completo.

## 7. Riscos

**Cruzamento com municipios e por centroide.** A tabela `municipios` guarda
`latitude`/`longitude`, nao geometria de area. O cruzamento usa
`ST_Contains(area, centroide)`, entao municipio cujo centroide cai fora mas cujo
territorio e atingido nao entra na conta. **Os 282 sao piso, nao total.**
Corrigir pede malha municipal com poligonos, que e entrega separada. A tela deve
dizer que o numero e por centroide, e nao apresenta-lo como exato.

**Nenhum metadado dentro do arquivo.** Emissao, validade e nivel nao existem no
KML -- so no nome do arquivo. Ou o operador informa na tela, ou se extrai do nome
por convencao fragil. **Decisao: o operador informa**; extrair de nome de arquivo
externo e contrato que ninguem garante.

**Parquet esta latentemente quebrado.** `flow-php/parquet` esta declarado no
`composer.json` e ausente do `vendor/`, e nao instala neste container
(`composer install` sai 0 e remove os diretorios). O arquivamento do Bronze nao
funciona hoje para nenhuma fonte, e passa a valer para esta tambem. Nao bloqueia
este recurso -- o rollup so age sobre linha com mais de 30 dias -- mas fica
registrado.

**Area de 88.237 km2 num poligono.** O maior poligono do arquivo cobre cerca de
15% de MG. Cruzamentos sobre areas desse porte varrem muitas linhas; o indice
GIST resolve, mas vale medir antes de assumir.

## 8. Fora de escopo

- Hidro e meteorologico: o modelo os acomoda, a implementacao nao os entrega.
- Alerta automatico a partir da camada (chuva na area passou de X, notifica).
- Malha municipal com poligonos, que corrigiria o cruzamento por centroide.
- Simplificacao de geometria, tiling e servir por bbox/zoom -- desnecessarios no
  porte acordado (poucos MB, dezenas de poligonos).
- Shapefile e GeoJSON como formatos de entrada.
- Edicao de geometria na tela.
