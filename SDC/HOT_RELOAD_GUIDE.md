# 🔥 Guia de Hot Reload - Vite + Docker

## ✅ Como Usar o Hot Reload Corretamente

### **Modo Desenvolvimento (Hot Reload Ativo)**

O Vite dev server já está **rodando automaticamente** no container `newsdc_node`.

#### ✅ O QUE FAZER:
1. **Edite seus arquivos** `.vue`, `.js`, `.css` normalmente
2. **Salve o arquivo** (Ctrl+S)
3. **A página recarrega automaticamente** 🎉

#### ❌ O QUE **NÃO** FAZER:
- **NÃO execute `npm run build`** durante desenvolvimento
- **NÃO execute `npm run dev`** manualmente (já está rodando no Docker)

### **Se o Hot Reload Parar de Funcionar**

Execute este comando:

```bash
# Remove build de produção e limpa caches
rm -rf public/build && php artisan cache:clear
```

Depois recarregue a página com **Ctrl+Shift+R** (hard refresh).

---

## 🏗️ Modo Produção (Build Final)

**Quando fazer build:**
- Antes de fazer deploy
- Para testar performance de produção
- Para gerar assets otimizados

```bash
npm run build
```

**Depois do build:**
- Hot reload **não funciona** (assets são estáticos)
- Para voltar ao dev mode: `rm -rf public/build`

---

## 🔍 Verificar Status do Vite

```bash
# Ver logs do Vite
docker logs newsdc_node --tail 50

# Verificar se Vite está rodando
docker exec newsdc_node ps aux | grep vite

# Testar conexão HMR
curl -s http://localhost:5173/@vite/client | head -5
```

---

## 🐛 Troubleshooting

### Hot reload não funciona:
1. Verifique se há arquivos em `public/build/`:
   ```bash
   ls -la public/build/
   ```
   - **Se houver arquivos**: `rm -rf public/build`

2. Limpe todos os caches:
   ```bash
   php artisan cache:clear
   php artisan view:clear
   docker exec newsdc_app php -r "opcache_reset();"
   ```

3. Hard refresh no navegador: **Ctrl+Shift+R**

### Mudanças CSS não aparecem:
- Verifique se não há CSS inline sobrescrevendo
- Limpe cache do navegador (Ctrl+Shift+Del)
- Verifique as classes Tailwind no navegador (F12 > Elements)

---

## 📝 Resumo

| Situação | Comando | Hot Reload? |
|----------|---------|-------------|
| **Desenvolvimento** | Nenhum (já está rodando) | ✅ SIM |
| **Produção/Deploy** | `npm run build` | ❌ NÃO |
| **Voltar ao Dev** | `rm -rf public/build` | ✅ SIM |

**Regra de Ouro:** Em desenvolvimento, **nunca** execute `npm run build`!
