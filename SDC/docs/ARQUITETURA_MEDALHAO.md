# Arquitetura do Medalhao — pipeline de dados do SDC

**Ultima revisao:** 2026-09-02
**Especificacoes de origem:** `docs/superpowers/specs/2026-08-07-medalhao-pipeline-sismos-design.md` (fase 1) e `SDC/docs/superpowers/specs/2026-09-01-inmet-medalhao-design.md` (fase 3)

## 1. O que e, em uma frase

Um kernel generico de ingestao em tres camadas — Bronze, Silver, Gold — que qualquer
fonte externa de dado pode consumir sem que o kernel saiba nada sobre o dominio dela.

Hoje tem dois consumidores: **Sismos** (USP e UnB) e **Inmet** (estacoes
meteorologicas). Adicionar um terceiro nao exige alterar uma linha do kernel.

## 2. As tres camadas

| Camada | Onde vive | O que guarda | Por que existe |
| --- | --- | --- | --- |
| **Bronze** | `bronze.ingestao_bruta` | O payload exatamente como veio da fonte, em texto | Proveniencia. Se a normalizacao estiver errada, o bruto permite refazer sem recoletar |
| **Silver** | `silver.sismos`, `silver.leituras_inmet` | Dado tipado, com geometria PostGIS | E a verdade consultavel: tipos corretos, chave natural, indice espacial |
| **Gold** | `gold.sismos_mapa`, `gold.inmet_mapa` e as duas de estatisticas | Matviews com o dado ja agregado e classificado | A entrega le so daqui. Nenhuma agregacao acontece em PHP no request |

Os tres schemas sao schemas Postgres de verdade, criados por migration com guarda
de driver (`if (DB::getDriverName() !== 'pgsql') return;`).

## 3. As pecas do kernel

Modulo `App\Modules\Medalhao`, que nao conhece dominio nenhum:

| Peca | Papel |
| --- | --- |
| `Contracts/FonteIngestor` | `chave()`, `grupo()`, `formato()`, `coletar(): PayloadBruto` |
| `Contracts/NormalizadorSilver` | `normalizar(PayloadBruto): iterable` — Generator, para o consumo do worker nao crescer com o payload |
| `Contracts/ArquivadorBronze` | Escrita Parquet, isolada atras de interface porque nenhuma lib PHP de Parquet e madura |
| `Registry/IngestorRegistry` | Mapeia chave -> (ingestor, normalizador). Cada dominio registra a si mesmo |
| `Jobs/IngerirFonteJob` | Coleta e grava no Bronze, com dedup por hash |
| `Jobs/NormalizarSilverJob` | Bronze -> DTOs -> persistidor do grupo |
| `Jobs/RolloverParquetJob` | Arquiva o Bronze vencido em Parquet e poda o Postgres |

## 4. Como uma fonte nova entra

Sem tocar o kernel. Sao quatro pontos, todos do lado do dominio:

1. Implementar `FonteIngestor` e `NormalizadorSilver`.
2. Registrar o par no `IngestorRegistry`, no `boot()` do provider do modulo.
3. Apontar o persistidor do grupo em `config('medalhao.persistidores.<grupo>')`.
   O contrato e `upsertLote(iterable $dtos, ?int $ingestaoId): int`.
4. Apontar o job de refresh do Gold em `config('medalhao.refresh_gold.<grupo>')`.

O ponto 4 nasceu na fase 3. Antes, o `NormalizarSilverJob` tinha um
`if ($grupo === 'sismos')` hardcoded — tres linhas abaixo do docblock que afirma
que o kernel nao conhece dominio. Cada fase nova acrescentaria mais um `if`.

## 5. O fluxo, ponta a ponta

```
Scheduler (a cada minuto: schedule:run)
  -> medalhao:ingerir <grupo>          despacha 1 job por fonte do grupo
    -> IngerirFonteJob                 fila "medalhao"
       FonteIngestor::coletar()
       hash igual ao anterior? descarta e para aqui
       -> bronze.ingestao_bruta
    -> NormalizarSilverJob             fila "medalhao"
       NormalizadorSilver -> DTOs
       persistidor do grupo -> upsert na Silver
    -> job de refresh do grupo         via config refresh_gold
       REFRESH MATERIALIZED VIEW CONCURRENTLY
  -> pagina Inertia le APENAS gold.*
```

### Dedup por hash

`PayloadBruto::hash()` e SHA-256 do conteudo. Se o payload for identico ao ultimo
daquela fonte, o `IngerirFonteJob` registra "conteudo identico ao anterior,
ignorado" e nem despacha a normalizacao. E o que torna a coleta idempotente.

Vale mais no Inmet que nos sismos: a API do INMET e consultada de hora em hora e
devolve o dia inteiro, com muita repeticao.

## 6. Isolamento da fila

**Nenhum job do pipeline entra nas filas de requisicao.** Tudo vai para a fila
`medalhao`, consumida por processo dedicado.

O motivo e latencia: uma coleta HTTP longa segurando um worker atrasaria
notificacao de alerta e webhook. A fila `medalhao` roda com `--timeout=300`,
contra os 60s e 120s das filas de requisicao.

As listas compartilhadas sao `critical,high`, `webhooks,default,low,high-throughput`
e `notificacoes_urgente,notificacoes`. **Acrescentar `medalhao` a qualquer uma
delas reintroduz exatamente o problema que a separacao resolve.**

## 7. Os dois consumidores hoje

### Sismos (fase 1)

Duas fontes: FDSN da USP (`moho.iag.usp.br`) e portal obsis do UnB. Recorte por
**bounding box** de MG, porque as fontes devolvem o mundo inteiro e o campo de
local vem generico demais para filtrar por texto.

`silver.sismos` repete nome e coordenada em cada linha, e isso se justifica: um
evento sismico nao tem entidade estavel por tras.

### Inmet (fase 3)

Uma fonte, mas **uma chamada HTTP por estacao** — nao existe endpoint de todas.
MG tem 68 estacoes automaticas, 61 operantes. O ingestor busca todas com
`Http::pool` e consolida num unico payload de Bronze.

Recorte por **UF**, nao por bbox: o inventario traz `SG_ESTADO` confiavel, o que
e mais preciso e mais barato que filtro geometrico.

Modelagem diferente da dos sismos, de proposito: **estacao e dimensao, leitura e
fato**. `estacoes_meteorologicas` guarda a estacao com geometria; `silver.leituras_inmet`
guarda so a medicao, com chave `(codigo_estacao, medido_em)`. Assim, corrigir a
coordenada de uma estacao corrige o historico inteiro num update.

## 8. Arquivamento Parquet

`medalhao:rollup`, diario as 04:00, move o Bronze mais velho que
`medalhao.retencao_dias` (30) para Parquet e poda o Postgres.

Caminho: `bronze/fonte=<fonte>/dt=<YYYY-MM-DD>/parte-0.parquet` — particionamento
Hive, legivel direto por pandas e Power BI.

**A poda so acontece depois da escrita verificada.** Se qualquer coisa falhar, a
excecao sobe e o Bronze permanece intacto.

Detalhe que custou um bug: a coluna `fonte` **nao** entra dentro do arquivo,
porque ela ja e a chave da particao no caminho. Com as duas, o pyarrow acha o
mesmo nome em dois lugares e aborta a leitura do dataset com
`ArrowTypeError: Unable to merge: Field fonte has incompatible types`.

## 9. Entrega

Os controllers leem **apenas** `gold.*`. A classificacao (faixa de magnitude, faixa
de precipitacao) e calculada na matview, no banco, nao em PHP.

As duas paginas usam o mesmo componente `Components/Mapa/MapaLeaflet.vue`. O popup
chega estruturado (`{titulo, linhas}`), nao como HTML pronto: quem monta a string
e escapa e o componente, porque nome de estacao e regiao de catalogo vem de fonte
externa.

## 10. Operacao — o que precisa estar de pe

O pipeline depende de **dois processos**, e a ausencia de qualquer um o deixa
silenciosamente parado:

| Processo | Onde | O que acontece sem ele |
| --- | --- | --- |
| `schedule:run` a cada minuto | servico `scheduler` (dev) / `laravel-scheduler` (supervisor, prod) | Nada e despachado. Nenhum agendamento do projeto roda |
| `queue:work --queue=medalhao` | servico `queue` (dev) / `medalhao-worker.conf` (prod) | Os jobs entram na fila e ninguem os consome |

Em 2026-09-01 o mapa do INMET exibia dado do dia anterior por exatamente isso: o
`compose.dev.yml` ja tinha o loop do worker, mas o container `newsdc_dev_queue`
fora criado antes daquela mudanca e nunca recriado; e o servico `scheduler` nao
existia em dev. Corrigido com `docker compose up -d queue scheduler`.

### Armadilha: o vendor vive na IMAGEM

Os containers montam `app/`, `config/`, `routes/`, mas **nao** `vendor/`. Duas
consequencias recorrentes:

- **Dependencia nova nao chega.** `flow-php/parquet` entrou no `composer.json` na
  fase 1, mas os containers seguiram sem ele ate um `composer install` manual —
  que e **efemero**, some ao recriar o container. O durado e rebuildar a imagem.
- **Classe de modulo novo nao e encontrada.** A imagem baka o classmap com
  `--classmap-authoritative`, o que desliga o fallback PSR-4. Corrigir com
  `composer dump-autoload --optimize` no container. `octane:reload` **nao** refaz
  classmap.

## 11. O que esta fora, por decisao registrada

- Deduplicacao entre USP e UnB: casar por sufixo de ID falha em silencio.
- Ingestao CEMADEN SALVAR (fase 2) e as agregacoes de chuva com QC (fase 4).
- Superficie interpolada IDW/krigagem via worker Python (fase 5).
- Retencao de Bronze por fonte: hoje e global.
