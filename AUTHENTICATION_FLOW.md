# 🔐 Fluxo de Autenticação - Sistema SDC

Documentação completa do fluxo de autenticação e configuração de rotas do sistema.

**Data**: 2025-01-21
**Versão**: 1.0.0

---

## 📋 Índice

- [Visão Geral](#-visão-geral)
- [Estrutura de Rotas](#-estrutura-de-rotas)
- [Tela de Login](#-tela-de-login)
- [Fluxo de Autenticação](#-fluxo-de-autenticação)
- [Middleware de Proteção](#-middleware-de-proteção)
- [Configuração Atual](#-configuração-atual)

---

## 🎯 Visão Geral

O sistema SDC utiliza **Laravel Breeze** com **Inertia.js** para autenticação.

### Características

✅ **Tela de Login como Página Inicial** - `/` redireciona para `/login`
✅ **Autenticação via CPF** - Sistema customizado para usar CPF ao invés de email
✅ **Proteção de Rotas** - Middleware `auth` protege todas as rotas internas
✅ **Sessão Persistente** - Opção "Lembrar-me" disponível
✅ **Recuperação de Senha** - Fluxo completo implementado

---

## 🗺️ Estrutura de Rotas

### Rotas Públicas (Guest)

Acessíveis apenas quando **NÃO autenticado**:

```
GET  /                    → Redireciona para /login
GET  /login               → Tela de Login
POST /login               → Processar Login
GET  /register            → Tela de Registro (se habilitado)
POST /register            → Processar Registro
GET  /forgot-password     → Esqueci minha senha
POST /forgot-password     → Enviar link de reset
GET  /reset-password/{token} → Resetar senha
POST /reset-password      → Salvar nova senha
```

### Rotas Protegidas (Auth)

Acessíveis apenas quando **autenticado**:

```
GET  /dashboard           → Painel principal
GET  /pae                 → Listagem de PAEs
GET  /profile             → Perfil do usuário
PATCH /profile            → Atualizar perfil
DELETE /profile           → Deletar conta
GET  /logs                → Visualizador de logs (admin)
POST /logout              → Sair do sistema
```

---

## 🖥️ Tela de Login

### Localização

**Arquivo**: [resources/js/Pages/Auth/Login.vue](SDC/resources/js/Pages/Auth/Login.vue)

### URL de Acesso

```
http://localhost/login
http://localhost/          (redireciona para /login)
```

### Estrutura da Tela

```
┌─────────────────────────────────────────────┐
│                                             │
│        [Logo Defesa Civil MG]               │
│     Sistema Integrado de Defesa Civil       │
│                                             │
│  ┌────────────────────────────────────┐    │
│  │  👤  CPF                            │    │
│  │  ___.___.___-__                     │    │
│  └────────────────────────────────────┘    │
│                                             │
│  ┌────────────────────────────────────┐    │
│  │  🔒  Senha                     👁    │    │
│  │  ••••••••                           │    │
│  └────────────────────────────────────┘    │
│                                             │
│  ☐ Lembrar-me        Esqueceu a senha?     │
│                                             │
│  ┌────────────────────────────────────┐    │
│  │       Acessar Sistema              │    │
│  └────────────────────────────────────┘    │
│                                             │
│  © 2025 Governo do Estado de Minas Gerais  │
└─────────────────────────────────────────────┘
```

### Features Implementadas

#### 1. **Input CPF com Máscara**
```vue
<!-- Formata automaticamente: 000.000.000-00 -->
<input
  v-model="cpfFormatted"
  @input="updateCpf($event.target.value)"
  maxlength="14"
/>
```

**Máscaras aplicadas**:
- Entrada: `12345678900`
- Exibição: `123.456.789-00`

#### 2. **Input Senha com Toggle**
```vue
<!-- Alterna entre password/text -->
<input :type="showPassword ? 'text' : 'password'" />
<span @click="togglePasswordVisibility">👁</span>
```

#### 3. **Validação em Tempo Real**
```javascript
const isValid = computed(() => {
  return cpf.value.length === 11 && password.value.length >= 3
})
```

**Botão desabilitado** se:
- CPF incompleto (< 11 dígitos)
- Senha muito curta (< 3 caracteres)
- Loading ativo

#### 4. **Mensagens de Erro**
```vue
<div v-if="errors.cpf" class="error-message">
  {{ errors.cpf }}
</div>
```

Erros exibidos:
- CPF inválido
- Senha incorreta
- Conta bloqueada
- Erros de servidor

#### 5. **Loading State**
```vue
<button :disabled="loading">
  <span v-if="!loading">Acessar Sistema</span>
  <span v-else>Autenticando...</span>
</button>
```

---

## 🔄 Fluxo de Autenticação

### Diagrama Completo

```
┌─────────────┐
│   Usuário   │
└──────┬──────┘
       │
       │ 1. Acessa http://localhost
       ▼
┌─────────────────────────────────────┐
│  Route::get('/')                    │
│  return redirect()->route('login')  │
└──────┬──────────────────────────────┘
       │
       │ 2. Redireciona para /login
       ▼
┌─────────────────────────────────────┐
│  Middleware: guest                  │
│  (verifica se NÃO autenticado)      │
└──────┬──────────────────────────────┘
       │
       │ SE NÃO autenticado:
       ▼
┌─────────────────────────────────────┐
│  AuthenticatedSessionController     │
│  create() → Exibe tela de Login     │
└──────┬──────────────────────────────┘
       │
       │ 3. Usuário preenche CPF + Senha
       │ 4. Clica "Acessar Sistema"
       ▼
┌─────────────────────────────────────┐
│  POST /login                        │
│  AuthenticatedSessionController     │
│  store()                            │
└──────┬──────────────────────────────┘
       │
       │ 5. Validação
       ▼
┌─────────────────────────────────────┐
│  Valida CPF e Senha no Banco        │
└──────┬──────────────────────────────┘
       │
       ├─ ❌ FALHOU
       │   └─> Retorna erro (CPF/senha inválidos)
       │
       └─ ✅ SUCESSO
           │
           │ 6. Cria sessão autenticada
           ▼
┌─────────────────────────────────────┐
│  Auth::login($user, $remember)      │
│  Cria sessão + cookie (se lembrar)  │
└──────┬──────────────────────────────┘
       │
       │ 7. Redireciona para Dashboard
       ▼
┌─────────────────────────────────────┐
│  redirect()->intended('/dashboard') │
└──────┬──────────────────────────────┘
       │
       │ 8. Verifica middleware auth
       ▼
┌─────────────────────────────────────┐
│  Middleware: auth                   │
│  (verifica se autenticado)          │
└──────┬──────────────────────────────┘
       │
       │ ✅ Autenticado
       ▼
┌─────────────────────────────────────┐
│  Inertia::render('Dashboard')       │
│  Exibe Dashboard                    │
└─────────────────────────────────────┘
```

---

## 🔒 Middleware de Proteção

### Guest Middleware

**Objetivo**: Permitir acesso apenas a usuários **não autenticados**

**Rotas Protegidas**:
- `/login`
- `/register`
- `/forgot-password`
- `/reset-password`

**Comportamento**:
```php
if (Auth::check()) {
    // Usuário JÁ autenticado
    return redirect('/dashboard');
}
// Permite acesso à rota
```

**Exemplo Prático**:
```
Usuário logado tenta acessar /login
→ É redirecionado automaticamente para /dashboard
```

---

### Auth Middleware

**Objetivo**: Permitir acesso apenas a usuários **autenticados**

**Rotas Protegidas**:
- `/dashboard`
- `/pae`
- `/profile`
- `/logs`
- Todas dentro de `Route::middleware('auth')`

**Comportamento**:
```php
if (!Auth::check()) {
    // Usuário NÃO autenticado
    return redirect('/login');
}
// Permite acesso à rota
```

**Exemplo Prático**:
```
Usuário NÃO logado tenta acessar /dashboard
→ É redirecionado automaticamente para /login
→ Após login, volta para /dashboard (intended)
```

---

## ⚙️ Configuração Atual

### 1. Rota Raiz (`/`)

**Arquivo**: [routes/web.php:19-22](SDC/routes/web.php#L19-L22)

```php
Route::get('/', function () {
    // Redireciona para a página de login como página inicial
    return redirect()->route('login');
});
```

✅ **Status**: Configurado corretamente
✅ **Comportamento**: Sempre redireciona para `/login`

---

### 2. Rotas de Autenticação

**Arquivo**: [routes/auth.php](SDC/routes/auth.php)

```php
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // ... outras rotas guest
});
```

✅ **Status**: Configurado corretamente com Laravel Breeze

---

### 3. Rotas Protegidas

**Arquivo**: [routes/web.php:24-39](SDC/routes/web.php#L24-L39)

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/pae', function () {
        return Inertia::render('Pae');
    })->name('pae.index');

    // ... outras rotas protegidas
});
```

✅ **Status**: Todas as rotas internas protegidas

---

### 4. Composable de Login

**Arquivo**: [resources/js/composables/useLogin.js](SDC/resources/js/composables/useLogin.js)

```javascript
export function useLogin() {
  const cpf = ref('')
  const password = ref('')
  const remember = ref(false)
  const showPassword = ref(false)
  const loading = ref(false)
  const errors = ref({})

  // Formata CPF: 000.000.000-00
  const cpfFormatted = computed(() => formatCpf(cpf.value))

  // Valida formulário
  const isValid = computed(() => {
    return cpf.value.length === 11 && password.value.length >= 3
  })

  // Submete login
  const submitLogin = async () => {
    loading.value = true
    errors.value = {}

    router.post('/login', {
      cpf: cpf.value,
      password: password.value,
      remember: remember.value
    }, {
      onError: (err) => {
        errors.value = err
        loading.value = false
      },
      onSuccess: () => {
        // Redireciona para dashboard
      }
    })
  }

  return {
    cpf,
    password,
    remember,
    showPassword,
    loading,
    errors,
    cpfFormatted,
    isValid,
    updateCpf,
    togglePasswordVisibility,
    submitLogin
  }
}
```

✅ **Status**: Implementado com validação e formatação

---

## 🎨 Estilização da Tela de Login

### Design Atual

```css
.login-container {
  min-height: 100vh;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.25rem;
  background: linear-gradient(135deg, #06315c, #001224);
}
```

**Features**:
- ✅ Gradiente azul escuro (identidade visual MG)
- ✅ Centralizado vertical e horizontalmente
- ✅ Responsivo (mobile-friendly)
- ✅ Logo oficial da Defesa Civil
- ✅ Campos com ícones
- ✅ Animações suaves
- ✅ Dark mode nativo

---

## 🧪 Testando o Fluxo

### Teste 1: Acesso à Raiz

```bash
# Navegador
http://localhost/

# Esperado
→ Redireciona para http://localhost/login
→ Exibe tela de login
```

### Teste 2: Login com Credenciais Válidas

```bash
# 1. Preencher formulário
CPF: 123.456.789-00
Senha: senha123

# 2. Clicar "Acessar Sistema"

# Esperado
→ Loading aparece
→ POST /login
→ Sessão criada
→ Redireciona para /dashboard
```

### Teste 3: Login com Credenciais Inválidas

```bash
# 1. Preencher formulário
CPF: 000.000.000-00
Senha: errada

# 2. Clicar "Acessar Sistema"

# Esperado
→ Loading aparece
→ POST /login
→ Retorna erro
→ Exibe mensagem: "CPF ou senha inválidos"
→ Permanece na tela de login
```

### Teste 4: Acesso a Rota Protegida Sem Login

```bash
# Navegador (sem estar logado)
http://localhost/dashboard

# Esperado
→ Middleware auth detecta não autenticado
→ Redireciona para /login
→ Após login, volta para /dashboard
```

### Teste 5: Tentativa de Acessar Login Já Logado

```bash
# Navegador (já logado)
http://localhost/login

# Esperado
→ Middleware guest detecta autenticado
→ Redireciona para /dashboard
```

---

## 🔧 Troubleshooting

### Problema 1: Rota raiz não redireciona

**Sintoma**: `http://localhost/` mostra página em branco

**Solução**:
```php
// routes/web.php
Route::get('/', function () {
    return redirect()->route('login');
});
```

---

### Problema 2: Login não funciona

**Sintomas**:
- Botão não clica
- Nada acontece ao submeter

**Diagnóstico**:
```javascript
// No navegador (F12 > Console)
// Ver se há erros JavaScript
```

**Soluções**:
```bash
# 1. Verificar se Inertia está instalado
npm list @inertiajs/vue3

# 2. Limpar cache
php artisan cache:clear
php artisan config:clear

# 3. Rebuild assets
npm run dev
```

---

### Problema 3: Sessão não persiste

**Sintoma**: Após login, é deslogado imediatamente

**Solução**:
```bash
# 1. Verificar .env
SESSION_DRIVER=file  # ou redis/database
SESSION_LIFETIME=120

# 2. Verificar permissões
chmod -R 775 storage/framework/sessions

# 3. Gerar chave da aplicação
php artisan key:generate
```

---

### Problema 4: "Esqueceu a senha" não funciona

**Sintoma**: Link não redireciona ou email não chega

**Solução**:
```bash
# 1. Verificar rota
Route::get('forgot-password', ...)
    ->name('password.request');

# 2. Verificar configuração de email (.env)
MAIL_MAILER=smtp
MAIL_HOST=mailhog  # desenvolvimento
MAIL_PORT=1025

# 3. Testar email
php artisan tinker
Mail::raw('Test', fn($m) => $m->to('test@test.com'));
```

---

## 📊 Fluxo de Navegação

### Usuário Não Autenticado

```
/ → /login → Preenche formulário → POST /login → /dashboard
│                                       │
│                                       └─ [Erro] → Volta /login
│
└─ /pae → /login → ... (intended: /pae)
```

### Usuário Autenticado

```
/ → /dashboard
│
├─ /pae → Exibe PAE
├─ /profile → Exibe Perfil
├─ /login → /dashboard (não pode acessar)
└─ POST /logout → /login
```

---

## 🔐 Segurança Implementada

### 1. **CSRF Protection**
```html
<!-- Automático no Inertia -->
<form @submit.prevent="submitLogin">
  <!-- Token CSRF incluído automaticamente -->
</form>
```

### 2. **Password Hashing**
```php
// Senhas são sempre hasheadas no banco
password_hash($password, PASSWORD_BCRYPT);
```

### 3. **Rate Limiting**
```php
// routes/auth.php
Route::middleware('throttle:6,1') // 6 tentativas por minuto
```

### 4. **Session Security**
```php
// config/session.php
'secure' => env('SESSION_SECURE_COOKIE', false),  // HTTPS only
'http_only' => true,  // XSS protection
'same_site' => 'lax',  // CSRF protection
```

---

## ✅ Checklist de Configuração

### Rotas
- [x] Rota raiz (`/`) redireciona para `/login`
- [x] Rota `/login` protegida com `guest` middleware
- [x] Rotas internas protegidas com `auth` middleware
- [x] Logout funciona corretamente

### Tela de Login
- [x] Campo CPF com máscara
- [x] Campo senha com toggle de visibilidade
- [x] Checkbox "Lembrar-me"
- [x] Link "Esqueceu a senha?"
- [x] Validação em tempo real
- [x] Loading state durante autenticação
- [x] Mensagens de erro claras

### Funcionalidades
- [x] Login com CPF funciona
- [x] Sessão persiste após login
- [x] Logout funciona
- [x] Redirecionamento correto após login
- [x] Proteção de rotas funcionando

---

## 📚 Referências

- [Laravel Breeze Documentation](https://laravel.com/docs/starter-kits#laravel-breeze)
- [Inertia.js Authentication](https://inertiajs.com/authentication)
- [Laravel Authentication](https://laravel.com/docs/authentication)

---

**Configuração verificada em**: 2025-01-21
**Status**: ✅ Funcionando Corretamente
**Tela de Login**: http://localhost/login
