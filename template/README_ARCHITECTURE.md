# SDC - Arquitetura SOLID

## Visão Geral

Este projeto segue os princípios SOLID e Domain-Driven Design (DDD) para garantir código limpo, manutenível e escalável.

## Estrutura de Camadas

### 1. Domain (Domínio)
**Localização:** `app/Domain/`

Contém a lógica de negócio pura, independente de frameworks e tecnologias.

```
app/Domain/{Entity}/
├── Models/          # Modelos Eloquent representando entidades
├── Enums/           # Enumerações do domínio
├── ValueObjects/    # Objetos de valor imutáveis
└── Events/          # Eventos de domínio
```

**Responsabilidades:**
- Definir regras de negócio
- Manter integridade dos dados
- Emitir eventos de domínio

**Exemplo:**
```php
// app/Domain/User/Models/User.php
// app/Domain/User/Enums/UserStatus.php
// app/Domain/User/ValueObjects/Email.php
// app/Domain/User/Events/UserCreated.php
```

### 2. Application (Aplicação)
**Localização:** `app/Application/`

Orquestra casos de uso e coordena o fluxo de dados.

```
app/Application/{Entity}/
├── Actions/     # Actions/Commands (operações que modificam estado)
├── Queries/     # Queries (operações de leitura)
└── DTOs/        # Data Transfer Objects
```

**Responsabilidades:**
- Implementar casos de uso
- Coordenar transações
- Validar dados de entrada

**Exemplo:**
```php
// app/Application/User/Actions/CreateUserAction.php
// app/Application/User/Queries/GetUserQuery.php
// app/Application/User/DTOs/CreateUserDTO.php
```

### 3. Infrastructure (Infraestrutura)
**Localização:** `app/Infrastructure/`

Implementações técnicas e integrações externas.

```
app/Infrastructure/
├── Persistence/
│   └── Repositories/    # Implementações de repositórios
├── Services/            # Serviços externos (APIs, etc)
└── Cache/              # Implementações de cache
```

**Responsabilidades:**
- Acesso a banco de dados
- Integrações com APIs externas
- Cache e otimizações

**Exemplo:**
```php
// app/Infrastructure/Persistence/Repositories/UserRepository.php
// app/Infrastructure/Services/EmailService.php
// app/Infrastructure/Cache/UserCacheRepository.php
```

### 4. Presentation (Apresentação)
**Localização:** `app/Presentation/`

Camada de interface com o mundo externo.

```
app/Presentation/
├── Http/
│   ├── Controllers/     # Controllers HTTP
│   ├── Requests/        # Form Requests
│   ├── Resources/       # API Resources
│   └── Middleware/      # Middlewares
└── Console/
    └── Commands/        # Comandos Artisan
```

**Responsabilidades:**
- Receber requisições HTTP
- Validar entrada de dados
- Formatar respostas
- Executar comandos CLI

**Exemplo:**
```php
// app/Presentation/Http/Controllers/UserController.php
// app/Presentation/Http/Requests/CreateUserRequest.php
// app/Presentation/Http/Resources/UserResource.php
```

## Fluxo de Dados

```
Request → Controller → Action/Query → Repository → Model
                ↓
            Response
```

1. **Controller** recebe a requisição
2. **Request** valida os dados
3. **Action/Query** executa a lógica de negócio
4. **Repository** acessa os dados
5. **Model** representa a entidade
6. **Resource** formata a resposta

## Princípios SOLID Aplicados

### Single Responsibility Principle (SRP)
Cada classe tem uma única responsabilidade bem definida.

### Open/Closed Principle (OCP)
Código aberto para extensão, fechado para modificação.

### Liskov Substitution Principle (LSP)
Implementações podem ser substituídas por suas abstrações.

### Interface Segregation Principle (ISP)
Interfaces específicas para cada cliente.

### Dependency Inversion Principle (DIP)
Dependência de abstrações, não de implementações concretas.

## Exemplos de Uso

### Criar um novo recurso

1. **Criar o Model (Domain)**
```php
// app/Domain/Product/Models/Product.php
namespace App\Domain\Product\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'price', 'description'];
}
```

2. **Criar o DTO (Application)**
```php
// app/Application/Product/DTOs/CreateProductDTO.php
namespace App\Application\Product\DTOs;

class CreateProductDTO
{
    public function __construct(
        public string $name,
        public float $price,
        public string $description
    ) {}
}
```

3. **Criar a Action (Application)**
```php
// app/Application/Product/Actions/CreateProductAction.php
namespace App\Application\Product\Actions;

use App\Domain\Product\Models\Product;
use App\Application\Product\DTOs\CreateProductDTO;

class CreateProductAction
{
    public function execute(CreateProductDTO $dto): Product
    {
        return Product::create([
            'name' => $dto->name,
            'price' => $dto->price,
            'description' => $dto->description,
        ]);
    }
}
```

4. **Criar o Controller (Presentation)**
```php
// app/Presentation/Http/Controllers/ProductController.php
namespace App\Presentation\Http\Controllers;

use App\Application\Product\Actions\CreateProductAction;
use App\Application\Product\DTOs\CreateProductDTO;
use App\Presentation\Http\Requests\CreateProductRequest;

class ProductController extends Controller
{
    public function store(
        CreateProductRequest $request,
        CreateProductAction $action
    ) {
        $product = $action->execute(
            new CreateProductDTO(
                name: $request->name,
                price: $request->price,
                description: $request->description
            )
        );

        return response()->json($product, 201);
    }
}
```

## Boas Práticas

1. **Sempre use DTOs** para transferir dados entre camadas
2. **Actions devem ser verbais**: CreateUser, UpdateProduct, DeleteOrder
3. **Queries devem retornar dados**: GetUser, ListProducts, FindOrder
4. **Models apenas no Domain**: não espalhe lógica de negócio em controllers
5. **Repositories para abstração de dados**: facilita testes e manutenção
6. **Use Type Hints**: sempre tipifique parâmetros e retornos
7. **Dependency Injection**: injete dependências via construtor

## Testes

Estrutura de testes deve seguir a mesma organização:

```
tests/
├── Unit/
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
└── Feature/
    └── Presentation/
```

## Recursos Adicionais

- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
- [Domain-Driven Design](https://martinfowler.com/bliki/DomainDrivenDesign.html)
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
