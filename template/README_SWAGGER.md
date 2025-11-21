# Swagger/OpenAPI - Documentação da API SDC

## ✅ Implementação Completa

O Swagger foi implementado com sucesso para documentar e visualizar todos os endpoints da API REST do SDC.

## 📍 Acesso

Após iniciar o servidor, acesse a documentação em:

**URL**: `http://localhost/api/documentation`

## 🚀 Início Rápido

### 1. Gerar Documentação

```bash
php artisan l5-swagger:generate
```

### 2. Acessar Interface

Abra no navegador: `http://localhost/api/documentation`

### 3. Autenticar

1. Use o endpoint `POST /api/v1/auth/login` para obter um token
2. Clique em "Authorize" no topo da página
3. Digite: `Bearer {seu_token}`
4. Agora você pode testar todos os endpoints!

## 📚 Endpoints Documentados

### Autenticação
- ✅ `POST /api/v1/auth/login` - Login e obtenção de token
- ✅ `POST /api/v1/auth/logout` - Logout
- ✅ `GET /api/v1/auth/me` - Dados do usuário

### Módulo PAE
- ✅ `GET /api/v1/pae/empreendimentos` - Lista empreendimentos
- ✅ `GET /api/v1/pae/empreendimentos/{id}` - Detalhes
- ✅ `POST /api/v1/pae/empreendimentos` - Criar
- ✅ `PUT /api/v1/pae/empreendimentos/{id}` - Atualizar
- ✅ `DELETE /api/v1/pae/empreendimentos/{id}` - Remover

### Módulo RAT
- ✅ `GET /api/v1/rat/protocolos` - Lista protocolos
- ✅ `GET /api/v1/rat/protocolos/{id}` - Detalhes
- ✅ `POST /api/v1/rat/protocolos` - Criar

### Integração
- ✅ `GET /api/v1/integracao/rat/{ratId}/pae` - Buscar PAE por RAT
- ✅ `GET /api/v1/integracao/pae/{paeId}/rat` - Buscar RAT por PAE

## 📁 Arquivos Criados

### Controllers
- `app/Http/Controllers/Api/V1/Auth/AuthController.php`
- `app/Http/Controllers/Api/V1/Pae/EmpreendimentoController.php`
- `app/Http/Controllers/Api/V1/Rat/ProtocoloController.php`
- `app/Http/Controllers/Api/V1/Integracao/IntegracaoController.php`

### Configuração
- `config/l5-swagger.php` - Configuração do Swagger
- `routes/api.php` - Rotas da API documentadas

### Documentação
- `SWAGGER_SETUP.md` - Guia de configuração
- `GUIA_SWAGGER.md` - Guia de uso da interface

## 🔧 Configuração

### Variáveis de Ambiente (.env)

```env
L5_SWAGGER_CONST_HOST=http://localhost
L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_UI_DARK_MODE=false
L5_SWAGGER_UI_DOC_EXPANSION=list
```

### Gerar Documentação Automaticamente

Para desenvolvimento, configure para gerar sempre:

```env
L5_SWAGGER_GENERATE_ALWAYS=true
```

Para produção, defina como `false` e gere manualmente quando necessário.

## 📖 Como Adicionar Novos Endpoints

1. Crie o controller em `app/Http/Controllers/Api/V1/`
2. Adicione anotações `@OA\*` seguindo os exemplos existentes
3. Execute: `php artisan l5-swagger:generate`
4. Acesse: `http://localhost/api/documentation`

### Exemplo de Anotação

```php
/**
 * @OA\Get(
 *     path="/api/v1/seu-endpoint",
 *     summary="Descrição do endpoint",
 *     tags={"Sua Tag"},
 *     security={{"sanctum": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Sucesso",
 *         @OA\JsonContent(...)
 *     )
 * )
 */
public function seuMetodo() {
    // ...
}
```

## 🎯 Recursos do Swagger UI

- ✅ **Teste direto**: Teste endpoints sem sair do navegador
- ✅ **Autenticação integrada**: Sistema de autenticação com tokens
- ✅ **Exemplos**: Cada endpoint tem exemplos de request/response
- ✅ **Validação**: Veja esquemas de validação
- ✅ **Filtro**: Busque endpoints rapidamente
- ✅ **Código**: Gere código de exemplo em várias linguagens

## 📝 Schemas Documentados

- `Empreendimento` - Estrutura de dados do empreendimento PAE
- `ProtocoloRAT` - Estrutura de dados do protocolo RAT
- `PaginationMeta` - Metadados de paginação
- `PaginationLinks` - Links de paginação

## 🔐 Autenticação

Todas as rotas (exceto login) requerem autenticação via Laravel Sanctum.

**Formato do token**: `Bearer {token}`

## 📚 Documentação Adicional

- `API_REST_MODULOS.md` - Visão geral da arquitetura de APIs
- `EXEMPLO_API_IMPLEMENTACAO.md` - Exemplos práticos de implementação
- `SWAGGER_SETUP.md` - Detalhes de configuração
- `GUIA_SWAGGER.md` - Guia completo de uso

## 🎉 Pronto para Usar!

A documentação Swagger está configurada e pronta para uso. Acesse `http://localhost/api/documentation` para começar a explorar e testar os endpoints da API!

