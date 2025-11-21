# Configuração do Swagger/OpenAPI - SDC

## Instalação

O pacote `darkaonline/l5-swagger` já foi instalado. Para configurar:

### 1. Publicar Configuração (se necessário)

```bash
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

### 2. Configurar Variáveis de Ambiente

Adicione ao arquivo `.env`:

```env
L5_SWAGGER_CONST_HOST=http://localhost
L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_UI_DARK_MODE=false
L5_SWAGGER_UI_DOC_EXPANSION=list
```

### 3. Gerar Documentação

```bash
php artisan l5-swagger:generate
```

### 4. Acessar Documentação

Acesse a documentação Swagger UI em:
- **URL**: `http://localhost/api/documentation`

## Estrutura de Documentação

### Controllers com Anotações Swagger

Os controllers estão localizados em:
- `app/Http/Controllers/Api/V1/Pae/EmpreendimentoController.php`
- `app/Http/Controllers/Api/V1/Rat/ProtocoloController.php`
- `app/Http/Controllers/Api/V1/Integracao/IntegracaoController.php`

### Exemplo de Anotação

```php
/**
 * @OA\Get(
 *     path="/api/v1/pae/empreendimentos",
 *     summary="Lista todos os empreendimentos PAE",
 *     tags={"PAE"},
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Lista de empreendimentos",
 *         @OA\JsonContent(...)
 *     )
 * )
 */
```

## Endpoints Documentados

### Módulo PAE
- `GET /api/v1/pae/empreendimentos` - Lista empreendimentos
- `GET /api/v1/pae/empreendimentos/{id}` - Detalhes do empreendimento
- `POST /api/v1/pae/empreendimentos` - Criar empreendimento
- `PUT /api/v1/pae/empreendimentos/{id}` - Atualizar empreendimento
- `DELETE /api/v1/pae/empreendimentos/{id}` - Remover empreendimento

### Módulo RAT
- `GET /api/v1/rat/protocolos` - Lista protocolos
- `GET /api/v1/rat/protocolos/{id}` - Detalhes do protocolo
- `POST /api/v1/rat/protocolos` - Criar protocolo

### Integração
- `GET /api/v1/integracao/rat/{ratId}/pae` - Buscar PAE por RAT
- `GET /api/v1/integracao/pae/{paeId}/rat` - Buscar RAT por PAE

## Autenticação

Todas as rotas requerem autenticação via Laravel Sanctum. No Swagger UI:

1. Clique no botão **"Authorize"** no topo da página
2. Digite: `Bearer {seu_token}`
3. Clique em **"Authorize"**

## Gerar Documentação Automaticamente

Para gerar documentação automaticamente a cada requisição (apenas em desenvolvimento):

```env
L5_SWAGGER_GENERATE_ALWAYS=true
```

Em produção, defina como `false` e gere manualmente quando necessário.

## Adicionar Novos Endpoints

Para documentar novos endpoints:

1. Adicione anotações `@OA\*` no controller
2. Execute: `php artisan l5-swagger:generate`
3. Acesse: `http://localhost/api/documentation`

## Exemplo Completo

```php
/**
 * @OA\Post(
 *     path="/api/v1/pae/empreendimentos",
 *     summary="Cria um novo empreendimento",
 *     tags={"PAE"},
 *     security={{"sanctum": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"nome", "tipo"},
 *             @OA\Property(property="nome", type="string", example="Barragem Sul Superior"),
 *             @OA\Property(property="tipo", type="string", example="Barragem de Rejeitos")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Empreendimento criado",
 *         @OA\JsonContent(ref="#/components/schemas/Empreendimento")
 *     )
 * )
 */
```

