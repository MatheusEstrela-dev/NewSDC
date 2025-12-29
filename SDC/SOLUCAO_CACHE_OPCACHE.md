# Solução: Erro de Props do Vue (Cache OPcache)

## 🔍 Problema Identificado

As páginas TDAP estavam retornando erro no console do Vue:

```
[Vue warn]: Invalid prop: type check failed for prop "movimentacoes".
Expected Array, got Object

movimentacoes = {
  current_page: 1,
  data: Array(0),
  first_page_url: '...',
  ...
}
```

## 🎯 Causa Raiz

**PHP OPcache** servindo código antigo (bytecode cacheado).

O PHP-FPM está rodando desde **26 de dezembro** sem restart, e mesmo com todas as correções aplicadas nos arquivos, o **OPcache** continua servindo a versão antiga do código compilado.

## ✅ Correções Já Aplicadas (Código Correto)

### 1. Use Cases com `executeAsDTO()`

Criados métodos que serializam corretamente os dados:

- `ListProductsUseCase::executeAsDTO()` ([app/Modules/Tdap/Application/UseCases/ListProductsUseCase.php](app/Modules/Tdap/Application/UseCases/ListProductsUseCase.php))
- `ListRecebimentosUseCase::executeAsDTO()` ([app/Modules/Tdap/Application/UseCases/ListRecebimentosUseCase.php](app/Modules/Tdap/Application/UseCases/ListRecebimentosUseCase.php))
- `ListMovimentacoesUseCase::executeAsDTO()` ([app/Modules/Tdap/Application/UseCases/ListMovimentacoesUseCase.php](app/Modules/Tdap/Application/UseCases/ListMovimentacoesUseCase.php))
- `ListRatsUseCase::executeAsDTO()` ([app/Modules/Rat/Application/UseCases/ListRatsUseCase.php](app/Modules/Rat/Application/UseCases/ListRatsUseCase.php))

**Estrutura retornada:**
```php
return [
    'data' => [...],          // Array de objetos serializados
    'pagination' => [...],    // Informações de paginação
    'filters' => [...],       // Filtros aplicados
    'statistics' => [...]     // Estatísticas (onde aplicável)
];
```

### 2. Controllers Atualizados

Todos os controllers agora usam `executeAsDTO()` e passam `$result['data']`:

**Exemplo - TdapMovimentacoesController:**
```php
public function index(Request $request): Response
{
    try {
        $filters = $request->only([...]);
        $perPage = $request->input('per_page', 15);

        $result = $this->listMovimentacoesUseCase->executeAsDTO($filters, $perPage);

        return Inertia::render('Tdap/MovimentacoesIndex', [
            'movimentacoes' => $result['data'],      // ✅ Passa apenas o array
            'pagination' => $result['pagination'],
            'filters' => $result['filters'],
            'statistics' => $result['statistics'],
        ]);
    } catch (\Exception $e) {
        return redirect()->back()->with('error', '...');
    }
}
```

### 3. Componentes Vue Atualizados

Todos os componentes Vue já esperam os props corretos:

- [resources/js/Pages/Tdap/ProductsIndex.vue](resources/js/Pages/Tdap/ProductsIndex.vue)
- [resources/js/Pages/Tdap/RecebimentosIndex.vue](resources/js/Pages/Tdap/RecebimentosIndex.vue)
- [resources/js/Pages/Tdap/MovimentacoesIndex.vue](resources/js/Pages/Tdap/MovimentacoesIndex.vue)

**Props definidos:**
```vue
defineProps({
  movimentacoes: {
    type: Array,           // ✅ Espera Array
    default: () => [],
  },
  pagination: {
    type: Object,
    default: () => ({...}),
  },
  ...
})
```

### 4. Frontend Reconstruído

```bash
npm run build
# ✓ 1325 modules transformed in 3.11s
```

## 🚀 Solução: Reiniciar PHP-FPM

### Opção 1: Script Automático (Recomendado)

Execute o script que criamos:

```bash
./restart-php.sh
```

### Opção 2: Manual

```bash
# Reiniciar PHP-FPM
sudo systemctl restart php8.3-fpm

# Verificar status
sudo systemctl status php8.3-fpm

# Limpar caches Laravel (já feito, mas pode repetir)
php artisan optimize:clear
```

### Opção 3: Se estiver usando Docker

```bash
docker-compose restart app
# ou
docker restart nome_do_container_php
```

## 🧪 Testes Após Restart

1. **Acesse o sistema:**
   ```
   http://localhost:8001/login
   ```

2. **Faça login** com o usuário admin

3. **Teste as páginas TDAP:**
   - http://localhost:8001/tdap/produtos
   - http://localhost:8001/tdap/recebimentos
   - http://localhost:8001/tdap/movimentacoes

4. **Abra o Console do Navegador (F12)**
   - Aba "Console"
   - Verifique se NÃO há mais erros de prop type
   - Verifique se a página renderiza corretamente

5. **Verifique a estrutura de dados:**
   - Aba "Network" (Rede)
   - Clique em "movimentacoes"
   - Aba "Preview" ou "Response"
   - Verifique que `props.movimentacoes` é um **Array**, não um Object com `data`, `current_page`, etc.

## 📊 Verificação Esperada

### ❌ ANTES (Estrutura Errada - Paginator completo):
```json
{
  "props": {
    "movimentacoes": {
      "current_page": 1,
      "data": [...],
      "first_page_url": "...",
      "last_page": 1,
      ...
    }
  }
}
```

### ✅ DEPOIS (Estrutura Correta - Array serializado):
```json
{
  "props": {
    "movimentacoes": [
      {
        "id": 1,
        "product_id": 123,
        "tipo": "entrada",
        "quantidade": 100,
        ...
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 50,
      "last_page": 4
    }
  }
}
```

## 🔧 Diagnóstico de OPcache

### Verificar se OPcache está Ativo:
```bash
php -r "echo 'OPcache enabled: ' . (function_exists('opcache_reset') ? 'YES' : 'NO') . PHP_EOL;"
# Output: OPcache enabled: YES
```

### Ver Processo PHP-FPM:
```bash
ps aux | grep php-fpm
```

**Output mostra processo desde 26/dez:**
```
root     2965  ... dez26 ... php-fpm: master process
```

### Verificar Uptime do Processo:
```bash
ps -p 2965 -o lstart,etime
```

## 📝 Arquivos Modificados (Resumo)

### Backend - Use Cases (4 arquivos):
1. `app/Modules/Tdap/Application/UseCases/ListProductsUseCase.php` - ✅
2. `app/Modules/Tdap/Application/UseCases/ListRecebimentosUseCase.php` - ✅
3. `app/Modules/Tdap/Application/UseCases/ListMovimentacoesUseCase.php` - ✅
4. `app/Modules/Rat/Application/UseCases/ListRatsUseCase.php` - ✅

### Backend - Controllers (4 arquivos):
1. `app/Modules/Tdap/Presentation/Http/Controllers/TdapProductsController.php` - ✅
2. `app/Modules/Tdap/Presentation/Http/Controllers/TdapRecebimentosController.php` - ✅
3. `app/Modules/Tdap/Presentation/Http/Controllers/TdapMovimentacoesController.php` - ✅
4. `app/Modules/Rat/Presentation/Http/Controllers/RatIndexController.php` - ✅

### Frontend - Vue Components (3 arquivos):
1. `resources/js/Pages/Tdap/ProductsIndex.vue` - ✅
2. `resources/js/Pages/Tdap/RecebimentosIndex.vue` - ✅
3. `resources/js/Pages/Tdap/MovimentacoesIndex.vue` - ✅

### Infraestrutura:
1. `app/Services/Logging/ActivityLogger.php` - ✅ (Corrigido para não quebrar sem Redis)
2. `routes/debug.php` - ✅ (Rota de debug temporária)

## 🎓 Lições Aprendidas

1. **OPcache é agressivo** - Mesmo alterando arquivos, o bytecode cacheado continua sendo servido
2. **Sempre reiniciar PHP-FPM** após mudanças estruturais no código
3. **Laravel cache** vs **OPcache** - São caches diferentes:
   - `php artisan cache:clear` - Limpa cache de aplicação Laravel
   - `php artisan optimize:clear` - Limpa caches de config, routes, views
   - `systemctl restart php-fpm` - Reinicia processo e limpa OPcache

## 🗑️ Limpeza Futura

### Remover Arquivo de Debug:
```bash
# Quando tudo estiver funcionando:
rm routes/debug.php
```

### Remover Middleware Temporário (se existir):
```bash
# Já verificado - NÃO está registrado no Kernel.php
# Pode deletar o arquivo se quiser:
rm app/Http/Middleware/FixInertiaProps.php
```

## ✅ Checklist Final

- [x] Código correto nos Use Cases
- [x] Código correto nos Controllers
- [x] Código correto nos Vue Components
- [x] Frontend reconstruído (`npm run build`)
- [x] Laravel caches limpos (`optimize:clear`)
- [ ] **PHP-FPM reiniciado** ← VOCÊ PRECISA FAZER ISSO
- [ ] Páginas testadas no navegador
- [ ] Console sem erros de prop type
- [ ] Dados renderizando corretamente

## 🆘 Se Ainda Não Funcionar

1. **Verifique processo PHP-FPM:**
   ```bash
   ps aux | grep php-fpm | grep -v grep
   # Deve mostrar processo NOVO (hoje)
   ```

2. **Teste rota de debug:**
   ```bash
   # Faça login primeiro no navegador, depois:
   curl -b cookies.txt http://localhost:8001/debug/movimentacoes
   ```

3. **Verifique logs Laravel:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Verifique logs PHP-FPM:**
   ```bash
   sudo tail -f /var/log/php8.3-fpm.log
   ```

## 📞 Suporte

Se após reiniciar o PHP-FPM o problema persistir:

1. Capture screenshot do erro no console
2. Execute: `php artisan route:list | grep tdap`
3. Verifique network tab no navegador
4. Envie os logs

---

**Criado em:** 2025-12-27
**Status:** Código corrigido, aguardando restart do PHP-FPM
