# Scripts de Inicialização MySQL

Este diretório contém scripts SQL que são executados automaticamente quando o container MySQL é iniciado pela primeira vez.

## 📋 Scripts Disponíveis

### `01-init-test-user.sql`
Cria o usuário de teste `sdc` com todas as permissões necessárias para a aplicação Laravel se conectar ao banco de dados.

**Credenciais:**
- **Usuário**: `sdc`
- **Senha**: `secret`
- **Banco de Dados**: `sdc`
- **Hosts permitidos**: `%` (qualquer host) e `localhost`

**Permissões:**
- Todas as permissões no banco `sdc`
- Inclui: SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, etc.

## 🔄 Quando os Scripts são Executados?

Os scripts em `/docker-entrypoint-initdb.d/` são executados **apenas na primeira inicialização** do container MySQL, quando o volume de dados está vazio.

**Importante**: Se você já tem dados no volume, os scripts **não serão executados novamente**.

## 🔧 Recriar o Usuário de Teste

Se você precisar recriar o usuário de teste (por exemplo, após mudar a senha):

```bash
# 1. Parar os containers
make dev-down

# 2. Remover o volume do banco (CUIDADO: apaga todos os dados!)
docker volume rm sdc-dev_db_data_dev

# 3. Iniciar novamente
make dev

# Os scripts serão executados automaticamente
```

## ✅ Verificar se o Usuário Foi Criado

```bash
# Acessar o shell do MySQL
make db-shell

# No MySQL CLI:
mysql> SELECT user, host FROM mysql.user WHERE user = 'sdc';
mysql> SHOW GRANTS FOR 'sdc'@'%';
```

## 🔐 Configuração na Aplicação

As credenciais devem estar configuradas no arquivo `.env` ou nas variáveis de ambiente do `docker-compose.yml`:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=sdc
DB_USERNAME=sdc
DB_PASSWORD=secret
```

## 🚀 Para MVP de Demonstração

Este usuário de teste está configurado especificamente para a bridge de desenvolvimento, permitindo que a middleware de autenticação funcione corretamente no ambiente Docker dentro do ACR (Azure Container Registry).

---

## 👤 Usuário de Teste da Aplicação

Após executar as migrations e seeders, um usuário de teste estará disponível para login na aplicação:

**Credenciais de Login:**
- **CPF**: `12345678900` (sem formatação)
- **Senha**: `password`
- **Email**: `teste@defesa.mg.gov.br`
- **Nome**: `Usuário de Teste`

**Como garantir que o usuário existe:**

```bash
# 1. Executar migrations
make migrate

# 2. Executar seeders (cria o usuário de teste)
make seed

# Ou tudo de uma vez:
make fresh
```

**Verificar o usuário no banco:**

```bash
# Via MySQL CLI
make db-shell
mysql> SELECT id, name, cpf, email FROM users WHERE cpf = '12345678900';

# Via Laravel Tinker
docker compose -f docker/docker-compose.yml exec app php artisan tinker
>>> App\Models\User::where('cpf', '12345678900')->first();
```

**Testar autenticação:**

```bash
# Verificar se a senha está correta
docker compose -f docker/docker-compose.yml exec app php artisan tinker --execute="echo Hash::check('password', App\Models\User::where('cpf', '12345678900')->first()->password) ? 'OK' : 'FAIL';"
```

