# Deep Dive: Documentação da API com Swagger (l5-swagger)

Este documento é um guia técnico detalhado sobre como a documentação da API do NewSDC é gerada, estruturada e mantida usando o pacote `darkaonline/l5-swagger` e anotações OpenAPI.

---

## 1. A Fundação: `config/l5-swagger.php`

Tudo começa no arquivo de configuração. Ele dita o comportamento do gerador de documentação.

- **`paths.annotations`**: A diretiva mais importante.
    ```php
    'annotations' => [
        base_path('app/Http/Controllers/Api'),
    ],
    ```
    Isso instrui o `l5-swagger` a escanear **apenas** os arquivos PHP dentro do diretório `app/Http/Controllers/Api/` em busca de anotações OpenAPI (`@OA\...`).

- **`routes.api`**: Define a URL onde a UI do Swagger será servida.
    ```php
    'routes' => [
        'api' => 'api/documentation',
    ],
    ```
    A documentação interativa pode ser acessada em `[SUA_URL]/api/documentation`.

- **`securityDefinitions`**: Mantido VAZIO. Os security schemes sao definidos via anotacoes `@OA\SecurityScheme` em `SwaggerController.php`, em uma unica fonte de verdade. Ver o padrao oficial na secao 5.

---

## 2. Definições Globais: `Api/SwaggerController.php`

Em vez de poluir os controllers reais, as definições globais da API estão inteligentemente centralizadas em um controller "dummy": `app/Http/Controllers/Api/SwaggerController.php`.

#### **`@OA\Info`**
Define os metadados principais da API: título, versão, descrição.

```php
/**
 * @OA\Info(
 *     title="SDC - Sistema de Defesa Civil API",
 *     version="1.0.0",
 *     description="API RESTful escalável para 100k+ usuários simultâneos.",
 *     // ...
 * )
 */
```

#### **`@OA\Server`**
Define os diferentes ambientes onde a API pode ser acessada, permitindo que o desenvolvedor teste a documentação contra o servidor local, de desenvolvimento ou de produção.

```php
 * @OA\Server(
 *     url="https://sdcdefesa.azurewebsites.net",
 *     description="Servidor de Producao (Azure App Service)"
 * )
```

#### **`@OA\Schema`**
Este é um dos recursos mais poderosos em uso. O `SwaggerController.php` define **schemas reutilizáveis** para objetos de dados e formatos de resposta comuns.

- **Exemplo de Objeto de Dados (`ProcessoDecretacaoItem`):**
    ```php
    * @OA\Schema(
    *     schema="ProcessoDecretacaoItem",
    *     type="object",
    *     title="Processo de Decretacao (formato plano)",
    *     @OA\Property(property="id", type="integer", example=261),
    *     @OA\Property(property="municipio", type="string", nullable=true, example="Ouro Verde de Minas"),
    *     // ... mais propriedades
    * )
    ```
- **Exemplo de Resposta Paginada (`PaginatedResponse`):**
    ```php
    * @OA\Schema(
    *     schema="PaginatedResponse",
    *     type="object",
    *     @OA\Property(property="data", type="array", @OA\Items(type="object")),
    *     @OA\Property(property="meta", type="object", /* ... */),
    *     @OA\Property(property="links", type="object", /* ... */)
    * )
    ```
**Vantagem:** Em vez de redefinir a estrutura de um processo ou de uma resposta paginada em cada endpoint, os desenvolvedores podem simplesmente referenciar esses schemas com `ref="#/components/schemas/SchemaName"`, economizando código e garantindo consistência.

---

## 3. Na Prática: Documentando um Endpoint

O controller `Api/V1/Decretacoes/DecretacoesApiController.php` serve como um exemplo perfeito.

```php
// DecretacoesApiController.php

/**
 * @OA\Get(
 *     path="/api/v1/decretacoes",
 *     summary="Lista processos de decretacoes",
 *     operationId="decretacoesIndex",
 *     tags={"Decretacoes"},
 *     security={{"bearerAuth": {}}}, // Aplica a segurança Bearer Token
 *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
 *     @OA\Response(
 *         response=200,
 *         description="Lista paginada de processos",
 *         @OA\JsonContent(ref="#/components/schemas/ProcessoDecretacaoList") // Reutiliza um schema!
 *     ),
 *     @OA\Response(response=401, description="Nao autenticado")
 * )
 */
public function index(Request $request): JsonResponse
{
    // ...
}
```

### Anatomia da Anotação de Endpoint:
- **`@OA\Get(...)`**: Define o método HTTP e o endpoint.
- **`summary`**: Um título curto para o endpoint.
- **`tags`**: Agrupa endpoints relacionados na UI do Swagger.
- **`security`**: Aplica um ou mais esquemas de segurança definidos globalmente. `bearerAuth` foi definido no `SwaggerController`.
- **`@OA\Parameter`**: Documenta um parâmetro de entrada (seja na `query`, `path`, `header`, ou `cookie`).
- **`@OA\Response`**: Documenta um possível código de status de resposta. O uso de `@OA\JsonContent(ref=...)` permite reutilizar os schemas globais para descrever o corpo da resposta, garantindo que a documentação e a API real permaneçam sincronizadas.
- **`@OA\RequestBody`**: (Visto no método `receive`) Documenta o corpo esperado para requisições `POST` ou `PUT`, também permitindo a reutilização de schemas.

---

## 4. 🔄 Fluxo de Trabalho do Desenvolvedor

Para adicionar ou atualizar a documentação de um endpoint, o processo é:

1.  **Escrever a Anotação:** Adicionar ou modificar o bloco de comentário `@OA\...` acima do método do controller correspondente em `app/Http/Controllers/Api/`.
2.  **Gerar a Documentação:** Executar o comando Artisan para que o `l5-swagger` escaneie novamente os arquivos e gere o arquivo `api-docs.json` atualizado.
    ```bash
    # Navegue até o diretório SDC
    cd SDC

    # Execute o comando
    php artisan l5-swagger:generate
    ```
    *(Nota: Seu ambiente de CLI precisa usar PHP 8.1+ para o Artisan funcionar corretamente).*
3.  **Verificar:** Abrir o navegador em `[SUA_URL]/api/documentation` para ver as alterações refletidas na UI interativa do Swagger.

---

## 5. PADRAO OFICIAL DE AUTENTICACAO DA API (obrigatorio)

Toda anotacao OpenAPI de endpoint protegido DEVE usar um destes dois schemes. Eles
sao definidos UMA unica vez em `app/Http/Controllers/Api/SwaggerController.php` via
`@OA\SecurityScheme`. NUNCA reintroduzir o scheme `sanctum` (era duplicado e declarava
`bearerFormat: JWT`, o que e incorreto — tokens Sanctum NAO sao JWT).

### 5.1 `bearerAuth` (Sanctum personal access token)

- Tipo: `http`, scheme `bearer` (sem `bearerFormat`).
- Token gerado em `/admin/permissions/users` (admin com permissao `users.edit`),
  via `$user->createToken()->plainTextToken`. Formato `{id}|{40-chars}` (ex: `1|wV8...`).
- Enviar em `Authorization: Bearer {id}|{token}`.
- Use em TODO endpoint protegido por `auth:sanctum`.

```php
*     security={{"bearerAuth": {}}},
```

### 5.2 X-PowerBI-Token APOSENTADO — padrao e Sanctum Bearer

Decisao do dono: **toda a API de dados padroniza em Sanctum Bearer**. O caminho legado
`X-PowerBI-Token` foi **removido do `DecretacoesApiAuth`** — agora o middleware aceita
apenas **sessao web (frontend logado)** OU **token pessoal Sanctum (Bearer)**. Um
`X-PowerBI-Token` agora retorna 401 na API de dados (decretacoes/rat/tdap).

Motivo: o Sanctum Bearer e DB-backed, vinculado a um usuario e **revogavel** em
`/admin/permissions/users`; o token PowerBI vivia em cache Redis (TTL ~1h, sem revogacao
nem auditoria). NUNCA reintroduzir `X-PowerBI-Token`/`powerBiToken` no auth ou no Swagger
sem decisao explicita.

Obs.: os endpoints `/api/v1/power-bi/*` (geracao de token, proxy) e o
`IntegrationTokenService` ainda existem como feature separada, mas NAO autenticam mais a
API de dados. Avaliar deprecacao em separado.

### 5.3 `security` na anotacao + autorizacao

| Grupo de rota (routes/api.php) | Middleware | Security na anotacao | Permissao (`can:`) |
|---|---|---|---|
| `prefix('v1')` geral (PAE, etc.) | `auth:sanctum` | `{{"bearerAuth": {}}}` | sim, por rota (ex.: `pae.empreendimentos.view`) |
| `prefix('v1/decretacoes')`, `v1/rat` (protocolos), `v1/tdap` | `decretacoes.api.auth` | `{{"bearerAuth": {}}}` | **NAO** — decisao: token valido (emitido por admin) ja basta |
| `webhooks/receive` | publico + HMAC | sem `security` | assinatura `X-Webhook-Signature` (HMAC) |
| rotas publicas (login, health) | nenhum | sem `security` | - |

Decisao de autorizacao para a API de token (decretacoes/rat/tdap): **sem `can:`**. A posse
de um token Sanctum valido (cuja emissao e controlada por admin com o modulo de
Permissionamento) ja e a autorizacao. NAO readicionar `can:` nessas rotas sem decisao.

### 5.4 Cenario Power BI (fornecer + receber)

- **Fornecer (export):** `GET /api/v1/decretacoes/export/power-bi` (datasets pequenos)
  ou `GET /api/v1/decretacoes/export/power-bi/async` + polling em
  `GET /api/v1/decretacoes/traces/{traceId}` (datasets grandes — recomendado).
- **Receber (ingestao FIDE/Hexagon):** `POST /api/v1/decretacoes/receive`.
- Auth: Bearer (token pessoal Sanctum) no header `Authorization`. O caminho
  `X-PowerBI-Token` existe no backend mas nao e documentado (ver 5.2).

### 5.5 Envelope de resposta

- Modulo PAE: usa Laravel API Resources -> respostas com `data` (+ `meta`/`links` em listagens).
- Modulo Decretacoes: usa envelope manual `{ "success": true, "data": ... }`.
  - `index` -> `ProcessoDecretacaoList` (`data.data[]` flat + `data.meta`).
  - `show` -> `ProcessoDecretacaoDetail` (`data` rico/aninhado do `ProcessoResource`).
  - `receive` / `export` -> formato flat `ProcessoDecretacaoItem`.
