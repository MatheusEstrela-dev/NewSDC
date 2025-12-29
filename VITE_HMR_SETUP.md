# Configuração do Vite HMR (Hot Module Replacement) no Docker

## Status Atual

✅ **Vite Dev Server** está rodando em `http://localhost:5173`
✅ **Laravel App** está rodando em `http://localhost:8001`
✅ **HMR** configurado com polling otimizado (300ms)

## Como Funciona

### Modo Desenvolvimento (HMR Ativo)

1. **APP_ENV** deve estar como `local` no `.env`
2. Acesse a aplicação em `http://localhost:8001`
3. O Vite automaticamente injeta o HMR client
4. Mudanças nos arquivos `.vue`, `.js`, `.css` são refletidas instantaneamente

### Modo Produção (Build)

1. **APP_ENV** está como `production` ou `testing`
2. Usa os assets compilados em `public/build/`
3. Requer `npm run build` após cada mudança

## Configurações Aplicadas

### vite.config.js
```javascript
server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    watch: {
        usePolling: true,
        interval: 300, // Detecção rápida de mudanças
        ignored: ['**/node_modules/**', '**/vendor/**', '**/storage/**', '**/public/**'],
    },
    hmr: {
        host: 'localhost',
        port: 5173,
        protocol: 'ws',
        clientPort: 5173,
        overlay: true, // Erros aparecem na tela
    },
    cors: true,
    origin: 'http://localhost:5173',
}
```

## Como Ativar o HMR

### Opção 1: Alterar APP_ENV (Recomendado para Dev)

```bash
# No arquivo SDC/.env
APP_ENV=local
APP_DEBUG=true
```

Então reinicie o container:
```bash
docker restart newsdc_app
```

### Opção 2: Forçar Vite no Blade

No arquivo `resources/views/app.blade.php`, força o Vite sempre:
```php
@vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
```

## Verificar se HMR Está Funcionando

1. Abra `http://localhost:8001` no navegador
2. Abra o DevTools (F12) → Console
3. Você deve ver mensagens como:
   ```
   [vite] connected.
   [vite] connecting...
   ```

4. Faça uma alteração em qualquer arquivo `.vue`
5. O console mostrará:
   ```
   [vite] hmr update /resources/js/Components/...
   ```

6. A página atualiza automaticamente SEM reload completo

## Teste Rápido

Edite qualquer componente Vue e veja a mudança instantânea:

```vue
<!-- SDC/resources/js/Components/Organisms/Demandas/Header/DemandasPageHeader.vue -->
<Heading :level="2" class="mb-1">
  Demandas [HMR TESTE]  <!-- Adicione isso -->
</Heading>
```

Se o HMR estiver funcionando, você verá "[HMR TESTE]" aparecer instantaneamente sem reload.

## Solução de Problemas

### HMR não conecta

1. Verifique se o container Node está rodando:
   ```bash
   docker ps | grep newsdc_node
   ```

2. Verifique os logs do Vite:
   ```bash
   docker logs newsdc_node --tail 20
   ```

3. Porta 5173 deve estar acessível:
   ```bash
   curl http://localhost:5173
   ```

### Mudanças não aparecem

1. **Limpe o cache do Vite:**
   ```bash
   docker exec newsdc_node sh -c "rm -rf node_modules/.vite"
   docker restart newsdc_node
   ```

2. **Limpe o cache do navegador** (Ctrl+Shift+R)

3. **Verifique APP_ENV:**
   ```bash
   grep APP_ENV SDC/.env
   ```

### Performance Lenta

Se o polling estiver consumindo muito CPU, ajuste no `vite.config.js`:
```javascript
watch: {
    usePolling: true,
    interval: 500, // Aumentar para 500ms ou 1000ms
}
```

## Comandos Úteis

```bash
# Reiniciar Vite dev server
docker restart newsdc_node

# Ver logs do Vite em tempo real
docker logs -f newsdc_node

# Reconstruir node_modules
docker exec newsdc_node npm install --legacy-peer-deps

# Build de produção
docker exec newsdc_node npm run build
```

## Performance Atual

- **Intervalo de polling:** 300ms
- **Arquivos ignorados:** node_modules, vendor, storage, public
- **WebSocket:** localhost:5173
- **Overlay de erros:** Ativado

## Status dos Componentes Corrigidos

✅ **DemandasPageHeader.vue** - Cores neutras (slate) aplicadas
✅ **TdapPageHeader.vue** - Cores neutras (slate) aplicadas
✅ **Build do Vite** - Compilado com sucesso
⏳ **HMR** - Aguardando teste em modo development (APP_ENV=local)

---

**Nota:** Para desenvolvimento ativo, sempre use `APP_ENV=local` para aproveitar o HMR.
Para produção, use `APP_ENV=production` e rode `npm run build` antes de deployar.
