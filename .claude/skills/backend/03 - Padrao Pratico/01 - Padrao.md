# Padrao Pratico do Backend - NewSDC (fonte unica)

Referencia verificada contra o codigo real em `SDC/app/`. Substitui notas antigas que
divergiam (nao existe UseCase nem Repository neste projeto; o banco e PostgreSQL, nao MySQL).

## Fluxo real

```
Request (HTTP)
  -> FormRequest        (validacao de formato + authorize)
  -> Controller         (thin: monta DTO, delega, retorna)
  -> DTO                (dados tipados via fromRequest())
  -> Service            (logica de negocio + transacao)
  -> Model (Eloquent)   (persistencia + scopes)
  -> Resource           (serializacao JSON)
```

Sem camada UseCase. Sem Repository. O Service fala direto com o Model Eloquent.

## Estrutura de um modulo

Modulos ficam em `SDC/app/Modules/{Modulo}/`. Layout tipico (ex: Cisterna, Pae, Decretacoes):

```
app/Modules/{Modulo}/
  Controllers/      {Modulo}Controller.php        (web/Inertia)
  Requests/         Store{Modulo}Request.php, Update{Modulo}Request.php
  DTOs/             {Modulo}DTO.php               (algumas pastas usam DTO/)
  Services/         {Modulo}Service.php
  Models/           {Modulo}.php
  Resources/        {Modulo}Resource.php, {Modulo}IndexResource.php
  Enums/            Status{Modulo}.php
  (opcionais)       Filters/, Observers/, Constants/, Policies via app/Policies
```

Controllers de API publica ficam fora do modulo, em `SDC/app/Http/Controllers/Api/V1/`.

## Convencoes reais (seguir sempre)

- `declare(strict_types=1);` no topo de todo arquivo PHP do modulo.
- Namespace `App\Modules\{Modulo}\...`.
- Service injetado no Controller como `private readonly`.
- Autorizacao no Controller via `$this->authorize('acao', Model::class)` (Policies) e
  checagem de permissao com `$request->user()?->can('permissao.slug')`.
- Sem emojis e sem logs permanentes no codigo (logs so para teste, remover depois).

## Boilerplate fiel ao codigo

### DTO

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\DTOs;

class CisternaDTO
{
    public function __construct(
        public int $municipioId,
        public string $nome,
        public string $tipo,
        public string $status = 'pendente',
        public ?string $observacoes = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromRequest(array $data): self
    {
        return new self(
            municipioId: (int) ($data['municipio_id'] ?? 0),
            nome: (string) ($data['nome'] ?? ''),
            tipo: (string) ($data['tipo'] ?? 'comunitaria'),
            status: (string) ($data['status'] ?? 'pendente'),
            observacoes: $data['observacoes'] ?? null,
        );
    }
}
```

### Service

```php
<?php

declare(strict_types=1);

namespace App\Modules\Cisterna\Services;

use App\Modules\Cisterna\DTOs\CisternaDTO;
use App\Modules\Cisterna\Models\Cisterna;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CisternaService
{
    /** @param array<string, mixed> $filtros */
    public function listar(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        return Cisterna::query()
            ->with(['municipio:id,nome,uf']) // eager load: evita N+1
            ->when($filtros['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filtros['search'] ?? null, fn ($q, $t) => $q->buscarPorTermo($t))
            ->orderBy('nome')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function criar(CisternaDTO $dto): Cisterna
    {
        return DB::transaction(fn (): Cisterna => Cisterna::create([
            'municipio_id' => $dto->municipioId,
            'nome' => $dto->nome,
            'tipo' => $dto->tipo,
            'status' => $dto->status,
            'observacoes' => $dto->observacoes,
        ]));
    }
}
```

### Controller (thin, web/Inertia)

```php
public function __construct(private readonly CisternaService $service) {}

public function index(Request $request): Response
{
    $this->authorize('viewAny', Cisterna::class);
    $filtros = $request->only(['status', 'tipo', 'search']);

    return Inertia::render('Cisterna/Index', [
        'cisternas' => CisternaIndexResource::collection(
            $this->service->listar((int) $request->integer('per_page', 15), $filtros)
        ),
        'filters' => $filtros,
        'canManage' => $request->user()?->can('cisternas.create') ?? false,
    ]);
}

public function store(StoreCisternaRequest $request): RedirectResponse
{
    $this->authorize('create', Cisterna::class);
    $this->service->criar(CisternaDTO::fromRequest($request->validated()));

    return back();
}
```

## Erros comuns

| Erro | Correcao |
|---|---|
| Criar UseCase/Repository "porque e DDD" | Este projeto nao usa. Logica vai no Service; persistencia no Model. |
| Assumir MySQL | Banco e PostgreSQL (`pgsql`). Ver skill `database`. |
| N+1 ao listar relacoes | Use `->with([...])` no Service, nunca acessar relacao dentro de loop. |
| Logica de negocio no Controller | Controller so monta DTO e delega ao Service. |
| Esquecer `declare(strict_types=1)` ou namespace `App\Modules` | Quebra o padrao do modulo. |
| Validar no Controller | Validacao vai no FormRequest (`rules()` + `authorize()`). |

## Comandos uteis

```bash
cd SDC
php artisan make:request Cisterna/StoreCisternaRequest
php artisan make:model Cisterna
php artisan test --filter=Cisterna
./vendor/bin/pint app/Modules/Cisterna   # estilo
php artisan tinker                        # debug interativo
```
