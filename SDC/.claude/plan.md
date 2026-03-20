# Plano: Configurar Hot Reload do Vite no Docker

## Diagnóstico

| Item | Status | Observação |
|------|--------|------------|
| Container `node` | Rodando | Vite dev server ativo |
| Arquivo `public/hot` | Existe | Aponta para `http://localhost:15175` |
| `INERTIA_SSR_ENABLED` no app | `true` | **PROBLEMA** - ignora hot reload |
| `.env` local | `false` | Não está sendo usado pelo Docker |

## Problema Principal

O `docker-compose.yml` tem `INERTIA_SSR_ENABLED: "true"` hardcoded. Isso faz o Laravel usar build estático ao invés do Vite dev server, ignorando o arquivo `hot`.

## Solução

### Opção 1: Usar variável de ambiente do host (Recomendado)

Modificar `docker-compose.yml` para ler do `.env`:

```yaml
environment:
  INERTIA_SSR_ENABLED: ${INERTIA_SSR_ENABLED:-false}
```

### Opção 2: Criar docker-compose.dev.yml

Override específico para desenvolvimento que desabilita SSR.

## Implementação

1. Editar `docker/docker-compose.yml` linha 56
2. Reiniciar container app: `docker compose restart app`
3. Hot reload funcionará automaticamente

## Comandos Pós-Implementação

```bash
# Reiniciar app para pegar nova config
docker compose -f docker/docker-compose.yml restart app

# Testar hot reload
# - Edite qualquer arquivo .vue
# - Browser deve atualizar automaticamente
```
