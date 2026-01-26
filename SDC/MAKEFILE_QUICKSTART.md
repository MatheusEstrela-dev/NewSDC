# Guia Rapido - Makefile NewSDC

## Inicio Rapido

### 1. Primeira Vez - Setup Completo
```bash
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC
make setup
```

Este comando executa:
- Build de todos os containers
- Sobe toda a stack de desenvolvimento
- Roda migrations do banco de dados
- Otimiza o Laravel

### 2. Desenvolvimento Diario

**Iniciar ambiente:**
```bash
make dev
```

**Ver logs:**
```bash
make logs
make logs-app
```

**Acessar shell do container:**
```bash
make shell
```

**Parar ambiente:**
```bash
make clean
```

### 3. Comandos Essenciais

**Banco de Dados:**
```bash
make db-migrate        # Rodar migrations
make db-seed           # Rodar seeders
make db-backup         # Fazer backup
make db-fresh          # Reset completo (CUIDADO: apaga dados!)
```

**Cache:**
```bash
make cache-clear       # Limpar todos os caches
```

**Status:**
```bash
make status            # Ver status dos containers
make health            # Verificar saude dos containers
make urls              # Mostrar todas as URLs disponiveis
```

**Frontend:**
```bash
make npm-install       # Instalar dependencias
make npm-dev           # Iniciar Vite dev server
make npm-build         # Build para producao
```

### 4. Monitoramento

**Iniciar stack de monitoramento:**
```bash
make monitor
```

**Acessar:**
- Grafana: http://localhost:3000 (admin/admin@123)
- Prometheus: http://localhost:9090
- Alertmanager: http://localhost:9093

**Parar monitoramento:**
```bash
make monitor-down
```

### 5. Limpeza

**Parar containers:**
```bash
make clean
```

**Parar e remover volumes (APAGA DADOS!):**
```bash
make clean-volumes
```

**Limpeza completa do Docker:**
```bash
make clean-system
```

**Destruicao total (CUIDADO!):**
```bash
make nuke
```

### 6. Producao

**Deploy completo:**
```bash
make deploy-prod
```

**Build producao:**
```bash
make prod-build
```

**Ver logs producao:**
```bash
make prod-logs
```

### 7. Vite (Frontend)

IMPORTANTE: O Vite roda no HOST, nao no container!

**Iniciar Vite em terminal separado:**
```bash
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC
npm run dev
```

**Acessar:**
- Vite HMR: http://localhost:5173

### 8. URLs Principais

Apos executar `make dev`:
- Aplicacao Laravel: http://localhost:8001
- Nginx: http://localhost:8082
- Mailhog: http://localhost:8026
- MySQL: localhost:3307 (user: sdc, pass: secret)
- Redis: localhost:6380

### 9. Credenciais de Teste

**Usuario criado:**
- CPF: 123.456.789-00
- Email: teste@sdc.gov.br
- Senha: password

### 10. Ajuda

**Ver todos os comandos disponiveis:**
```bash
make help
make
```

**Ver informacoes do sistema:**
```bash
make info
```

## Workflow Completo - Exemplo

### Primeiro Dia
```bash
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC
make setup          # Setup inicial (demora uns minutos)
npm run dev         # Em outro terminal - iniciar Vite
```

Acessar: http://localhost:8001/login
Login: 123.456.789-00 / password

### Dia a Dia
```bash
make dev            # Iniciar containers
make logs-app       # Ver o que esta acontecendo
make shell          # Debugar algo
npm run dev         # Nao esqueca do Vite!
make clean          # Finalizar o dia
```

### Quando der problema
```bash
make status         # Ver o que esta quebrado
make logs           # Ver todos os logs
make clean          # Parar tudo
make dev-build      # Rebuild e reiniciar
```

### Trabalhar no banco
```bash
make shell-db       # Acessar MySQL CLI
make db-migrate     # Rodar nova migration
make db-backup      # Antes de fazer algo arriscado!
```

## Troubleshooting

### Containers nao iniciam
```bash
make status
make logs
make clean
make dev-build
```

### Erro de porta ocupada
```bash
netstat -ano | findstr "8001 8082 3307"
# Matar processo que esta usando a porta
make clean
make dev
```

### Vite nao conecta
1. Verificar se Vite esta rodando: `npm run dev`
2. Acessar http://localhost:5173
3. Verificar nginx logs: `make logs-app`

### Problema de cache
```bash
make cache-clear
make dev-build
```

### Reset total
```bash
make nuke           # CUIDADO: apaga tudo!
make setup          # Recomecar do zero
```

## Dicas

1. **Use `make help`** para ver todos os comandos
2. **Vite no HOST** sempre - melhor performance
3. **Backup antes** de `db-fresh` ou `nuke`
4. **Monitor com Grafana** para ver metricas
5. **Logs sempre** quando tiver duvida

## Suporte

Consulte a documentacao completa:
- MAKEFILE_GUIDE.md - Guia detalhado
- WINDOWS_SETUP.md - Setup Windows
- DOCKER_SETUP_COMPLETE.md - Setup completo Docker

---

SDC - Sistema de Defesa Civil
