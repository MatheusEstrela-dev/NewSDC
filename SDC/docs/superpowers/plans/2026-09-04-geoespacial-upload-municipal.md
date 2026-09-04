# Upload municipal de camadas geoespaciais

Extensao do dominio `Geoespacial` que ja existe, e nao modulo novo: a
geometria, o pipeline do medalhao e o cruzamento espacial sao os mesmos. O que
muda e quem envia, para onde o arquivo vai e quem autoriza a publicacao.

## O que o levantamento mostrou

- **O vinculo municipio-usuario ja existe e esta populado.** `compdec_orgaos`
  tem 893 COMPDECs, 858 com `municipio_id`, e `compdec_orgao_user` tem 893
  vinculos com `funcao` e `is_principal`. Nao e preciso inventar tenancy.
- **A tabela `tenants` esta VAZIA.** Multi-tenancy nao esta em uso; construir
  por ali seria apoiar em coisa que ninguem usa.
- **O padrao de bind mount dedicado ja existe no compose**, em quatro lugares:
  `- ${ANEXOS_HOST_PATH:-../storage/anexos}:/data/anexos`.
- **355 dos 893 COMPDECs declaram `tem_mapeamento_risco`** e o sistema nao tem
  esse mapeamento em lugar nenhum. Este modulo transforma declaracao em dado.

## Decisoes tomadas

| Questao | Decisao |
|---|---|
| Moderacao | O envio entra como pendente; so vai ao mapa depois que a CEDEC aprova |
| Visibilidade | O municipio ve as proprias (todos os status) e as aprovadas de qualquer origem; a CEDEC ve tudo |
| Classificacao | Mesmos dominios (hidro/geologico/meteorologico) + coluna de origem |

## Modelo de dados

Amplia `silver.geo_camadas`, seguindo a regra 9 (colunas nascem na migration de
origem `2026_09_03_000003_create_silver_geoespacial.php`):

```
origem         varchar(12)  NOT NULL DEFAULT 'estadual'   -- estadual | municipal
municipio_id   bigint       NULL REFERENCES municipios(id)
orgao_id       bigint       NULL     -- compdec_orgaos, de onde veio
enviado_por    bigint       NULL REFERENCES users(id)
status         varchar(12)  NOT NULL DEFAULT 'aprovada'   -- pendente | aprovada | recusada
revisado_por   bigint       NULL REFERENCES users(id)
revisado_em    timestamptz  NULL
motivo_recusa  text         NULL
arquivo_caminho varchar(500) NULL    -- caminho no disco geo_municipal
```

`status` default `aprovada` para nao invalidar as camadas estaduais que ja
existem: envio da CEDEC continua publicando direto, envio municipal nasce
`pendente` por regra do controller.

Indice em `(origem, status)` e em `(municipio_id, status)` — sao os dois
recortes que as telas fazem.

**O municipio NAO escolhe o municipio.** `municipio_id` e `orgao_id` sao
derivados do usuario autenticado, via `compdec_orgao_user` / `orgao_principal_id`
-> `compdec_orgaos.municipio_id`. Deixar o campo no formulario permitiria o
municipio A enviar como B.

## Camada Gold

- `gold.geo_feicao_mapa` passa a filtrar `WHERE c.status = 'aprovada'`, e ganha
  `origem`, `municipio_id` e `municipio_nome`. Geometria pendente nao aparece no
  mapa operacional -- e o ponto da moderacao.
- Fila de revisao NAO e matview: e consulta direta, porque muda a cada decisao e
  precisa ler o estado do instante, nao um snapshot.

## Arquivo e bind mount

Novo mount no compose (app e fila), no molde do `ANEXOS_HOST_PATH`:

```
- ${GEO_MUNICIPAL_HOST_PATH:-../storage/geo-municipal}:/data/geo-municipal
```

Disco Flysystem `geo_municipal` em `config/filesystems.php`, apontando para
`/data/geo-municipal`. Layout espelhando o particionamento do Bronze:

```
municipio=<codigo_ibge>/<ano>/<hash12>.<kml|kmz>
```

**Duplicacao consciente:** o arquivo original fica no bind mount E o KML
extraido fica no envelope do Bronze. Sao coisas diferentes: o bind mount guarda
o documento COMO O MUNICIPIO ENVIOU (inclusive o KMZ compactado), que e o
artefato auditavel; o Bronze guarda a entrada do pipeline. Custa ~42 KB por
envio, e sem o original nao ha como provar depois o que o municipio mandou.

## Permissoes

Em `config/permissions.php`, no padrao dos slugs existentes:

- `geoespacial.camadas.view` — ver o mapa e as camadas
- `geoespacial.camadas.enviar` — enviar (municipio)
- `geoespacial.camadas.revisar` — aprovar e recusar (CEDEC)

Enviar exige, alem da permissao, vinculo com um COMPDEC que tenha
`municipio_id`. Usuario sem vinculo nao tem municipio a atribuir e o envio e
recusado com essa razao explicita.

## Fluxo

```
Municipio envia
  -> valida (tipo, tamanho, dominio, nivel, emissao)
  -> deriva municipio_id do usuario
  -> grava o ORIGINAL no bind mount
  -> grava o envelope no Bronze  -> despacha NormalizarSilverJob
  -> camada nasce status=pendente, NAO entra no gold
  -> notifica a CEDEC (modulo Notificacoes)

CEDEC revisa
  -> aprova   -> status=aprovada -> AtualizarGoldGeoJob -> GoldAtualizado
  -> recusa   -> status=recusada + motivo
  -> notifica o municipio da decisao
```

## Telas

**Municipio** (`/geoespacial`, mesma tela, comportamento por permissao):
formulario de envio + lista das proprias camadas com status e motivo de recusa
quando houver. Sem seletor de municipio.

**CEDEC** (`/geoespacial/revisao`): fila de pendentes com previa no mapa,
municipio de origem, area em km2, municipios atingidos e o aviso de plausibilidade
territorial descrito abaixo. Botoes de aprovar e recusar, recusa exigindo motivo.

## Risco que precisa estar na tela

**Nao ha como validar que a geometria esta dentro do municipio.** Confirmado: nao
existe poligono municipal no banco -- `municipios` tem apenas
`latitude`/`longitude` (centroide). As unicas checagens possiveis sao:

1. A geometria intersecta a bbox de MG (bloqueia envio grosseiramente errado).
2. Distancia entre o centroide da geometria e o centroide do municipio do
   remetente. Acima de um limite, **avisa o revisor** -- nao bloqueia, porque
   municipio grande com area de risco na borda daria falso positivo.

Isso e aviso para humano decidir, nao validacao. Corrigir de verdade exige a
malha municipal com poligonos (IBGE), que e entrega separada e tambem
destravaria o cruzamento por area em vez de por centroide, hoje registrado como
piso e nao total.

## Fora de escopo

- Malha municipal com poligonos (corrigiria a validacao e o cruzamento).
- Download do arquivo original pela tela.
- Edicao de geometria.
- Versionamento de camada municipal (reenvio corrigido vira camada nova).
- Shapefile e GeoJSON como formato de entrada.
- Raster e altimetria por MDE, ja registrados como caminho do modulo Hidro.

## Ordem de implementacao

1. Migration: colunas novas em `silver.geo_camadas` + indices; `gold.geo_feicao_mapa`
   filtrando `status` e carregando origem/municipio.
2. Bind mount no compose + disco `geo_municipal` + permissoes em config.
3. Resolucao do municipio a partir do usuario (servico proprio, testavel isolado).
4. Envio municipal: request, gravacao do original, status pendente, notificacao.
5. Fila de revisao: consulta, aprovar, recusar com motivo, notificacao da decisao.
6. Telas: envio e lista do municipio; fila da CEDEC com previa e aviso de
   plausibilidade.
7. Verificacao ponta a ponta com o KML real, como usuario municipal e como CEDEC.

Tasks 1 e 2 sao independentes e podem ir em paralelo; o resto e sequencial.
