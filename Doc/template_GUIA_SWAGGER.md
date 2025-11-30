# Guia de Uso do Swagger - SDC API

## Acesso à Documentação

Após configurar e gerar a documentação, acesse:

**URL**: `http://localhost/api/documentation`

## Como Usar

### 1. Autenticação

Antes de testar os endpoints protegidos, você precisa obter um token:

#### Passo 1: Fazer Login
1. Na interface do Swagger, encontre o endpoint `POST /api/v1/auth/login`
2. Clique em "Try it out"
3. Preencha os dados:
   ```json
   {
     "cpf": "12345678900",
     "password": "password"
   }
   ```
4. Clique em "Execute"
5. Copie o `token` retornado na resposta

#### Passo 2: Autorizar no Swagger
1. Clique no botão **"Authorize"** (cadeado) no topo da página
2. No campo "Value", digite: `Bearer {seu_token}` (substitua {seu_token} pelo token copiado)
3. Clique em **"Authorize"**
4. Clique em **"Close"**

Agora você pode testar todos os endpoints protegidos!

### 2. Testar Endpoints

#### Listar Empreendimentos PAE
1. Encontre `GET /api/v1/pae/empreendimentos`
2. Clique em "Try it out"
3. (Opcional) Preencha parâmetros de query (page, per_page, etc.)
4. Clique em "Execute"
5. Veja a resposta JSON

#### Criar Empreendimento
1. Encontre `POST /api/v1/pae/empreendimentos`
2. Clique em "Try it out"
3. Edite o JSON de exemplo no "Request body"
4. Clique em "Execute"
5. Veja a resposta com o empreendimento criado

### 3. Estrutura de Respostas

#### Sucesso (200/201)
```json
{
  "data": {
    "id": 1,
    "nome": "Barragem Sul Superior",
    ...
  },
  "message": "Operação realizada com sucesso"
}
```

#### Erro de Validação (422)
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "nome": ["O campo nome é obrigatório."]
  }
}
```

#### Não Autenticado (401)
```json
{
  "message": "Unauthenticated."
}
```

## Endpoints Disponíveis

### Autenticação
- `POST /api/v1/auth/login` - Login e obtenção de token
- `POST /api/v1/auth/logout` - Logout (revoga token)
- `GET /api/v1/auth/me` - Dados do usuário autenticado

### Módulo PAE
- `GET /api/v1/pae/empreendimentos` - Lista empreendimentos
- `GET /api/v1/pae/empreendimentos/{id}` - Detalhes
- `POST /api/v1/pae/empreendimentos` - Criar
- `PUT /api/v1/pae/empreendimentos/{id}` - Atualizar
- `DELETE /api/v1/pae/empreendimentos/{id}` - Remover

### Módulo RAT
- `GET /api/v1/rat/protocolos` - Lista protocolos
- `GET /api/v1/rat/protocolos/{id}` - Detalhes
- `POST /api/v1/rat/protocolos` - Criar

### Integração
- `GET /api/v1/integracao/rat/{ratId}/pae` - Buscar PAE por RAT
- `GET /api/v1/integracao/pae/{paeId}/rat` - Buscar RAT por PAE

## Gerar Documentação

Para regenerar a documentação após adicionar novos endpoints:

```bash
php artisan l5-swagger:generate
```

Ou configure para gerar automaticamente (apenas desenvolvimento):

```env
L5_SWAGGER_GENERATE_ALWAYS=true
```

## Dicas

1. **Use o filtro**: Digite no campo de busca para filtrar endpoints
2. **Expanda seções**: Clique nas setas para expandir detalhes
3. **Veja exemplos**: Cada endpoint tem exemplos de request/response
4. **Teste direto**: Use o botão "Try it out" para testar sem sair do navegador
5. **Copie código**: Use o botão "Copy" para copiar exemplos de código

## Troubleshooting

### Erro: "Unauthenticated"
- Verifique se você fez login e copiou o token corretamente
- Certifique-se de usar o formato: `Bearer {token}` (com espaço após Bearer)

### Documentação não aparece
- Execute: `php artisan l5-swagger:generate`
- Verifique se o diretório `storage/api-docs` existe e tem permissões de escrita

### Erro ao gerar documentação
- Verifique se todas as anotações `@OA\*` estão corretas
- Verifique se os controllers estão no diretório `app/Http/Controllers/Api/`

