# Bug: Vite/Rollup nao inicia no Docker

## Erro
```
Error: Cannot find module @rollup/rollup-linux-x64-musl
```

## Causa Raiz
O Rollup (usado pelo Vite) requer binarios nativos especificos para cada plataforma:
- Windows: `@rollup/rollup-win32-x64-msvc`
- Linux Alpine (Docker): `@rollup/rollup-linux-x64-musl`

Quando `node_modules` e instalado no Windows e montado no Docker (Linux), os binarios sao incompativeis.

## Problemas Identificados

### 1. Volume compartilhado Windows/Linux
O volume `../:/var/www:cached` monta o codigo do Windows no container Linux, incluindo `node_modules` com binarios Windows.

### 2. Rede Docker sem acesso externo
Os containers na rede `sdc_network` nao conseguem acessar a internet:
```bash
docker exec newsdc_node wget -q --spider https://registry.npmjs.org
# Timeout - sem conexao
```

Comunicacao interna funciona:
```bash
docker exec newsdc_app ping newsdc_db  # OK
docker exec newsdc_app ping newsdc_redis  # OK
```

## Solucao Aplicada

### docker-compose.yml
```yaml
node:
  volumes:
    - ../:/var/www:cached
    - newsdc_node_modules:/var/www/node_modules  # Volume isolado

  command: sh -c "npm install && rm -rf node_modules/.vite && npm run dev -- --host 0.0.0.0 --port 5175"

volumes:
  newsdc_node_modules:
    driver: local
```

### Como funciona
1. Volume nomeado `newsdc_node_modules` isola o `node_modules` do container
2. `npm install` executa no container (Linux) e baixa binarios corretos
3. O volume persiste entre restarts

## Acao Necessaria

### Resolver problema de rede Docker
O container precisa de acesso a internet para `npm install`. Verificar:

1. **VPN corporativa** - Desconectar temporariamente
2. **Proxy** - Configurar no Docker Desktop ou no container
3. **Firewall** - Liberar acesso para containers
4. **Docker Desktop** - Reiniciar o servico

### Teste de conectividade
```bash
docker exec newsdc_node wget -q --spider https://registry.npmjs.org && echo "OK" || echo "FALHA"
```

### Apos resolver a rede
```bash
cd docker
docker compose up -d node --force-recreate
docker logs -f newsdc_node
```

## Referencias
- [npm bug #4828](https://github.com/npm/cli/issues/4828) - Bug de dependencias opcionais
- [Rollup native bindings](https://github.com/rollup/rollup/blob/master/docs/faqs.md)
