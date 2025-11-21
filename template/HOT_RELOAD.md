# Hot Reload - Guia de Uso

## Configuração Implementada

O projeto está configurado com **Vite Hot Module Replacement (HMR)** para desenvolvimento rápido com atualização automática do browser.

## Como Funciona

### Desenvolvimento Local (Docker)

#### Método 1: Node Container em Segundo Plano
O container `node` já está configurado para rodar o Vite automaticamente:

```bash
# Iniciar todos os containers (incluindo Vite)
docker-compose -f docker-compose.dev.yml up -d

# Ver logs do Vite
docker-compose -f docker-compose.dev.yml logs -f node
```

#### Método 2: Executar Vite Manualmente
Para ter mais controle sobre o Vite:

```bash
# Parar o container node automático
docker-compose -f docker-compose.dev.yml stop node

# Executar Vite em foreground
docker-compose -f docker-compose.dev.yml run --rm --service-ports node npm run dev
```

## Configurações do Vite

O arquivo `vite.config.js` está configurado com:

```javascript
server: {
    host: '0.0.0.0',           // Aceita conexões externas (Docker)
    port: 5173,                // Porta do Vite
    strictPort: true,          // Não muda a porta automaticamente
    watch: {
        usePolling: true,      // Necessário para Docker/WSL
    },
    hmr: {
        host: 'localhost',     // Host para HMR
        port: 5173,            // Porta HMR
    },
}
```

## URLs de Acesso

Após iniciar os containers:

- **Aplicação Laravel**: http://localhost
- **Vite Dev Server**: http://localhost:5173
- **MailHog (Email)**: http://localhost:8025

## Como Testar o Hot Reload

1. **Inicie os containers:**
   ```bash
   docker-compose -f docker-compose.dev.yml up -d
   ```

2. **Verifique se o Vite está rodando:**
   ```bash
   docker-compose -f docker-compose.dev.yml logs node
   ```

   Você deve ver algo como:
   ```
   VITE v5.x.x  ready in xxx ms
   ➜  Local:   http://localhost:5173/
   ➜  Network: http://172.x.x.x:5173/
   ```

3. **Abra a aplicação:**
   ```
   http://localhost
   ```

4. **Edite um arquivo Vue ou JS:**
   ```
   resources/js/Pages/Welcome.vue
   ```

5. **O browser atualiza automaticamente!** ⚡

## Troubleshooting

### Hot Reload não funciona?

1. **Verifique se o Vite está rodando:**
   ```bash
   docker-compose -f docker-compose.dev.yml ps
   ```
   O container `sdc_node` deve estar `Up`

2. **Reinicie o container Node:**
   ```bash
   docker-compose -f docker-compose.dev.yml restart node
   ```

3. **Verifique os logs por erros:**
   ```bash
   docker-compose -f docker-compose.dev.yml logs node
   ```

### Mudanças não são detectadas?

Isso pode acontecer em ambiente WSL2/Docker. A configuração `usePolling: true` resolve isso, mas se ainda tiver problemas:

```bash
# Aumente o limite de watchers do sistema (Linux/WSL)
echo fs.inotify.max_user_watches=524288 | sudo tee -a /etc/sysctl.conf
sudo sysctl -p
```

### Porta 5173 já está em uso?

```bash
# Pare todos os containers
docker-compose -f docker-compose.dev.yml down

# Ou mude a porta em vite.config.js e docker-compose.dev.yml
```

## Comandos Úteis

```bash
# Ver logs do Vite em tempo real
docker-compose -f docker-compose.dev.yml logs -f node

# Reiniciar apenas o Vite
docker-compose -f docker-compose.dev.yml restart node

# Parar Vite
docker-compose -f docker-compose.dev.yml stop node

# Iniciar Vite
docker-compose -f docker-compose.dev.yml start node

# Build para produção
docker-compose -f docker-compose.dev.yml run --rm node npm run build
```

## Recursos Adicionais

### Vite Features Habilitadas

- ✅ Hot Module Replacement (HMR)
- ✅ Fast Refresh para Vue.js
- ✅ CSS Hot Reload
- ✅ Auto-reload para mudanças em templates Blade
- ✅ Source Maps para debugging

### Arquivos Monitorados

O Vite monitora automaticamente:
- `resources/js/**/*`
- `resources/css/**/*`
- `resources/vue/**/*`

O Laravel Vite Plugin também monitora (com `refresh: true`):
- `resources/views/**/*.blade.php`
- `routes/**/*.php`
- `app/View/Components/**/*.php`

## Performance

### Desenvolvimento
- ⚡ Startup rápido (< 1s)
- ⚡ HMR instantâneo (< 100ms)
- ⚡ Atualizações sem reload completo

### Build Produção
```bash
# Build otimizado
docker-compose -f docker-compose.dev.yml run --rm node npm run build

# O build vai para public/build/
```

## Dicas

1. **Mantenha o Vite rodando** durante desenvolvimento para aproveitar o HMR
2. **Use Vue DevTools** para melhor experiência de debug
3. **Reinicie o Vite** se adicionar novos arquivos que não são detectados
4. **Use `console.log`** - eles aparecem no browser instantaneamente!

## Integração com IDE

### VS Code
Recomendado instalar extensões:
- Volar (Vue 3)
- ESLint
- Tailwind CSS IntelliSense

### PhpStorm/WebStorm
- Já tem suporte nativo para Vite
- Configure o servidor de desenvolvimento em Settings > Languages & Frameworks > JavaScript > Webpack

---

**Aproveite o desenvolvimento rápido com Hot Reload! 🚀**
