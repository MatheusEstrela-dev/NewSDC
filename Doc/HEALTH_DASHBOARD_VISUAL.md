# 📊 Health Check Dashboard Visual

## 🎨 Interface Moderna e Interativa

Dashboard visual completo para monitoramento de saúde do sistema em tempo real com auto-refresh a cada 5 segundos.

---

## 🚀 ACESSAR DASHBOARD

### URL Principal:
```
http://localhost:8000/health-dashboard
```

**Requer:** Login no sistema

---

## ✨ FUNCIONALIDADES

### 1. **Cards de Resumo** (4 cards superiores)
- ⏱️ **Uptime** - Tempo que o sistema está online
- 💾 **Memória** - Uso atual e pico de memória
- 🖥️ **CPU Load** - Carga do processador (1min/5min/15min)
- 📊 **Requisições/min** - Taxa de requisições por minuto

### 2. **Status de Componentes** (cards grandes)

#### 🗄️ Database (MySQL)
- Status (OK/ERROR)
- Latência em ms
- Tipo de conexão

#### 🔴 Redis Cache
- Status (OK/ERROR)
- Latência em ms
- Memória usada
- Clientes conectados

#### 📋 Queue System
- Status (OK/ERROR)
- Jobs pendentes (com alerta se > 500)
- Filas monitoradas

#### 💿 Storage
- Status (OK/WARNING/ERROR)
- Total/Livre em GB
- **Barra de progresso visual** colorida:
  - 🟢 Verde: < 75% usado
  - 🟡 Amarelo: 75-90% usado
  - 🔴 Vermelho: > 90% usado

### 3. **Informações do Sistema**
- PHP Version
- Laravel Version
- Timestamp da última atualização

### 4. **Features Visuais**
- ✅ Animações suaves (slide-in, pulse)
- ✅ Auto-refresh a cada 5 segundos
- ✅ Botão manual de refresh
- ✅ Badges coloridos de status
- ✅ Ícones FontAwesome
- ✅ Design responsivo (mobile-friendly)
- ✅ Dark theme moderno
- ✅ Loading states com spinner

---

## 🎨 CORES E ESTADOS

### Status Geral
- 🟢 **SAUDÁVEL** (verde) - Todos componentes OK
- 🟡 **DEGRADADO** (amarelo) - Algum componente com problema

### Status de Componentes
- 🟢 **OK** - Badge verde
- 🟡 **WARNING** - Badge amarelo
- 🔴 **ERROR** - Badge vermelho

### Cores por Serviço
- 🔵 Database - Azul
- 🔴 Redis - Vermelho
- 🟡 Queue - Amarelo
- 🟣 Storage - Roxo
- 🟢 Performance - Verde
- 🟠 CPU - Laranja

---

## 📸 EXEMPLO VISUAL

```
┌─────────────────────────────────────────────────────────────┐
│  ❤️ SDC Health Dashboard          Status Geral: SAUDÁVEL ⚫  │
│  Sistema de Defesa Civil                          [Atualizar]│
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │ ⏱️ Uptime │  │💾 Memória│  │🖥️ CPU    │  │📊 Req/min│   │
│  │  2d 5h   │  │ 128.5 MB │  │ 0.5/0.6  │  │   1,250  │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
│                                                              │
│  ┌─────────────────────────┐  ┌─────────────────────────┐  │
│  │ 🗄️ Database (MySQL)     │  │ 🔴 Redis Cache          │  │
│  │ mysql              [OK] │  │ In-Memory Store    [OK] │  │
│  │ Latência: 6.64 ms       │  │ Latência: 0.97 ms       │  │
│  │                         │  │ Memória: 1.26 MB        │  │
│  │                         │  │ Clientes: 2             │  │
│  └─────────────────────────┘  └─────────────────────────┘  │
│                                                              │
│  ┌─────────────────────────┐  ┌─────────────────────────┐  │
│  │ 📋 Queue System         │  │ 💿 Storage              │  │
│  │ redis              [OK] │  │ Disk Space      [WARNING]│  │
│  │ Jobs Pendentes: 0       │  │ Total: 815.33 GB        │  │
│  │ Filas: critical, high...│  │ Livre: 18.61 GB         │  │
│  │                         │  │ ████████████████░░ 97%  │  │
│  └─────────────────────────┘  └─────────────────────────┘  │
│                                                              │
│  ┌─────────────────────────────────────────────────────────┐│
│  │ ℹ️ Informações do Sistema                               ││
│  │ PHP: 8.3.28  │  Laravel: 12.39.0  │  Update: 23:04:01  ││
│  └─────────────────────────────────────────────────────────┘│
│                                                              │
│       Atualização automática a cada 5 segundos              │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔧 TECNOLOGIAS USADAS

- **TailwindCSS** - Framework CSS utility-first
- **Vue.js 3** - Framework JavaScript reativo
- **Chart.js** - Biblioteca de gráficos (preparada para expansão)
- **FontAwesome 6** - Ícones
- **Fetch API** - Requisições para `/api/health/detailed`

---

## 📝 CONFIGURAÇÃO

### 1. Acessar Localmente
```bash
# Sistema já rodando
http://localhost:8000/health-dashboard

# Ou via Swagger testar API primeiro
http://localhost:8000/api/documentation
```

### 2. Integrar no Menu Principal

Adicionar no seu menu de navegação:
```html
<a href="{{ route('health.dashboard') }}">
    <i class="fas fa-heartbeat"></i>
    Health Dashboard
</a>
```

### 3. Personalizar Auto-Refresh

Editar `resources/views/health-dashboard.blade.php`:

```javascript
// Trocar intervalo de 5000ms (5s) para outro valor
this.autoRefresh = setInterval(() => {
    this.refreshData();
}, 10000); // 10 segundos
```

---

## 🚨 ALERTAS VISUAIS

### Quando algo está errado:

1. **Status Geral** muda de "SAUDÁVEL" (verde) para "DEGRADADO" (amarelo)
2. **Badge do componente** fica vermelho (ERROR) ou amarelo (WARNING)
3. **Barra de storage** fica vermelha se > 90%
4. **Jobs pendentes** ficam amarelos se > 500

---

## 📊 PRÓXIMAS MELHORIAS (Opcional)

- [ ] Gráfico de linha com histórico de latência
- [ ] Gráfico de pizza com distribuição de memória
- [ ] Alertas sonoros quando status = degraded
- [ ] Export para PDF
- [ ] Dark/Light theme toggle
- [ ] Dashboard fullscreen mode
- [ ] Filtros de componentes
- [ ] Histórico de incidents

---

## 🎯 CASOS DE USO

### 1. Monitoramento em Tempo Real
Deixar dashboard aberto em monitor secundário para acompanhar saúde do sistema 24/7

### 2. Debugging de Performance
Identificar rapidamente qual componente está com latência alta

### 3. Apresentações
Mostrar saúde do sistema durante demos ou reuniões

### 4. Deploy Monitoring
Acompanhar métricas durante deploys para validar que tudo voltou ao normal

---

## ✅ CHECKLIST

- [x] Interface visual moderna criada
- [x] Auto-refresh a cada 5 segundos
- [x] Animações e transições suaves
- [x] Badges de status coloridos
- [x] Barra de progresso de storage
- [x] Design responsivo
- [x] Rota configurada (`/health-dashboard`)
- [x] Integração com API `/api/health/detailed`

---

## 🚀 ACESSO RÁPIDO

```bash
# Após login
http://localhost:8000/health-dashboard
```

**Login padrão (se ainda não configurou):**
- Email: seu-usuario@example.com
- Senha: sua-senha

---

**🎉 Dashboard visual pronto para usar!**

Acesse agora: **http://localhost:8000/health-dashboard**
