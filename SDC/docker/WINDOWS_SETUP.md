# Windows Setup

Requisitos:

- Docker Desktop
- Git
- Bun no host para Vite
- Just opcional

## Subir ambiente dev

Da raiz do repositorio:

```powershell
just dev-build
just dev-up
```

Ou diretamente:

```powershell
docker compose -f SDC\docker\compose.dev.yml build app
docker compose -f SDC\docker\compose.dev.yml up -d
```

URLs:

- App: http://localhost:19444
- Mailhog: http://localhost:8025
- PostgreSQL: localhost:5433
- Redis: localhost:6380

## Vite

```powershell
cd SDC
bun run dev
```
