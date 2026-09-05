# Modulo Cedec — contrato de interfaces (fases 1 a 5)

Data: 2026-09-04
Spec: `docs/superpowers/specs/2026-09-04-cedec-cadastro-prefeitura-design.md`

Este arquivo e a fonte unica dos nomes, tipos e assinaturas que as cinco fases
compartilham. Um plano de fase NAO pode inventar nome nem tipo que contrarie o que
esta aqui. Se uma fase precisar de algo ausente, o plano dela deve declarar a adicao
explicitamente na secao "Interfaces / Produces".

---

## 0. Convencoes do repo (valem para toda fase)

- App executavel em `NewSDC/SDC`. Backend modular em `app/Modules/<Modulo>`, rotas em
  `routes/modules/<modulo>.php`, frontend em `resources/js`.
- Stack: Laravel 12 / PHP 8.3, Vue 3, Inertia, Vite, Tailwind, Ziggy, Octane/FrankenPHP,
  Redis, PostgreSQL.
- Testes: **PHPUnit**, nao Pest. Classe com `declare(strict_types=1)`, namespace
  `Tests\Feature\Cedec`, trait `DatabaseTransactions`, `Inertia\Testing\AssertableInertia`
  para props, `Spatie\Permission\Models\Permission` para conceder slug.
- **Container (corrigido em 2026-09-05, medido com `docker ps`).** O nome
  `newsdc_frankenphp_local`, que aparece no `.claude/kernel.py`, NAO EXISTE. O container que
  roda e `newsdc_dev_app`, imagem `newsdc-swoole-dev` — Swoole, nao FrankenPHP — e a raiz da
  aplicacao dentro dele e `/var/www`, nao `/app`. Os demais containers do stack sao
  `newsdc_dev_queue`, `newsdc_dev_scheduler`, `newsdc_dev_reverb`, `newsdc_dev_redis`,
  `newsdc_dev_db` (Postgres, publicado no host em **5434**) e `newsdc_dev_db_bkp` (5435).
  - `docker exec newsdc_dev_app php -l /var/www/<caminho>`
  - `docker exec newsdc_dev_app php artisan route:list --name=cedec`

- **Testes rodam NO HOST, nunca no container (corrigido em 2026-09-05).**
  `docker exec newsdc_dev_app php artisan test` FALHA com `Command "test" is not defined`: a
  imagem e montada sem dev dependencies, e `vendor/bin` la dentro so tem `carbon`, `openapi`
  e `patch-type-declarations`. Quem tem PHPUnit e o host.

  Exporte UMA VEZ por terminal, a partir de `SDC/`:

  ```bash
  mkdir -p /c/tmp/newsdc-cache
  export MSYS_NO_PATHCONV=1
  export APP_CONFIG_CACHE=/tmp/newsdc-cache/nao-existe-config.php
  export APP_PACKAGES_CACHE=/tmp/newsdc-cache/packages.php
  export APP_SERVICES_CACHE=/tmp/newsdc-cache/services.php
  export APP_ROUTES_CACHE=/tmp/newsdc-cache/routes.php
  export APP_EVENTS_CACHE=/tmp/newsdc-cache/events.php
  export DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5434 DB_DATABASE=sdc DB_USERNAME=sdc
  export DB_PASSWORD="$(grep -m1 '^DB_PASSWORD=' .env | cut -d= -f2-)"
  ```

  Depois, cada teste e so:

  ```bash
  php -d extension=pdo_pgsql vendor/bin/phpunit --filter=<Nome>
  ```

  Cada peca do prefixo existe por um motivo medido:
  - As CINCO variaveis de cache sao obrigatorias. `APP_CONFIG_CACHE` sozinho NAO basta:
    cobre so o cache de config, enquanto o PHPUnit do host segue reescrevendo
    `bootstrap/cache/packages.php` e `services.php`, que sao bind-mount compartilhado com o
    container. Como a imagem nao tem dev dependencies, o manifest escrito pelo host descreve
    outro conjunto de providers e todo `artisan` dentro do container morre em
    `ProviderRepository`. Recuperacao: apagar os dois arquivos e rodar `artisan` duas vezes
    (a primeira falha recompilando, a segunda passa). Ver a memoria
    `bootstrap-cache-compartilhado-host-container`.
  - `MSYS_NO_PATHCONV=1` e os caminhos comecando com `/` sao obrigatorios porque
    `Application::normalizeCachePath` so considera absoluto o que comeca com `/` ou `\`. Se
    o Git Bash converter para `C:/tmp/...`, o Laravel concatena com o base path e o teste
    morre com "The <projeto>\C:/tmp/newsdc-cache directory must be present and writable".
  - Os `DB_*` sao necessarios porque o `.env` aponta `DB_HOST=newsdc_db`, um nome de rede
    Docker que o host nao resolve, e porque existe um `.env.testing` que forca
    `DB_CONNECTION=sqlite` com `:memory:`. Como `tests/TestCase.php` e vazio, nao roda
    migration nenhuma, e 86 dos 89 arquivos de teste usam `DatabaseTransactions`, cair no
    sqlite significa `no such table` em toda a suite. As variaveis acima vencem o
    `.env.testing` e apontam para o Postgres de desenvolvimento.
  - `-d extension=pdo_pgsql` porque o PHP do Laragon nao carrega a extensao por padrao.

  **Consequencia para os testes:** eles rodam contra o banco de DESENVOLVIMENTO. Nenhum
  teste pode fazer `update()` ou `delete()` sem `where` restrito as linhas que ele mesmo
  criou, e asserçao de contagem tem de ser relativa a um "antes", nunca total absoluto.
- Frontend: `npm run build` na pasta `SDC`.
- Depois de mudar PHP: `octane:reload` (~1s). Restart do container so para `.env`,
  `config/` ou extensao — custa ~3min.
- **Sem emoji dentro do codigo.** Emoji so na mensagem de commit (gitmoji).
- Commits: `<emoji> tipo(escopo): descricao` em pt-BR, escopo `cedec`. Commit atomico:
  agrupar os arquivos que entregam UMA mudanca. **Nao incluir trailer de co-autor.**
- Migration: **consolidar na migration principal existente**, nao criar `add_*`.
- **Teste nao entra no commit (corrigido em 2026-09-05).** `SDC/tests` esta no `.gitignore`
  do repositorio, o que confirma a regra de ouro 10 do usuario. Todo `git add` dos steps
  leva SO os arquivos de producao; o arquivo de teste fica local. Um `git add` que inclua
  caminho sob `SDC/tests` e recusado com "paths are ignored by one of your .gitignore
  files". Escrever o teste continua obrigatorio — TDD nao muda; o que muda e que ele nao e
  versionado. Ha inconsistencia no proprio repo (`SDC/tests/Feature/Pae/PaeFormularioControllerTest.php`
  aparece como rastreado, forcado para dentro em algum momento); nao siga esse exemplo.

### Regras de UI obrigatorias (medidas contra o modulo RAT)

- A calha horizontal e do `<main>`; a raiz da pagina **nao** leva `p-*`.
- `scrollWidth - clientWidth === 0` em 375px e 840px. Quem transborda rola dentro de si.
- Breakpoint por `useMobile`, alinhado em `lg`. Tabela vira BLOCO no mobile.
- Paginacao so com setas.
- Dark mode por CLASSE, nunca `prefers-color-scheme`.
- **Nenhum `<style scoped>` novo.** So Tailwind.
- Nao reimplementar header, secao, card ou campo: usar os componentes da secao 7.
- `SelectInput` le `value`/`id` e `label`/`name`/`text`. Backend que manda `nome`
  precisa ser mapeado para `{ value, label }`.
- Atributo solto (`inputmode`, `maxlength`) passado a `FormField` cai na div raiz e nao
  chega ao input — tem de ser prop declarada e repassada.
- Prop closure no Inertia e avaliada em TODA visita completa. Filtro que nao quer
  recalcular indicador precisa de reload parcial com `only: [...]`.
- Tailwind nao escaneia `app/**/*.php`: classe de cor vinda de enum PHP so entra no CSS
  se existir em algum `.vue` ou no `safelist`.

---

## 1. Banco — colunas novas em `compdec_prefeituras`

Consolidadas **dentro** de
`database/migrations/2026_05_05_100004_create_compdec_prefeituras_table.php`, no bloco
`Schema::create`, logo apos os campos `prefeito_*` existentes:

```php
$table->string('prefeito_partido', 60)->nullable();
$table->string('email_prefeitura', 255)->nullable()
    ->comment('E-mail institucional da prefeitura; alimenta o relatorio de contatos');
$table->string('email_prefeitura_2', 255)->nullable();
$table->string('email_prefeitura_3', 255)->nullable();
$table->string('tel_prefeitura', 20)->nullable();
$table->string('tel_prefeitura_2', 20)->nullable();
$table->string('fax_prefeitura', 20)->nullable();
```

Colunas que JA existem e nao mudam: `id`, `municipio_id` (unique, FK para `municipios`),
`prefeito_nome`, `prefeito_telefone`, `prefeito_celular`, `prefeito_email`, `endereco`,
`bairro`, `cep`, `latitude`, `longitude`, `inss_tem_cobranca`, `inss_aliquota`,
`inss_lei_cobranca`, `inss_responsavel`, `legacy_id`, `timestamps`, `softDeletes`.

Nenhum indice novo.

---

## 2. Model — `App\Modules\Compdec\Models\Prefeitura`

Fica onde esta. O modulo `Cedec` **importa** essa classe, do mesmo jeito que
`Pmda`/`PlanCon`/`AjudaHumanitaria` importam `App\Modules\Compdec\Models\Orgao`.
Nada vai para `App\Models`. Tabela segue `compdec_prefeituras`.

`$fillable` recebe, ao final do array existente:

```php
'prefeito_partido',
'email_prefeitura',
'email_prefeitura_2',
'email_prefeitura_3',
'tel_prefeitura',
'tel_prefeitura_2',
'fax_prefeitura',
```

`$casts` nao muda (as sete sao string nullable).

Ja existe e **nao** deve ser reimplementado: `MEDIA_FOTO_PREFEITO = 'foto_prefeito'`,
`registerMediaCollections()` (`singleFile`, mimes jpeg/png/webp),
`registerMediaConversions()` (`thumb`, `Fit::Contain` 200x200, `nonQueued`),
o accessor `fotoPrefeitoUrl`, a relacao `municipio()`, `PrefeituraFactory`.

---

## 3. DTOs

### `App\Modules\Compdec\DTOs\PrefeituraDTO` (existente, estendido)

Sete propriedades novas no construtor, em camelCase, todas `?string $x = null`, na
ordem: `prefeitoPartido`, `emailPrefeitura`, `emailPrefeitura2`, `emailPrefeitura3`,
`telPrefeitura`, `telPrefeitura2`, `faxPrefeitura`.

`fromRequest(int $municipioId, array $data): self` le as chaves snake_case
(`prefeito_partido`, `email_prefeitura`, `email_prefeitura_2`, `email_prefeitura_3`,
`tel_prefeitura`, `tel_prefeitura_2`, `fax_prefeitura`).

`toArray(): array` devolve as mesmas chaves snake_case.

### `App\Modules\Cedec\DTOs\PrefeituraFiltroDTO` (novo)

```php
final class PrefeituraFiltroDTO
{
    public function __construct(
        public ?string $busca = null,
        public ?int $redecId = null,
        public ?string $macrorregiao = null,
        public ?string $pendencia = null,
        public int $perPage = 20,
    ) {}

    public static function fromRequest(\Illuminate\Http\Request $request): self;

    /** @return array{busca: ?string, redec_id: ?int, macrorregiao: ?string, pendencia: ?string} */
    public function toArray(): array;
}
```

`$pendencia` aceita exatamente `'sem_email'`, `'sem_telefone'`, `'sem_foto'` ou `null`.
`$perPage` limitado a 20, 50 ou 100; qualquer outro valor cai em 20.

**Armadilha do nome da coluna (medida no banco):** o dominio e o DTO usam
`macrorregiao`, com dois `r`. A COLUNA, tanto em `gestaocedec_local.cedec_municipio`
quanto na migration `2026_03_03_000001_create_cedec_municipio_table.php` do NewSDC, chama
`macroregiao`, com um `r` so. Toda query tem de usar a grafia da coluna; so a interface
usa a grafia correta. Documente isso no service, ou o filtro volta vazio em silencio.

**Armadilha de teste com Inertia:** `AssertableInertia::component()` confere NO DISCO se
o arquivo `.vue` existe (`ensure_pages_exist` em `config/inertia.php`). Em fase que ainda
nao criou a pagina — a fase 2 e o caso — o teste do controller precisa passar `false` como
segundo argumento: `->component('Cedec/Prefeituras/Index', false)`.

---

## 4. Services

### `App\Modules\Cedec\Services\CedecPrefeituraService` (novo)

```php
final class CedecPrefeituraService
{
    /** Municipios (853) com LEFT JOIN em compdec_prefeituras: municipio sem
     *  prefeitura tambem aparece. Ordena por municipios.nome. */
    public function listar(PrefeituraFiltroDTO $filtro): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /** @return array{total: int, sem_email: int, sem_telefone: int, sem_foto: int} */
    public function estatisticas(): array;

    public function obterPorMunicipio(int $municipioId): ?Prefeitura;

    /** Indicadores read-only vindos de municipios + cedec_municipio.
     *  @return array{populacao: ?float, pop_rural: ?int, area: ?string,
     *   macrorregiao: ?string, territorio_desenv: ?string, distancia_bh: ?float,
     *   qtd_pipa: ?int, latitude: ?string, longitude: ?string, origem: string} */
    public function indicadoresMunicipais(int $municipioId): array;

    /** updateOrCreate por municipio_id, em transacao. */
    public function upsertPorMunicipio(int $municipioId, PrefeituraDTO $dto): Prefeitura;

    public function uploadFoto(int $municipioId, \Illuminate\Http\UploadedFile $arquivo): \Spatie\MediaLibrary\MediaCollections\Models\Media;

    public function removerFoto(int $municipioId): bool;
}
```

`origem` em `indicadoresMunicipais()` e a string literal
`'cedec_municipio (espelho do legado)'`, exibida em tela.

O `PrefeituraService` do Compdec **continua intacto** — os dois convergem na mesma
linha via `updateOrCreate(['municipio_id' => ...])`.

### `App\Modules\Cedec\Services\ContatoRelatorioService` (novo)

```php
final class ContatoRelatorioService
{
    public const TAMANHO_BLOCO_PADRAO = 50;

    /** @return \Illuminate\Support\Collection<int, array{municipio_id: int,
     *   municipio_nome: string, email_prefeitura: ?string,
     *   email_prefeitura_2: ?string, email_prefeitura_3: ?string}> */
    public function emails(): \Illuminate\Support\Collection;

    /** @return \Illuminate\Support\Collection<int, array{municipio_id: int,
     *   municipio_nome: string, tel_prefeitura: ?string, tel_prefeitura_2: ?string,
     *   fax_prefeitura: ?string, prefeito_telefone: ?string,
     *   prefeito_celular: ?string}> */
    public function telefones(): \Illuminate\Support\Collection;

    /** Blocos de e-mail concatenados com "; ". Descarta vazio antes de blocar.
     *  @return array<int, array{indice: int, total: int, texto: string}> */
    public function blocosDeEmail(int $tamanho = self::TAMANHO_BLOCO_PADRAO): array;

    /** Mesma forma, sobre tel_prefeitura + tel_prefeitura_2 + prefeito_telefone
     *  + prefeito_celular + fax_prefeitura. */
    public function blocosDeTelefone(int $tamanho = self::TAMANHO_BLOCO_PADRAO): array;

    /** Primeira linha e o cabecalho.
     *  @return array<int, array<int, string>> */
    public function csvEmails(): array;

    /** @return array<int, array<int, string>> */
    public function csvTelefones(): array;
}
```

`indice` comeca em 1. `total` e a quantidade de contatos DENTRO daquele bloco (o
ultimo bloco costuma ter menos que `$tamanho`). Fronteira acordada: 50 contatos geram
1 bloco; 51 geram 2; 100 geram 2; 101 geram 3.

`$tamanho` menor que 1 lanca `InvalidArgumentException`.

---

## 5. Enums — `App\Modules\Cedec\Enums`

Ambos `enum X: string`, com o **rotulo como valor**, porque o legado gravava o texto
do rotulo em `varchar`, nao um id. Cada um expoe:

```php
public function label(): string;             // devolve o proprio value
/** @return array<int, array{value: string, label: string}> */
public static function opcoes(): array;      // pronto para SelectInput
```

`Macrorregiao` — 10 casos, valores exatos do legado (`cadastrar.php:20-30`):
`SUL DE MINAS`, `ALTO PARANAIBA`, `CENTRAL`, `ZONA DA MATA`, `VALE DO RIO DOCE`,
`TRIANGULO`, `CENTRO OESTE`, `JEQUITINHONHA MUCURI`, `NORTE DE MINAS`,
`NOROESTE DE MINAS`.

`TerritorioDesenvolvimento` — 17 casos, valores exatos do legado
(`cadastrar.php:33-49`): `Vertentes`, `Vale do Rio Doce`, `Vale do Aco`,
`Triangulo Sul`, `Triangulo Norte`, `Sul`, `Sudoeste`, `Oeste`, `Norte`, `Noroeste`,
`Mucuri`, `Metropolitana`, `Medio e Baixo Jequitinhonha`, `Mata`, `Central`,
`Caparao`, `Alto Jequitinhonha`.

---

## 6. HTTP

### Rotas — `routes/modules/cedec.php`, carregado em `routes/web.php`

```
GET    /cedec/prefeituras                   cedec.prefeituras.index        can:cedec.prefeituras.view
GET    /cedec/prefeituras/{municipio}/edit  cedec.prefeituras.edit         can:cedec.prefeituras.view
PUT    /cedec/prefeituras/{municipio}       cedec.prefeituras.update       can:cedec.prefeituras.edit
POST   /cedec/prefeituras/{municipio}/foto  cedec.prefeituras.foto.upload  can:cedec.prefeituras.edit
DELETE /cedec/prefeituras/{municipio}/foto  cedec.prefeituras.foto.destroy can:cedec.prefeituras.edit
GET    /cedec/contatos                      cedec.contatos.index           can:cedec.contatos.view
GET    /cedec/contatos/export               cedec.contatos.export          can:cedec.prefeituras.export
```

**Armadilha obrigatoria:** `Route::model()` e GLOBAL neste projeto e ja causou 404 no
Pmda e no PlanCon. `{municipio}` ja e usado por `routes/modules/cisterna.php:100` sem
binder explicito. Portanto **NAO registrar `Route::model('municipio', ...)`** — usar
binding implicito por type-hint `App\Models\Municipio` no controller.

O binding e por `Municipio`, nao por `Prefeitura`: a CEDEC navega pelos 853 municipios,
inclusive os que ainda nao tem linha de prefeitura.

### Controllers — `App\Modules\Cedec\Controllers`

```php
final class PrefeituraController extends \App\Http\Controllers\Controller
{
    public function __construct(private readonly CedecPrefeituraService $service) {}

    public function index(Request $request): \Inertia\Response;
    public function edit(Municipio $municipio): \Inertia\Response;
    public function update(UpdatePrefeituraRequest $request, Municipio $municipio): \Illuminate\Http\RedirectResponse;
    public function uploadFoto(Request $request, Municipio $municipio): \Illuminate\Http\RedirectResponse;
    public function removerFoto(Municipio $municipio): \Illuminate\Http\RedirectResponse;
}

final class ContatoRelatorioController extends \App\Http\Controllers\Controller
{
    public function __construct(private readonly ContatoRelatorioService $service) {}

    public function index(Request $request): \Inertia\Response;
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse;
}
```

Paginas Inertia renderizadas: `Cedec/Prefeituras/Index`, `Cedec/Prefeituras/Edit`,
`Cedec/Contatos/Index`.

### Request — `App\Modules\Cedec\Requests\UpdatePrefeituraRequest`

`authorize()` devolve `$this->user()?->can('cedec.prefeituras.edit') ?? false`.

`rules()`:

```php
'prefeito_nome'       => ['nullable', 'string', 'max:255'],
'prefeito_partido'    => ['nullable', 'string', 'max:60'],
'prefeito_telefone'   => ['nullable', 'string', 'max:20'],
'prefeito_celular'    => ['nullable', 'string', 'max:20'],
'prefeito_email'      => ['nullable', 'email', 'max:255'],
'email_prefeitura'    => ['nullable', 'email', 'max:255'],
'email_prefeitura_2'  => ['nullable', 'email', 'max:255'],
'email_prefeitura_3'  => ['nullable', 'email', 'max:255'],
'tel_prefeitura'      => ['nullable', 'string', 'max:20'],
'tel_prefeitura_2'    => ['nullable', 'string', 'max:20'],
'fax_prefeitura'      => ['nullable', 'string', 'max:20'],
'endereco'            => ['nullable', 'string'],
'bairro'              => ['nullable', 'string', 'max:120'],
'cep'                 => ['nullable', 'string', 'max:10', 'regex:/^\d{5}-?\d{3}$/'],
'latitude'            => ['nullable', 'numeric', 'between:-90,90'],
'longitude'           => ['nullable', 'numeric', 'between:-180,180'],
'inss_tem_cobranca'   => ['nullable', 'boolean'],
'inss_aliquota'       => ['nullable', 'numeric', 'between:0,100'],
'inss_lei_cobranca'   => ['nullable', 'string', 'max:120'],
'inss_responsavel'    => ['nullable', 'string', 'max:255'],
```

`messages()`: `'cep.regex' => 'CEP deve estar no formato 00000-000 ou 00000000.'`

Os indicadores municipais **nao** entram nas regras: sao read-only na fase 1.

### Resource — `App\Modules\Cedec\Resources\PrefeituraListaResource`

Uma linha da listagem:

```php
[
    'municipio_id'     => int,
    'municipio_nome'   => string,
    'codigo_ibge'      => string,
    'redec'            => ?string,
    'prefeito_nome'    => ?string,
    'email_prefeitura' => ?string,
    'tel_prefeitura'   => ?string,
    'tem_foto'         => bool,
]
```

---

## 7. Permissoes

Slugs, em `config/permissions.php`:

| Slug | Legado em `cedec_permissao` |
| --- | --- |
| `cedec.prefeituras.view` | `cad_prefeitura` |
| `cedec.prefeituras.edit` | `alterar_prefeitura` |
| `cedec.prefeituras.export` | (novo; cobre listagem e relatorios) |
| `cedec.contatos.view` | `relatorio` |

**Correcao 2026-09-04:** entram em DOIS lugares, nao tres. A "lista achatada de
permissoes" que a versao anterior deste contrato mandava editar NAO EXISTE —
`RolesAndPermissionsSeeder::getAllPermissionSlugs()` deriva a lista automaticamente do
bloco `modules`. Os dois lugares reais sao:

1. o bloco `modules`, sob a chave `'Cedec'`, com os sub-blocos
   `'Prefeituras' => ['view','edit','export']` e `'Contatos' => ['view']`;
2. `role_permissions`.

**Quais perfis:** "perfis CEDEC estaduais" nao corresponde a nenhum cargo do sistema (os
perfis sao niveis de hierarquia). Replique o padrao que `compdec.prefeitura.edit` ja usa:
`admin`, `manager` e `analyst`. Perfis municipais/COMPDEC **nao** recebem nenhum dos quatro.

Depois de mexer no arquivo: RESTART do container (config cache) e
`php artisan db:seed --class=RolesAndPermissionsSeeder`.

**Catalogo de REDECs** (a versao anterior nao dizia de onde vem): reusar
`App\Modules\Decretacoes\Services\RedecService`, que ja existe e e cacheado. E uma
dependencia cross-modulo nova, transparente para as fases 3, 4 e 5 — elas so consomem a
prop `redecs` no formato `[{value, label}]`.

---

## 8. Frontend

### Componentes existentes a reusar — nao reimplementar

| Caminho | Uso |
| --- | --- |
| `Components/Organisms/PageHeader.vue` | `variant="gradient"`, `:icon-image="moduleIcon('prefeituras')"`, acoes no slot `#actions` |
| `Components/Molecules/Statistics/StatCard.vue` dentro de `Molecules/Statistics/StatCardsGrid.vue` | card E filtro rapido. **Correcao 2026-09-04:** o card "Total" TAMBEM e `clickable` e emite `filter(null)` para LIMPAR a pendencia — e o que `Organisms/Pmda/PmdaStatisticsCards.vue:4-5` faz (`clickable @click="$emit('filter', '')"`). A versao anterior deste contrato dizia o contrario e estava errada |
| `Components/Molecules/Filter/FilterSection.vue` + `FilterField.vue` + `FilterActions.vue` | **secao de FILTRO.** Correcao 2026-09-04: e o componente canonico, com 28 consumidores contra 9 do `CollapsibleSection`, e 11 dos 12 `*FiltersSection.vue` do projeto usam ele (o unico fora e o `Cisterna/BeneficiarioFiltersSection.vue`). A versao anterior deste contrato mandava `CollapsibleSection` para filtro e estava errada |
| `Components/Molecules/CollapsibleSection.vue` | **secao de FORMULARIO**, com `namespace="cedec"`. Nao usar para filtro |
| `Components/Molecules/Form/FormField.vue`, `FormSelect.vue`, `ToggleField.vue`, `FormActions.vue` | campos |
| `Components/Molecules/Navigation/Pagination.vue` | **Correcao 2026-09-04:** o componente recebe UMA prop `pagination: Object`. "Achatado" descreve a FORMA desse objeto — `current_page`, `last_page`, `per_page`, `total`, `from`, `to` no primeiro nivel, sem `meta` aninhado — nao seis props separadas. Ele renderiza numeros de pagina alem das setas; a regra "so setas" da skill de responsividade nao esta implementada nele e mudar isso afetaria 20 consumidores, entao esta FORA do escopo do modulo Cedec |
| `Components/Molecules/ListEmptyState.vue` | props `title` e `helper` |
| `Components/Atoms/Button/ActionButton.vue` | acoes |
| `Components/Organisms/ExportCsvModal.vue` | export |
| `Composables/useMobile.js` | breakpoint |
| `Composables/useCopiarTexto.js` | botao copiar dos blocos |
| `Support/moduleIcons.js` | registrar `prefeituras: apartment` em `MODULE_ICONS`; `apartment` ja esta em `ICONS` e nao esta mapeado |

### Componentes novos

```
Pages/Cedec/Prefeituras/Index.vue
Pages/Cedec/Prefeituras/Edit.vue
Pages/Cedec/Contatos/Index.vue
Components/Organisms/Cedec/PrefeituraFiltersSection.vue
Components/Organisms/Cedec/PrefeituraTable.vue
Components/Organisms/Cedec/PrefeituraFormSections.vue
Components/Organisms/Cedec/IndicadoresMunicipaisPanel.vue
Components/Organisms/Cedec/ContatoBlocosOutlook.vue
Components/Organisms/Cedec/PrefeituraStatCards.vue
Components/Molecules/Cedec/ContatoBloco.vue
```

**Correcao 2026-09-04:** `PrefeituraStatCards` e ORGANISMO, nao molecula. Os cinco
equivalentes do projeto vivem todos em `Organisms/`: `Organisms/Pmda/PmdaStatisticsCards.vue`,
`Organisms/Cisterna/CisternaStatisticsCards.vue`, `Organisms/Demandas/Statistics/`,
`Organisms/Rat/Statistics/`, `Organisms/Tdap/Statistics/`. Quem e molecula e o
`StatCardsGrid`/`StatCard` que ele compoe.

Contrato de camada: atomo e molecula **nao** chamam API nem estado global; organismo
concentra interacao; a pagina orquestra.

Props dos novos:

```
PrefeituraStatCards      { estatisticas: {total, sem_email, sem_telefone, sem_foto} }
                         emit: filter(pendencia: string|null)
PrefeituraFiltersSection { filters, redecs: [{value,label}], macrorregioes: [{value,label}] }
                         emits: apply(filtros), clear()
PrefeituraTable          { prefeituras: Array<linha do PrefeituraListaResource>, podeEditar: boolean }
IndicadoresMunicipaisPanel { indicadores: <retorno de indicadoresMunicipais()> }
PrefeituraFormSections   { form: Object, errors: Object, podeEditar: boolean }
ContatoBloco             { indice: number, total: number, texto: string }
ContatoBlocosOutlook     { blocos: Array<{indice,total,texto}>, tamanhoBloco: number }
```

### Props Inertia por pagina

`Cedec/Prefeituras/Index`:

```
prefeituras   { data: [linha do PrefeituraListaResource], current_page, last_page,
                per_page, total, from, to }
estatisticas  { total, sem_email, sem_telefone, sem_foto }
filtros       { busca, redec_id, macrorregiao, pendencia }
redecs        [{ value, label }]
macrorregioes [{ value, label }]
```

`Cedec/Prefeituras/Edit`:

```
municipio    { id, nome, codigo_ibge, uf }
prefeitura   { todas as colunas de compdec_prefeituras + foto_prefeito_url }
indicadores  { retorno de indicadoresMunicipais() }
```

`Cedec/Contatos/Index`:

```
aba            'emails' | 'telefones'
emails         [{ municipio_id, municipio_nome, email_prefeitura, email_prefeitura_2, email_prefeitura_3 }]
telefones      [{ municipio_id, municipio_nome, tel_prefeitura, tel_prefeitura_2, fax_prefeitura, prefeito_telefone, prefeito_celular }]
blocos         [{ indice, total, texto }]
tamanho_bloco  50
totais         { emails_preenchidos: int, telefones_preenchidos: int, municipios: int }
```

### Refit do formulario do Compdec (fase 4)

`Components/Organisms/Compdec/PrefeituraForm.vue` hoje importa `TextInput`,
`SelectInput`, `Heading` e `Button` crus e tem `<style scoped>` com `.form-section` e
`.form-grid`. Ele e reescrito sobre `Molecules/Form/*` + `CollapsibleSection`, o
`<style scoped>` some, e ele passa a ser consumido pela aba do Compdec
(`Organisms/Compdec/Tabs/PrefeituraTab.vue`) **e** pelo `Edit.vue` do Cedec. Um
formulario, dois donos. A aba do Compdec tem de continuar funcionando.

---

## 9. ETL — fase 1

Corrige `App\Modules\Compdec\Services\PrefeituraService::migrarLegado()`, que hoje faz
`SELECT c.prefeito_nome, c.prefeito_telefone, c.prefeito_celular, c.prefeito_email,
c.inss_tem_cobranca, c.inss_aliquota, c.inss_lei_cobranca, c.inss_responsavel
FROM com_comdec c` — **nenhuma dessas colunas existe** em `com_comdec` (verificado no
`information_schema` do banco legado).

**Correcao 2026-09-04 — conexao legada.** NAO use `config('compdec.legacy_connection', 'legacy')`
e NAO troque o banco dela. Medido:

- `dbsdc` e `gestaocedec_local` sao dois sistemas legados DIFERENTES, com nomes de tabela
  colidentes e schemas incompativeis. `dbsdc` e do repo `sdc` (Laravel); `gestaocedec_local`
  e do repo `gestaocedec` (PHP puro). `dbsdc.cedec_municipio` nao tem sequer as colunas
  `email` e `prefeito`; `gestaocedec_local.cedec_municipio` tem, com 854 linhas.
- `docker/compose.dev.yml:199` injeta `DB_LEGACY_DATABASE: dbsdc` no bloco `environment:`, o
  que SOBREPOE o `.env`. Ja `.env.example:139` diz `gestaocedec_local` — documentacao e
  runtime discordam. Nao conserte isso aqui; registre como pendencia.
- A conexao `legacy` e compartilhada por sete consumidores (Compdec `AnexoService`,
  `EquipeService`, `OrgaoService`, `PlanoContingenciaService`, `PrefeituraService`; Pmda
  `ComunidadeLegadoService`; AjudaHumanitaria; `ImportPontosCaptacaoCommand`;
  `MigrarCompdecLegadoCommand`). Virar o banco embaixo deles e mudanca de alto risco.

O caminho e uma conexao DEDICADA, no molde de `legado_cisterna_mysql`
(`config/database.php:88`, somente leitura, consumida por um unico command): adicionar
`legado_gestaocedec` apontando para `gestaocedec_local`, com chaves proprias de env, e o
modulo Cedec lendo dela via `config('cedec.legacy_connection')`.

Origem correta: `cedec_municipio` LEFT JOIN `cedec_prefeitura` ON `id_municipio`.
Ponte para o NewSDC: `cedec_municipio.Codmundv = municipios.codigo_ibge`.
Exclui `cedec_municipio.id_municipio = 7221` (sentinela "MUNICIPIO TESTE",
`Codmundv = 0`, e-mail `prefeitura@prefeitura@gmail.com1`).

Precedencia por campo:

| Destino | Origem | Fallback |
| --- | --- | --- |
| `prefeito_nome` | `cedec_municipio.prefeito` | nenhum. NUNCA `cedec_prefeitura.prefeiro`, que esta no mandato anterior |
| `prefeito_telefone` | `cedec_municipio.tel_pref` | — |
| `prefeito_celular` | `cedec_municipio.cel_pref` | — |
| `prefeito_partido` | `cedec_prefeitura.partido` | — |
| `prefeito_email` | **sem origem confiavel no legado**: fica `null`. O legado nunca separou e-mail do prefeito do e-mail da prefeitura | — |
| `email_prefeitura` | `cedec_municipio.email` | `cedec_prefeitura.email` se vazio |
| `email_prefeitura_2` | `cedec_prefeitura.email2` | — |
| `email_prefeitura_3` | `cedec_prefeitura.email3` | — |
| `tel_prefeitura` | `cedec_municipio.tel` | `cedec_prefeitura.tel1` se vazio |
| `tel_prefeitura_2` | `cedec_prefeitura.tel2` | — |
| `fax_prefeitura` | `cedec_municipio.fax` | `cedec_prefeitura.fax` se vazio |
| `endereco`, `bairro`, `cep`, `latitude`, `longitude` | `cedec_municipio` | — |
| `inss_tem_cobranca` | `cedec_municipio.cobra_iss` | — |
| `inss_aliquota` | `cedec_municipio.aliquota_iss` | — |
| `inss_lei_cobranca` | `cedec_municipio.num_lei_iss` | — |
| `inss_responsavel` | `cedec_municipio.resp_cob_iss` | — |
| `legacy_id` | `cedec_municipio.id_municipio` | — |
| foto (media) | `cedec_prefeitura.fotoPref` | — |

Higienizacao, aplicada antes de gravar: `trim` em tudo; e-mail para minusculas; `"-"`,
`""` e string so de espacos viram `null`; e-mail sintaticamente invalido vira `null` e
gera linha em `compdec_etl_log` com `acao = 'skipped'`; telefone normalizado.

Reusa `App\Modules\Compdec\Support\LegacyParser` (`toIntOrNull`, `toStringOrNull`,
`toDecimalBR`, `toBool`) e `MigracaoReport`. Mantem `compdec_etl_log` e a flag
`--dry-run`.

Command novo: `App\Modules\Cedec\Console\ImportarPrefeiturasCommand`, assinatura
`cedec:importar-prefeituras {--chunk=100} {--dry-run}`.

Numeros medidos no legado, uteis para assercao: 854 linhas em `cedec_municipio` e em
`cedec_prefeitura`; 853 municipios reais; 716 e-mails em `cedec_municipio.email`;
854 prefeitos; 366 telefones em `.tel`; 150 fax; 561 `tel_pref`; 709 `cel_pref`;
423 fotos em `fotoPref`; 147 `cobra_iss`; 764 divergencias de e-mail entre as duas
tabelas.

---

## 10. Divisao das fases

| Fase | Arquivo do plano | Entrega |
| --- | --- | --- |
| 1 | `2026-09-04-cedec-fase1-dados-etl.md` | migration consolidada, Model, PrefeituraDTO, ETL corrigido, command |
| 2 | `2026-09-04-cedec-fase2-backend-permissoes.md` | modulo Cedec backend, enums, filtro, service, controllers, request, resource, rotas, permissoes, sidebar |
| 3 | `2026-09-04-cedec-fase3-index.md` | `Prefeituras/Index.vue`, filtros, tabela, stat cards |
| 4 | `2026-09-04-cedec-fase4-edit-foto.md` | `Prefeituras/Edit.vue`, form sections, painel read-only, foto, refit do form do Compdec |
| 5 | `2026-09-04-cedec-fase5-relatorios.md` | `Contatos/Index.vue`, blocos, copiar, export CSV |

Dependencia: 2 depende de 1; 3, 4 e 5 dependem de 2. Cada fase e um commit atomico
(ou poucos), verificavel sozinha.
