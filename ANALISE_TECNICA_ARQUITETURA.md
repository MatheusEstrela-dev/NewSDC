# Análise Técnica Aprofundada da Arquitetura NewSDC

Este documento fornece um mergulho técnico na arquitetura do projeto NewSDC, detalhando a implementação dos padrões de design e oferecendo recomendações técnicas específicas para evolução e maturidade.

---

## P - Posição Atual: Análise Técnica Detalhada

A base do projeto é exemplar, combinando DDD no backend com Atomic Design no frontend. Abaixo, detalhamos a função e interação de cada componente.

### 1. **Backend: Domain-Driven Design em Camadas**

A estrutura modular com camadas bem definidas é o pilar da manutenibilidade do backend.

#### **1.1. Presentation Layer**
Esta camada é a porta de entrada para todas as interações externas.

-   **Controllers (`App\Modules\*\Presentation\Http\Controllers`)**: Atuam como orquestradores finos. Sua única responsabilidade é receber a requisição HTTP, extrair os dados e delegar a execução para a camada de aplicação. Eles não contêm lógica de negócio.
    ```php
    // Exemplo de um método de Controller
    public function store(CreateTaskRequest $request, CreateTaskUseCase $useCase): RedirectResponse
    {
        // 1. A validação já foi feita pelo CreateTaskRequest
        $dto = CreateTaskDTO::fromRequest($request);

        // 2. Delega para a camada de aplicação
        $task = $useCase->execute($dto, $request->user());

        // 3. Retorna a resposta (neste caso, para o Inertia)
        return redirect()->route('demandas.show', $task->id)
            ->with('success', 'Demanda criada com sucesso.');
    }
    ```
-   **Form Requests (`App\Modules\*\Presentation\Http\Requests`)**: Responsáveis pela validação e autorização das requisições. Isso remove a complexidade de validação dos controllers, tornando-os mais limpos e focados.
-   **API Resources (`App\Modules\*\Presentation\Http\Resources`)**: Transformam os modelos do Eloquent (Entidades do Domínio) em uma representação JSON customizada. Crucial para desacoplar a estrutura do banco de dados da resposta enviada ao frontend, permitindo formatação, adição de dados condicionais e controle sobre a exposição dos dados.
-   **Middleware**: Filtram as requisições HTTP para tarefas como autenticação (`auth`, `sanctum`) e verificação de permissões (`can:demandas.manage`), protegendo as rotas de forma declarativa.

#### **1.2. Application Layer**
O coração da lógica de aplicação (orquestração), livre de frameworks.

-   **Use Cases (`App\Modules\*\Application\UseCases`)**: Representam uma ação específica que o sistema pode realizar (ex: `CreateTaskUseCase`, `AssignAgentToTaskUseCase`). Eles orquestram o fluxo: recebem um DTO, utilizam os repositórios para buscar ou persistir entidades de domínio e executam a lógica de negócio (seja neles mesmos ou através de Serviços de Domínio).
-   **Data Transfer Objects (DTOs) (`App\Modules\*\Application\DTOs`)**: Objetos simples e imutáveis que carregam dados entre as camadas, primariamente da Presentation para a Application. Eles garantem um contrato de dados claro e desacoplam os Use Cases dos detalhes da requisição HTTP.

#### **1.3. Domain Layer**
O núcleo do software, contendo a lógica de negócio mais pura e crítica.

-   **Entities (`App\Modules\*\Domain\Entities`)**: São os modelos do Eloquent, mas enriquecidos com lógica de negócio. Em vez de serem apenas estruturas de dados, eles contêm métodos que garantem sua consistência e regras (ex: um método `Task::close()` que verifica as condições antes de alterar o status).
-   **Value Objects (`App\Modules\*\Domain\ValueObjects`)**: Objetos imutáveis que representam um conceito do domínio (ex: `TaskStatus`, `Prioridade`). Eles encapsulam a validação e a lógica associada a esses valores, eliminando a necessidade de "strings mágicas" e garantindo a validade dos dados em nível de domínio.
-   **Repository Interfaces (`App\Modules\*\Domain\Repositories`)**: Contratos que definem os métodos de persistência necessários para as entidades (ex: `TaskRepositoryInterface` com métodos `findById`, `save`, `findByStatus`). Essa abstração é fundamental para o DDD, pois permite que o domínio seja independente da tecnologia de banco de dados.
-   **Domain Services (`App\Modules\*\Domain\Services`)**: Usados para lógica de negócio que não pertence naturalmente a uma única entidade (ex: um serviço que calcula o SLA de uma `Task` com base em sua `Prioridade` e `Tipo`).

#### **1.4. Infrastructure Layer**
A implementação concreta das abstrações definidas no domínio.

-   **Repository Implementations (`App\Modules\*\Infrastructure\Repositories`)**: Classes que implementam as interfaces de repositório usando o Eloquent ORM. É aqui que a "tradução" do domínio para o mundo do MySQL acontece.
-   **Outros Serviços**: Esta camada também abriga implementações de gateways para serviços externos (ex: envio de e-mails, notificações), implementações de cache (Redis), etc.

### 2. **Frontend: Atomic Design & Inertia.js**

A arquitetura do frontend é igualmente disciplinada, promovendo reutilização e manutenibilidade.

-   **Inertia.js**: Atua como a cola inteligente entre o backend Laravel e o frontend Vue. Ele permite que o Laravel controle o roteamento e passe dados diretamente para os componentes Vue como `props`, eliminando a necessidade de uma API REST completa para a aplicação principal, ao mesmo tempo que oferece uma experiência de SPA.
-   **Páginas (`resources/js/Pages`)**: Componentes Vue que correspondem a uma rota. Recebem os `props` do controller Laravel e os distribuem para os componentes filhos.
-   **Templates (`resources/js/Layouts`)**: Estruturas de layout reutilizáveis (ex: `AuthenticatedLayout.vue`) que contêm elementos persistentes como a barra de navegação e o sidebar, utilizando o conceito de "layouts persistentes" do Inertia.
-   **Organismos (`resources/js/Components/Organisms`)**: Componentes complexos e autocontidos que formam uma seção distinta da interface (ex: `TaskTable.vue`, `Sidebar.vue`). Eles compõem moléculas e átomos e gerenciam seu próprio estado, quando necessário.
-   **Moléculas (`resources/js/Components/Molecules`)**: Combinações de átomos que funcionam como uma unidade (ex: um campo de busca composto por um `Input` e um `Button`).
-   **Átomos (`resources/js/Components/Atoms`)**: Os blocos de construção fundamentais da UI (ex: `Button.vue`, `Input.vue`, `Badge.vue`). São componentes genéricos e altamente reutilizáveis, estilizados com Tailwind CSS.

#### **2.1. Mapeamento da Estrutura Frontend para os Módulos DDD (Exemplo Prático)**

A organização de pastas no frontend espelha a arquitetura modular do backend, criando um acoplamento coeso entre o domínio e sua representação visual. Esta estrutura garante que a complexidade relacionada a um módulo de negócio específico seja contida, ao mesmo tempo que promove a reutilização de componentes genéricos.

```
resources/js/
├── Pages/
│   ├── Rat/                 <-- Módulo do DDD
│   │   ├── Create.vue       (Página que usa o Template do Layout)
│   │   ├── Index.vue
│   │   └── Partials/        <-- Organismos Específicos do Módulo
│   │       ├── RatForm.vue  (Organismo que consome Moléculas/Átomos globais)
│   │       └── RatList.vue
│   ├── Pae/                 <-- Outro Módulo DDD
│   │   └── ...
│   └── Profile/             <-- Páginas não atreladas a um módulo de negócio
│
├── Components/
│   ├── Atoms/
│   │   ├── Button.vue
│   │   └── Input.vue
│   ├── Molecules/
│   │   └── FormGroup.vue      (Ex: Label + Input + Erro)
│   └── Organisms/           <-- Organismos Globais/Reutilizáveis
│       └── DataTable.vue
│
└── Layouts/
    └── AuthenticatedLayout.vue
```

-   **`Pages/Rat/`**: Contém as páginas de topo do Inertia para o módulo "Rat". Elas compõem a estrutura geral da página usando templates e organismos.
-   **`Pages/Rat/Partials/`**: Uma convenção útil para armazenar componentes **Organismo** que são específicos e complexos demais para serem genéricos, mas que são parte integral das páginas daquele módulo. `RatForm.vue` é um exemplo perfeito: ele é um organismo complexo, mas provavelmente só será usado dentro do contexto de "Rat".
-   Esta estrutura mantém a modularidade, facilitando encontrar e gerenciar os componentes relacionados a um domínio de negócio específico, enquanto os componentes verdadeiramente genéricos e reutilizáveis permanecem em `resources/js/Components/`.

---

## R - Recomendações Técnicas Aprofundadas

### 1. **Estratégia de Testes Automatizados (Técnico)**

#### **1.1. Testes de Unidade (Pest/PHPUnit)**
-   **Foco**: Lógica de negócio pura, sem I/O (rede, banco de dados).
-   **Alvos**: `Value Objects`, `Domain Services`, `Use Cases`.
-   **Exemplo (Pest - Testando um Use Case)**:
    ```php
    // tests/Unit/Demandas/Application/UseCases/CloseTaskUseCaseTest.php
    use App\Modules\Demandas\Domain\Repositories\TaskRepositoryInterface;
    use App\Modules\Demandas\Domain\Entities\Task;

    it('should close an open task successfully', function () {
        // Arrange
        $task = Task::factory()->make(['status' => 'open']); // Usando factory para criar uma entidade em memória
        $repositoryMock = Mockery::mock(TaskRepositoryInterface::class);
        $repositoryMock->shouldReceive('findById')->once()->andReturn($task);
        $repositoryMock->shouldReceive('save')->once()->withArgs(function (Task $savedTask) {
            return $savedTask->status === 'closed'; // Verifica se o status foi alterado
        });

        $useCase = new CloseTaskUseCase($repositoryMock);

        // Act
        $useCase->execute($task->id);

        // Assert (implícito pelo mock, mas pode ter asserções explícitas)
        expect($task->status)->toBe('closed');
    });
    ```

#### **1.2. Testes de Integração (Laravel/Pest)**
-   **Foco**: Interação entre camadas, principalmente com o banco de dados.
-   **Alvos**: Implementações de `Repository`, `Controllers`.
-   **Exemplo (Pest - Testando uma Rota/Controller)**:
    ```php
    // tests/Feature/Demandas/CreateTaskTest.php
    use Illuminate\Foundation\Testing\RefreshDatabase;
    use App\Models\User;

    uses(RefreshDatabase::class);

    it('allows an authenticated user to create a task', function () {
        // Arrange
        $user = User::factory()->create();
        $taskData = ['titulo' => 'Nova Demanda de Teste', 'descricao' => 'Detalhes...'];

        // Act & Assert
        $this->actingAs($user)
             ->post(route('demandas.store'), $taskData)
             ->assertRedirect(route('demandas.show', 1)); // ou o ID esperado

        $this->assertDatabaseHas('tasks', [
            'titulo' => 'Nova Demanda de Teste',
            'user_id' => $user->id
        ]);
    });
    ```

#### **1.3. Testes End-to-End (Playwright)**
-   **Foco**: Fluxo completo do usuário no navegador.
-   **Alvos**: User journeys críticos (login, criação de demanda, etc.).
-   **Exemplo Conceitual (Playwright - `*.spec.js`)**:
    ```javascript
    test('User can create a new task', async ({ page }) => {
      // Arrange: Login
      await page.goto('/login');
      await page.fill('input[name="email"]', 'user@example.com');
      await page.fill('input[name="password"]', 'password');
      await page.click('button[type="submit"]');
      await page.waitForURL('/dashboard');

      // Act
      await page.click('a:has-text("Nova Demanda")');
      await page.waitForURL('/demandas/nova');
      await page.fill('input[name="titulo"]', 'Minha tarefa E2E');
      await page.fill('textarea[name="descricao"]', 'Descrição completa da tarefa.');
      await page.click('button:has-text("Salvar")');

      // Assert
      await page.waitForURL('/demandas/**');
      const heading = await page.textContent('h1');
      expect(heading).toContain('Minha tarefa E2E');
    });
    ```

### 2. **Pipeline de CI/CD Otimizado (GitHub Actions)**

-   **Arquivo de Workflow**: `.github/workflows/ci-cd.yml`
-   **Exemplo de Configuração**:

    ```yaml
    name: Laravel CI/CD

    on:
      push:
        branches: [ main, dev ]
      pull_request:
        branches: [ main, dev ]

    jobs:
      build-and-test:
        runs-on: ubuntu-latest
        steps:
          - uses: actions/checkout@v3

          - name: Setup PHP
            uses: shivammathur/setup-php@v2
            with:
              php-version: '8.3'
              extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite
              coverage: none

          - name: Get Composer Cache Directory
            id: composer-cache
            run: echo "::set-output name=dir::$(composer config cache-files-dir)"
          - uses: actions/cache@v3
            with:
              path: ${{ steps.composer-cache.outputs.dir }}
              key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
              restore-keys: ${{ runner.os }}-composer-

          - name: Install Composer Dependencies
            run: composer install --prefer-dist --no-progress

          - name: Install NPM Dependencies
            run: npm install

          - name: Build Frontend Assets
            run: npm run build

          - name: Run Static Analysis (Pint & PHPStan)
            run: |
              ./vendor/bin/pint --test
              ./vendor/bin/phpstan analyse --memory-limit=2G

          - name: Run Tests
            env:
              DB_CONNECTION: sqlite
              DB_DATABASE: ":memory:"
            run: php artisan test

      deploy-to-production:
        needs: build-and-test
        if: github.ref == 'refs/heads/main' && github.event_name == 'push'
        runs-on: ubuntu-latest
        steps:
          # Passos para buildar e pushar a imagem Docker
          - name: Build and Push Docker image
            uses: docker/build-push-action@v2
            with:
              context: ./SDC
              push: true
              tags: your-registry/newsdc-app:latest

          # Passos para acionar o deploy no servidor
          - name: Trigger deployment
            uses: appleboy/ssh-action@master
            with:
              host: ${{ secrets.PROD_HOST }}
              username: ${{ secrets.PROD_USERNAME }}
              key: ${{ secrets.PROD_SSH_KEY }}
              script: 'cd /var/www/newsdc && ./deploy.sh' # Script que faz o pull da nova imagem e reinicia os containers
    ```

### 3. **Documentação de API com OpenAPI (l5-swagger)**

-   **Implementação**: Use anotações diretamente nos controllers.
-   **Exemplo de Anotação de Controller**:

    ```php
    // No seu BaseController ou em um arquivo de schemas
    /**
     * @OA\Info(title="NewSDC API", version="1.0")
     * @OA\Schema(
     *   schema="TaskResource",
     *   type="object",
     *   @OA\Property(property="id", type="integer"),
     *   @OA\Property(property="titulo", type="string"),
     *   @OA\Property(property="status", type="string", enum={"open", "in_progress", "closed"}),
     *   ...
     * )
     */

    // No seu TaskController
    class TaskController
    {
        /**
         * @OA\Get(
         *     path="/api/v1/demandas/{id}",
         *     summary="Retorna os detalhes de uma demanda",
         *     tags={"Demandas"},
         *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
         *     @OA\Response(
         *         response=200,
         *         description="Operação bem-sucedida",
         *         @OA\JsonContent(ref="#/components/schemas/TaskResource")
         *     ),
         *     @OA\Response(response=404, description="Demanda não encontrada")
         * )
         */
        public function show(Task $task)
        {
            return new TaskResource($task);
        }
    }
    ```

---

## O - Resultados Esperados (Impacto Técnico)

-   **Confiabilidade Contratual**: Testes de unidade no domínio funcionam como uma especificação executável das regras de negócio. Testes de integração garantem que os "contratos" entre as camadas (ex: `RepositoryInterface` vs. `EloquentRepository`) sejam cumpridos.
-   **Redução do MTTR (Mean Time To Recovery)**: Um pipeline de CI/CD que roda testes em cada commit detecta bugs minutos após serem introduzidos, em vez de horas ou dias depois em ambientes de staging/produção. Isso reduz drasticamente o tempo e o custo de correção.
-   **Escalabilidade Organizacional**: A documentação OpenAPI desacopla o trabalho das equipes de backend e frontend. A equipe de frontend pode desenvolver usando uma API mockada gerada a partir da especificação, enquanto a equipe de backend a implementa. Isso permite paralelismo real e facilita a integração de novos desenvolvedores.
-   **Manutenibilidade a Longo Prazo**: A combinação de DDD (domínio explícito), testes (segurança para refatoração) e automação (consistência) cria um sistema resiliente a mudanças, onde a complexidade é gerenciada e o débito técnico é mantido sob controle.
