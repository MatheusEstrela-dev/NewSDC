# Cheat Sheet - Backend Simplificado

Referência rápida: **Request → DTO → Controller → Service → Model**

---

## FormRequest (Validação)

```php
class CreateRatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'priority' => 'required|in:baixa,media,alta',
            'due_date' => 'required|date|after:today',
        ];
    }
}
```

---

## DTO (Dados Tipados)

```php
final readonly class CreateRatDTO
{
    public function __construct(
        public string $title,
        public string $priority,
        public string $due_date,
        public int $created_by_id,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            title: $data['title'],
            priority: $data['priority'],
            due_date: $data['due_date'],
            created_by_id: auth()->id(),
        );
    }
}
```

---

## Service (Lógica)

```php
class RatService
{
    public function create(CreateRatDTO $dto): Rat
    {
        // Validação de negócio
        if ($this->hasExceededQuota($dto->created_by_id)) {
            throw new \DomainException('Quota exceeded');
        }

        // Criar
        return Rat::create([
            'title' => $dto->title,
            'priority' => $dto->priority,
            'due_date' => $dto->due_date,
            'created_by_id' => $dto->created_by_id,
            'status' => 'aberta',
        ]);
    }

    public function update(Rat $rat, UpdateRatDTO $dto): Rat
    {
        if ($rat->status === 'completa') {
            throw new \DomainException('Cannot update completed');
        }

        $rat->update([
            'title' => $dto->title ?? $rat->title,
            'priority' => $dto->priority ?? $rat->priority,
        ]);

        return $rat;
    }

    public function list(array $filters = [])
    {
        $query = Rat::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate(15);
    }

    private function hasExceededQuota(int $userId): bool
    {
        return Rat::whereUserId($userId)
            ->whereMonth('created_at', now()->month)
            ->count() >= 50;
    }
}
```

---

## Controller (Thin)

```php
class RatController
{
    public function __construct(private RatService $service) {}

    public function index()
    {
        $rats = $this->service->list(request()->only(['status', 'priority']));
        return RatResource::collection($rats);
    }

    public function show(Rat $rat)
    {
        return RatResource::make($rat);
    }

    public function store(CreateRatRequest $request)
    {
        $dto = CreateRatDTO::from($request->validated());
        $rat = $this->service->create($dto);
        return RatResource::make($rat)->response()->setStatusCode(201);
    }

    public function update(UpdateRatRequest $request, Rat $rat)
    {
        $dto = UpdateRatDTO::from($request->validated());
        $updated = $this->service->update($rat, $dto);
        return RatResource::make($updated);
    }

    public function destroy(Rat $rat)
    {
        $rat->delete();
        return response()->noContent();
    }
}
```

---

## Model (Eloquent)

```php
class Rat extends Model
{
    protected $fillable = ['title', 'priority', 'status', 'due_date', 'created_by_id'];

    protected $casts = ['due_date' => 'date'];

    // Scopes
    public function scopeOpen($query) => $query->where('status', 'aberta');
    public function scopeCompleted($query) => $query->where('status', 'completa');

    // Métodos query
    public function canBeAssigned(): bool
    {
        return !in_array($this->status, ['completa', 'cancelada']);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && now()->isAfter($this->due_date);
    }
}
```

---

## Resource (Serialização)

```php
class RatResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'priority' => $this->priority,
            'status' => $this->status,
            'due_date' => $this->due_date?->toDateString(),
            'is_overdue' => $this->isOverdue(),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
```

---

## Testes

```php
class RatServiceTest extends TestCase
{
    private RatService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RatService::class);
    }

    public function test_creates_rat()
    {
        $dto = new CreateRatDTO(
            title: 'Test',
            priority: 'alta',
            due_date: now()->addDays(5)->toDateString(),
            created_by_id: 1,
        );

        $rat = $this->service->create($dto);

        $this->assertNotNull($rat->id);
        $this->assertDatabaseHas('rats', ['title' => 'Test']);
    }

    public function test_cannot_update_completed()
    {
        $rat = RatFactory::create(['status' => 'completa']);
        $this->expectException(DomainException::class);

        $this->service->update($rat, $dto);
    }
}
```

---

## Debugging Rápido

### N+1 Queries
```php
// ❌ Ruim
$rats = Rat::all();
foreach ($rats as $rat) {
    echo $rat->creator->name;  // Query por RAT!
}

// ✅ Bom
$rats = Rat::with('creator')->get();
```

### Exception Handling
```php
// Service lança exceção
throw new \DomainException('RAT cannot be assigned');

// Controller passa adiante
// Laravel retorna 422 JSON com mensagem
```

### Validação
```php
// FormRequest valida
// Se falhar, Laravel retorna 422:
{
    "message": "The given data was invalid.",
    "errors": {
        "title": ["Título obrigatório"]
    }
}
```

---

## Estrutura de Pasta

```
app/Modules/{Modulo}/
├── Http/
│   ├── Controllers/
│   │   └── XxxController.php
│   ├── Requests/
│   │   ├── CreateXxxRequest.php
│   │   └── UpdateXxxRequest.php
│   └── Resources/
│       └── XxxResource.php
├── Services/
│   └── XxxService.php
├── Models/
│   └── Xxx.php
└── DTOs/
    ├── CreateXxxDTO.php
    └── UpdateXxxDTO.php
```

---

## Fluxo HTTP Completo

```
POST /api/rats
├─ FormRequest valida
├─ DTO criado (from())
├─ Controller delega
├─ Service executa lógica
├─ Model persiste
├─ Resource serializa
└─ JSON Response 201
```

---

## Checklist de Implementação

- [ ] FormRequest com validação
- [ ] DTO com factory `from()`
- [ ] Service com métodos claros
- [ ] Model com scopes + query methods
- [ ] Controller thin
- [ ] Resource para serialização
- [ ] Migration criada
- [ ] Testes no Service
- [ ] Validações de negócio no Service
- [ ] Pronto!

---

## Comandos Úteis

```bash
# Criar estrutura
php artisan make:request Rat/CreateRatRequest
php artisan make:model Rat
php artisan make:controller Rat/RatController --resource

# Testar
php artisan test tests/Feature/RatServiceTest.php

# Debugar
php artisan tinker
> app(RatService::class)->create($dto)

# Logs
tail -f storage/logs/laravel.log
```

---

**Simples. Direto. Funciona.**
