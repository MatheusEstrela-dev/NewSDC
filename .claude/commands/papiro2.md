# Blueprint Arquitetural NewSDC
## Como Construir uma Nova Feature do Zero

Este documento é o manual de instruções oficial para o desenvolvimento de novas funcionalidades no projeto NewSDC. Ele serve como um guia passo a passo, garantindo que todas as novas implementações sigam os padrões de **Domain-Driven Design (DDD)** e **Atomic Design** estabelecidos.

**Cenário de Exemplo**: Vamos construir um novo módulo de "Gestão de Ativos".

---

### Passo 0: Planejamento e Modelagem do Domínio (5 minutos de pensamento evitam 5 horas de refatoração)

Antes de escrever qualquer código, defina o domínio.

1.  **Linguagem Ubíqua**: Quais são os termos de negócio?
    -   `Ativo`: Um item físico ou digital (computador, licença de software).
    -   `Status do Ativo`: `Em estoque`, `Alocado`, `Em manutenção`, `Baixado`.
    -   `Alocação`: O ato de associar um `Ativo` a um `Usuário`.

2.  **Artefatos do Domínio**:
    -   **Entidade Principal**: `Ativo`.
    -   **Objetos de Valor**: `StatusAtivo`.
    -   **Casos de Uso Principais**: "Criar novo ativo", "Alocar ativo para usuário", "Dar baixa em um ativo".

---

### Passo 1: O Alicerce - Estrutura de Pastas e Configuração

Crie o esqueleto do novo módulo e informe ao Laravel sobre sua existência.

1.  **Crie as Pastas do Módulo**:
    ```bash
    mkdir -p SDC/app/Modules/Ativos/{Application/DTOs,Application/UseCases,Domain/Entities,Domain/Repositories,Domain/ValueObjects,Infrastructure/Repositories,Presentation/Http/Controllers,Presentation/Http/Requests}
    ```

2.  **Crie o Service Provider**:
    -   `php artisan make:provider Modules/Ativos/AtivosServiceProvider`
    -   Este arquivo será o ponto de entrada do seu módulo.

3.  **Crie o Arquivo de Rotas**:
    -   Crie o arquivo `SDC/routes/modules/ativos.php`.

4.  **Registre o Módulo no Laravel**:
    -   Abra `config/app.php` e adicione seu novo provider:
        ```php
        'providers' => [
            // ... outros providers
            App\Modules\Ativos\AtivosServiceProvider::class, // <-- Adicionar aqui
        ],
        ```
    -   Abra `app/Providers/RouteServiceProvider.php` e registre as rotas do módulo dentro do método `boot`:
        ```php
        Route::middleware('web')
            ->group(base_path('routes/modules/ativos.php'));
        ```

---

### Passo 2: O Coração - Construindo a Camada de Domínio

Defina as regras de negócio e os contratos de persistência.

1.  **Crie a Migration**:
    -   `php artisan make:migration create_ativos_table`
    -   Defina o schema: `id`, `titulo`, `status`, `user_id` (para alocação), etc.

2.  **Crie a Entidade e os Value Objects**:
    -   **Entidade**: `app/Modules/Ativos/Domain/Entities/Ativo.php` (pode estender `Illuminate\Database\Eloquent\Model`).
    -   **Value Object**: Crie `app/Modules/Ativos/Domain/ValueObjects/StatusAtivo.php` como um Enum do PHP 8.1:
        ```php
        // Domain/ValueObjects/StatusAtivo.php
        enum StatusAtivo: string
        {
            case EM_ESTOQUE = 'em_estoque';
            case ALOCADO = 'alocado';
            case BAIXADO = 'baixado';
        }
        ```

3.  **Crie a Interface do Repositório**:
    -   Este é o **contrato**. Ele define o que a camada de domínio espera que a infraestrutura faça.
    -   Crie `app/Modules/Ativos/Domain/Repositories/AtivoRepositoryInterface.php`:
        ```php
        // Domain/Repositories/AtivoRepositoryInterface.php
        interface AtivoRepositoryInterface
        {
            public function findById(int $id): ?Ativo;
            public function save(Ativo $ativo): void;
        }
        ```

---

### Passo 3: O Trabalho Pesado - Implementando a Infraestrutura

Implemente os contratos do domínio usando as ferramentas do framework.

1.  **Crie a Implementação do Repositório**:
    -   Crie `app/Modules/Ativos/Infrastructure/Repositories/EloquentAtivoRepository.php`:
        ```php
        // Infrastructure/Repositories/EloquentAtivoRepository.php
        use App\Modules\Ativos\Domain\Entities\Ativo;
        use App\Modules\Ativos\Domain\Repositories\AtivoRepositoryInterface;

        class EloquentAtivoRepository implements AtivoRepositoryInterface
        {
            public function findById(int $id): ?Ativo {
                return Ativo::find($id);
            }
            public function save(Ativo $ativo): void {
                $ativo->save();
            }
        }
        ```

2.  **Vincule a Interface à Implementação (Binding)**:
    -   Este passo é **CRÍTICO**. Diga ao Laravel que, sempre que uma classe pedir por `AtivoRepositoryInterface`, ele deve entregar uma instância de `EloquentAtivoRepository`.
    -   No `AtivosServiceProvider.php`, dentro do método `register()`:
        ```php
        // Modules/Ativos/AtivosServiceProvider.php
        use App\Modules\Ativos\Domain\Repositories\AtivoRepositoryInterface;
        use App\Modules\Ativos\Infrastructure\Repositories\EloquentAtivoRepository;

        public function register(): void
        {
            $this->app->bind(
                AtivoRepositoryInterface::class,
                EloquentAtivoRepository::class
            );
        }
        ```

---

### Passo 4: O Orquestrador - Escrevendo a Lógica de Aplicação

Crie o `UseCase` que vai orquestrar a feature.

1.  **Crie o DTO (Data Transfer Object)**:
    -   `app/Modules/Ativos/Application/DTOs/CreateAtivoDTO.php`
    -   Será um objeto simples para carregar os dados da requisição.

2.  **Crie o Use Case**:
    -   `app/Modules/Ativos/Application/UseCases/CreateAtivoUseCase.php`
        ```php
        // Application/UseCases/CreateAtivoUseCase.php
        class CreateAtivoUseCase
        {
            public function __construct(private readonly AtivoRepositoryInterface $ativoRepository) {}

            public function execute(CreateAtivoDTO $dto): Ativo
            {
                $ativo = Ativo::new($dto->titulo, ...);
                $this->ativoRepository->save($ativo);
                return $ativo;
            }
        }
        ```

---

### Passo 5: A Vitrine - Expondo a Feature via HTTP

Crie o `Controller` e a `Rota` para que o mundo exterior possa usar a feature.

1.  **Crie o Form Request para Validação**:
    -   `php artisan make:request Modules/Ativos/Presentation/Http/Requests/CreateAtivoRequest`

2.  **Crie o Controller**:
    -   `php artisan make:controller Modules/Ativos/Presentation/Http/Controllers/AtivoController`
    -   Implemente o método `store`, injetando o `Request` e o `UseCase`.

3.  **Adicione a Rota**:
    -   Em `routes/modules/ativos.php`, adicione a rota:
        `Route::post('/ativos', [AtivoController::class, 'store'])->name('ativos.store');`

---

### Passo 6: A Experiência - Construindo o Frontend Atômico

Agora, crie a interface para o usuário.

1.  **Crie as Pastas da Página**:
    -   `mkdir -p SDC/resources/js/Pages/Ativos/Partials`

2.  **Crie o Organismo do Formulário**:
    -   Primeiro, verifique se você pode reutilizar Átomos (`Input`, `Button`) e Moléculas (`FormGroup`) globais de `resources/js/Components/`.
    -   Crie o formulário específico do domínio: `resources/js/Pages/Ativos/Partials/AtivoForm.vue`. Use o `useForm` do Inertia.

3.  **Crie a Página de Criação**:
    -   Crie a página `resources/js/Pages/Ativos/Create.vue`.
    -   Ela deve importar o layout principal (`AuthenticatedLayout.vue`) e o organismo que você acabou de criar (`AtivoForm.vue`).

4.  **Renderize a Página no Controller**:
    -   No `AtivoController`, crie um método `create()` que retorne a view Inertia:
        ```php
        public function create()
        {
            return Inertia::render('Ativos/Create');
        }
        ```
    -   Não se esqueça de adicionar a rota `GET /ativos/create` no seu arquivo de rotas.

---

### Checklist de Conclusão

Antes de finalizar, verifique:
-   [ ] O `ServiceProvider` do módulo está registrado em `config/app.php`?
-   [ ] O arquivo de rotas do módulo está sendo carregado no `RouteServiceProvider`?
-   [ ] A interface do repositório está vinculada (`bind`) à sua implementação concreta no `ServiceProvider` do módulo?
-   [ ] O `FormRequest` está fazendo a validação e autorização corretamente?
-   [ ] O Controller está recebendo o `UseCase` via injeção de dependência e delegando o trabalho?
-   [ ] O componente de página Vue está sendo renderizado e o formulário submete os dados para a rota correta?
-   [ ] **Você criou testes para isso?** (O próximo passo natural é criar testes de feature e unidade para garantir que tudo funcione).
