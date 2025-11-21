# Sistema de Backup - SDC

## Descrição

Sistema automatizado de backup do banco de dados MySQL com retenção configurável.

## Configuração

O backup é executado automaticamente via cron dentro do container `sdc_backup`. Por padrão, os backups são executados diariamente às 2:00 AM.

### Variáveis de Ambiente

- `MYSQL_HOST`: Host do MySQL (padrão: `db`)
- `MYSQL_DATABASE`: Nome do banco de dados (padrão: `sdc_db`)
- `MYSQL_USER`: Usuário do MySQL (padrão: `root`)
- `MYSQL_PASSWORD`: Senha do MySQL
- `BACKUP_SCHEDULE`: Agendamento do cron (padrão: `0 2 * * *` - diariamente às 2:00 AM)
- `BACKUP_RETENTION_DAYS`: Dias de retenção dos backups (padrão: `7`)

## Uso

### Backup Automático

O backup é executado automaticamente conforme o agendamento configurado. Os arquivos são salvos em `/backup/data` dentro do container.

### Backup Manual

Para executar um backup manual:

```bash
docker exec sdc_backup /backup/backup.sh
```

### Listar Backups

```bash
docker exec sdc_backup ls -lh /backup/data
```

### Restaurar Backup

Para restaurar um backup:

```bash
# Listar backups disponíveis
docker exec sdc_backup ls -lh /backup/data

# Restaurar um backup específico
docker exec sdc_backup /backup/restore.sh sdc_db_20251121_020000.sql.gz
```

### Ver Logs do Backup

```bash
docker logs sdc_backup
```

### Verificar Status do Cron

```bash
docker exec sdc_backup crontab -l
```

## Estrutura dos Arquivos

- `backup.sh`: Script principal de backup
- `restore.sh`: Script para restaurar backups
- `/backup/data/`: Diretório onde os backups são armazenados
- `/backup/backup.log`: Log dos backups executados

## Formato dos Backups

Os backups são salvos no formato:
```
{DB_NAME}_{TIMESTAMP}.sql.gz
```

Exemplo: `sdc_db_20251121_020000.sql.gz`

## Retenção

Por padrão, backups com mais de 7 dias são automaticamente removidos. Isso pode ser configurado através da variável `BACKUP_RETENTION_DAYS`.

