# RAT API — GET /api/v1/rat/protocolos + POST + PowerBI

**Data:** 2026-04-15
**Módulo:** `app/Modules/Rat` + `app/Http/Controllers/Api/V1/Rat`
**Padrão de referência:** Decretações (`DecretacoesApiController` + `ProcessoQueryService` + `EntradaProcessoService`)

---

## Contexto

O endpoint `GET /api/v1/rat/protocolos` existe no sistema mas retorna dados vazios (stub).
O `ProtocoloController` atual referencia um model `Protocolo` inexistente e não tem lógica real.

O objetivo é:
1. Implementar o GET com todos os campos das abas do Frontend — listagem paginada e export flat para PowerBI via `?format=powerbi`
2. Implementar o POST para recebimento de dados externos com rate limiting
3. Seguir exatamente o padrão já validado no módulo de Decretações

**Fonte de dados:** model `Rat` (`rats` table, UUID + colunas JSON por aba) em `app/Modules/Rat/Models/Rat.php`.

---

## Arquitetura

```
app/
├── Http/Controllers/Api/V1/Rat/
│   └── RatApiController.php             ← substitui o stub ProtocoloController (3 métodos públicos)
│
├── Modules/Rat/
│   ├── DTOs/
│   │   └── RatReceiveBiDTO.php          ← readonly DTO: mapeia Request → array do model Rat
│   ├── Http/Requests/
│   │   └── ReceiveRatBiRequest.php      ← FormRequest com rules() de todas as abas
│   └── Services/
│       ├── RatExportBiService.php       ← GET: listForApi() + listForPowerBI()
│       └── RatReceiveBiService.php      ← POST: valida origem → RatWriteService::createWithData()

routes/
└── api.php                              ← adiciona 3 rotas com auth dual + rate limiting
```

**Arquivos intocados:** `RatFilterService`, `RatFilterDTO`, `RatResource`, `RatListResource`,
`RatWriteService`, `RatProtocoloService`, `RatService`, `EloquentRatRepository`.

---

## Rotas

```php
// GET — leitura / PowerBI (tier pro: 1000 créditos/min)
Route::prefix('v1/rat')
    ->middleware(['decretacoes.api.auth', 'api-rate-limiter:pro'])
    ->group(function () {
        Route::get('protocolos',       [RatApiController::class, 'index'])->name('api.v1.rat.protocolos.index');
        Route::get('protocolos/{id}',  [RatApiController::class, 'show'])->name('api.v1.rat.protocolos.show');
    });

// POST — escrita externa (tier default: 300 créditos/min)
Route::prefix('v1/rat')
    ->middleware(['decretacoes.api.auth', 'api-rate-limiter:default'])
    ->group(function () {
        Route::post('protocolos', [RatApiController::class, 'receive'])->name('api.v1.rat.protocolos.receive');
    });
```

O bloco existente `Route::apiResource('protocolos', ProtocoloController::class)` dentro do grupo `auth:sanctum` é **removido**.

**Middleware de auth:** reutiliza `decretacoes.api.auth` — suporta Sanctum + token PowerBI sem código novo.

---

## RatApiController

Thin controller, 3 métodos públicos — espelho do `DecretacoesApiController`.

```php
// index(): detecta ?format=powerbi → delega para RatExportBiService
// show(string $id): busca Rat por UUID → 404 ou dados completos via RatResource
// receive(ReceiveRatBiRequest $request): cria Rat externo via RatReceiveBiService
```

---

## RatExportBiService

Responsabilidade única: consultar e formatar dados do model `Rat` para API.

### listForApi(RatFilterDTO $filters): LengthAwarePaginator
- Reutiliza `RatFilterService::apply()` + `EloquentRatRepository::paginate()`
- Retorna paginado com `RatListResource`
- Filtros aceitos via query string: `protocolo`, `status`, `municipio`, `data_inicio`, `data_fim`, `ano`, `per_page`

### listForPowerBI(RatFilterDTO $filters): array
- Busca todos (sem paginação, perPage=9999)
- Desnormaliza cada `Rat` em linhas flat — cada recurso e cada envolvido gera uma linha separada (mesmo padrão do `RatNovoService::flattenOcorrenciaData()`)
- Retorna array simples para consumo direto no Power BI

**Detecção do modo:** `RatApiController::index()` verifica `$request->input('format') === 'powerbi'`.

---

## RatReceiveBiService

Responsabilidade única: receber dados externos e persistir como Rat.

```php
public function receive(RatReceiveBiDTO $dto): Rat
{
    // Delega para RatWriteService::createWithData($dto->toModelArray())
    // RatWriteService já gera protocolo automático via RatProtocoloService
}
```

Não duplica lógica de escrita — apenas transforma o DTO externo no array que `createWithData()` espera.

---

## RatReceiveBiDTO

Readonly DTO que mapeia os campos do request externo para a estrutura interna do model `Rat`.

```php
readonly class RatReceiveBiDTO {
    public function __construct(
        public readonly array $dadosGerais,
        public readonly array $comunicacao,
        public readonly array $local,
        public readonly array $endereco,
        public readonly array $recursos,
        public readonly array $envolvidos,
        public readonly array $vistoria,
        public readonly bool  $finalize,
    ) {}

    public static function fromRequest(ReceiveRatBiRequest $request): self { ... }

    public function toModelArray(): array { ... }
}
```

---

## ReceiveRatBiRequest

FormRequest com `rules()` cobrindo todos os campos das abas, espelho do `UpdateRatRequest`
com adição de `finalize` e validações mais rígidas para input externo.

### Campos validados por aba

**dados_gerais:**
- `dados_gerais.data_fato` — nullable|string
- `dados_gerais.data_inicio_atividade` — nullable|string
- `dados_gerais.data_termino_atividade` — nullable|string
- `dados_gerais.nat_cobrade_id` — nullable
- `dados_gerais.nat_nome_operacao` — nullable|string|max:255
- `dados_gerais.tem_vistoria` — nullable|boolean

**comunicacao:**
- `comunicacao.tipo_solicitacao` — nullable|string|in:telefone,radio,pessoal,sistema,email,outro
- `comunicacao.data_comunicacao` — nullable|string
- `comunicacao.telefone_contato` — nullable|string|max:20
- `comunicacao.nome_solicitante` — nullable|string|max:255

**local:**
- `local.pais_id` — nullable|integer
- `local.uf` — nullable|string|size:2
- `local.municipio_id` — nullable

**endereco:**
- `endereco.cep` — nullable|string|max:10
- `endereco.logradouro` — nullable|string|max:255
- `endereco.numero` — nullable|string|max:20
- `endereco.complemento` — nullable|string|max:255
- `endereco.bairro` — nullable|string|max:150
- `endereco.km` — nullable|string|max:20
- `endereco.cruzamento` — nullable|string
- `endereco.ponto_referencia` — nullable|string
- `endereco.tipo_localizacao` — nullable|string|in:urbana,rural,rodovia,estrada,mata,montanha,rio,lago,outros
- `endereco.latitude` — nullable|numeric|between:-90,90
- `endereco.longitude` — nullable|numeric|between:-180,180

**recursos (array):**
- `recursos` — nullable|array
- `recursos.*.tipo_recurso` — nullable|string
- `recursos.*.categoria` — nullable|string
- `recursos.*.orgao_responsavel` — nullable|string
- `recursos.*.identificacao` — nullable|string
- `recursos.*.condutor` — nullable|string
- `recursos.*.descricao` — nullable|string
- `recursos.*.data_saida` — nullable|string
- `recursos.*.data_chegada` — nullable|string
- `recursos.*.km_percorrido` — nullable|numeric
- `recursos.*.local_origem` — nullable|string
- `recursos.*.local_destino` — nullable|string

**envolvidos (array):**
- `envolvidos` — nullable|array
- `envolvidos.*.tipo_pessoa` — nullable|string
- `envolvidos.*.cpf` — nullable|string
- `envolvidos.*.nome` — nullable|string
- `envolvidos.*.nome_social` — nullable|string
- `envolvidos.*.data_nascimento` — nullable|date
- `envolvidos.*.idade_aparente` — nullable|integer
- `envolvidos.*.sexo` — nullable|string
- `envolvidos.*.nome_mae` — nullable|string
- `envolvidos.*.nome_pai` — nullable|string
- `envolvidos.*.ocupacao` — nullable|string
- `envolvidos.*.escolaridade` — nullable|string
- `envolvidos.*.cep` / `.uf` / `.municipio` / `.logradouro` / `.bairro` / `.numero` / `.complemento` — nullable|string

**vistoria (objeto):**
- `vistoria` — nullable|array
- `vistoria.solicitante.nome` / `.cpf` / `.telefone` / `.cep` / `.bairro` / `.endereco` — nullable|string
- `vistoria.imovel.endereco` / `.bairro` / `.municipio` / `.cep` — nullable|string
- `vistoria.estrutura.tipo_imovel` / `.tipo_construcao` / `.tipo_destinacao` / `.tipo_edificacao` — nullable|string
- `vistoria.estrutura.sistema_estrutural` / `.estado_conservacao` / `.regime_ocupacao` — nullable|string
- `vistoria.estrutura.num_pavimentos` — nullable|integer
- `vistoria.moradores.proprietario` / `.telefone` — nullable|string

**raiz:**
- `finalize` — nullable|boolean

---

## Registro no RatServiceProvider

```php
$this->app->singleton(RatExportBiService::class);
$this->app->singleton(RatReceiveBiService::class);
```

---

## Resposta dos endpoints

### GET /api/v1/rat/protocolos (paginado)
```json
{
  "success": true,
  "data": { "current_page": 1, "data": [...], "total": 42 }
}
```

### GET /api/v1/rat/protocolos?format=powerbi (flat)
```json
{
  "success": true,
  "data": [
    { "id": "...", "protocolo": "RAT-2026-00001", "status": "finalizado",
      "dados_gerais_data_fato": "2026-01-10", "local_uf": "MG",
      "recurso_tipo_recurso": "Viatura", "envolvido_nome": "João Silva", ... }
  ],
  "meta": { "total_registros": 84, "gerado_em": "2026-04-15T..." }
}
```

### POST /api/v1/rat/protocolos
```json
{ "success": true, "data": { ...RatResource completo... } }
```
HTTP 201 em sucesso, 422 em validação, 429 em rate limit excedido.

---

## O que NÃO está no escopo

- Autenticação nova — reutiliza `decretacoes.api.auth`
- Rate limiter novo — reutiliza `ApiRateLimiter` existente
- Endpoints PUT/DELETE/PATCH — fora do escopo desta API de integração
- Migração de banco — nenhuma, o model `Rat` e a tabela `rats` já existem
