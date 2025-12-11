# Rede Bridge SDC - Conectividade entre Containers

## ✅ Status: Todos os containers estão na mesma rede bridge

**Rede:** `sdc-dev_sdc_network` (bridge)

## 📋 Containers na Rede

Todos os containers abaixo estão conectados à mesma rede bridge e podem se comunicar entre si usando os **hostnames**:

| Container         | Hostname  | Porta Interna | Porta Externa | Status     |
| ----------------- | --------- | ------------- | ------------- | ---------- |
| **Jenkins**       | `jenkins` | 8080          | 8090          | ✅ Rodando |
| **App (Laravel)** | `app`     | 8000          | 8000          | ✅ Rodando |
| **Nginx**         | `nginx`   | 80, 443       | 80, 443       | ✅ Rodando |
| **MySQL**         | `db`      | 3306          | 3306          | ✅ Rodando |
| **Redis**         | `redis`   | 6379          | 6379          | ✅ Rodando |
| **Mailhog**       | `mailhog` | 1025, 8025    | 1025, 8025    | ✅ Rodando |
| **Node.js**       | `node`    | 5173          | 5173          | ✅ Rodando |

## 🔗 Como os Containers se Comunicam

### Do Jenkins para outros serviços:

```bash
# Acessar aplicação Laravel
http://app:8000

# Acessar banco de dados MySQL
mysql://db:3306

# Acessar Redis
redis://redis:6379

# Acessar Mailhog SMTP
mailhog:1025

# Acessar Node.js/Vite
http://node:5173
```

### Do App (Laravel) para outros serviços:

```env
# .env do Laravel já configurado:
DB_HOST=db
REDIS_HOST=redis
MAIL_HOST=mailhog
```

### Do Nginx para App:

```nginx
# Configuração nginx já aponta para:
proxy_pass http://app:8000;
```

## 🧪 Teste de Conectividade

### Teste realizado:

```bash
# Do Jenkins para App
docker exec sdc_jenkins_dev curl http://app:8000
# Resultado: ✅ 302 (conexão funcionando)
```

## 📝 Exemplos de Uso no Jenkins

### 1. Pipeline que acessa o banco de dados:

```groovy
stage('Test Database') {
    steps {
        sh '''
            mysql -h db -u sdc -psecret sdc -e "SELECT 1"
        '''
    }
}
```

### 2. Pipeline que testa a API:

```groovy
stage('Test API') {
    steps {
        sh '''
            curl -f http://app:8000/api/health || exit 1
        '''
    }
}
```

### 3. Pipeline que usa Redis:

```groovy
stage('Cache Test') {
    steps {
        sh '''
            redis-cli -h redis ping
        '''
    }
}
```

## 🔧 Configuração da Rede

A rede está configurada no `docker-compose.yml`:

```yaml
networks:
    sdc_network:
        driver: bridge
        ipam:
            driver: default
            config:
                - subnet: 172.25.0.0/16
```

**Todos os serviços usam:**

```yaml
networks:
    - sdc_network
```

## ✅ Verificação

Para verificar todos os containers na rede:

```bash
docker network inspect sdc-dev_sdc_network
```

Para listar containers conectados:

```bash
docker ps --filter "network=sdc-dev_sdc_network"
```

## 🎯 Resumo

✅ **Todos os containers estão na mesma rede bridge**  
✅ **Comunicação por hostname funcionando**  
✅ **Jenkins pode acessar todos os serviços**  
✅ **App pode acessar DB, Redis e Mailhog**  
✅ **Nginx pode fazer proxy para App**

**A rede está 100% funcional para comunicação entre containers!**



