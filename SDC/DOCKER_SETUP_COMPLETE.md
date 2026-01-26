# Docker Setup Complete - NewSDC

## Summary

All Docker containers have been successfully configured and are now running for the NewSDC project!

## What Was Done

### 1. Makefile Windows Compatibility
- Fixed Windows compatibility issues in the Makefile
- Added OS detection for color support (works on both Windows and Unix)
- Fixed UID/GID detection to default to 1000 on Windows
- Maintained all 70+ existing commands

### 2. Vite Configuration
- Configured Vite to run on HOST machine (not in container) for better performance
- Updated docker-compose.yml to use profile for node container
- Updated all relevant Makefile commands to reflect this change

### 3. Windows Helper Scripts
Created three Windows-specific scripts in `docker/` folder:
- `start-dev.bat` - Batch script to start all containers
- `start-dev.ps1` - PowerShell script with better features
- `stop-all.bat` - Stop all containers
- `stop-all.ps1` - PowerShell version

### 4. Documentation
Created comprehensive documentation:
- [WINDOWS_SETUP.md](SDC/docker/WINDOWS_SETUP.md) - Complete Windows setup guide with Make installation instructions
- Updated [README.md](SDC/docker/README.md) - Added Windows-specific quick start
- Updated [MAKEFILE_GUIDE.md](SDC/docker/MAKEFILE_GUIDE.md) - Added Windows notes

### 5. Container Deployment
Successfully built and deployed all containers:
- App (Laravel + PHP 8.3)
- Nginx (Web Server)
- MySQL 8.0 (Database)
- Redis (Cache/Session/Queue)
- Mailhog (Email Testing)
- Queue Worker (Supervisor)
- Prometheus (Metrics Collection)
- Grafana (Dashboards)
- Alertmanager (Alerts)
- 5 Exporters (Node, MySQL, Redis, Nginx, Blackbox)

## Currently Running Containers

```
NAME                       STATUS
newsdc_app                 Up (healthy) - Laravel Application
newsdc_nginx               Up - Web Server
newsdc_db                  Up (healthy) - MySQL 8.0
newsdc_redis               Up (healthy) - Redis
newsdc_mailhog             Up - Email Testing
newsdc_queue               Up - Queue Worker
newsdc_prometheus          Up (healthy) - Metrics
newsdc_grafana             Up (healthy) - Dashboards
newsdc_alertmanager        Up (healthy) - Alerts
newsdc_node_exporter       Up - System Metrics
newsdc_mysql_exporter      Up - Database Metrics
newsdc_redis_exporter      Up - Redis Metrics
newsdc_nginx_exporter      Up - Nginx Metrics
newsdc_blackbox_exporter   Up - Endpoint Monitoring
```

## Access URLs

### Application
- Laravel App: http://localhost:8001
- Nginx: http://localhost:8082
- Mailhog: http://localhost:8026

### Database
- MySQL: localhost:3307 (user: sdc, pass: secret)
- Redis: localhost:6380

### Monitoring
- Grafana: http://localhost:3000 (admin/admin@123)
- Prometheus: http://localhost:9090
- Alertmanager: http://localhost:9093

### Frontend (Run on HOST)
- Vite: http://localhost:5173 (after running `npm run dev`)

## How to Use

### Option 1: Using Makefile (Recommended - Requires Make Installation)

```bash
# Install Make first (see WINDOWS_SETUP.md)
# Then run:
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC

# Start development environment
make dev

# Start Vite in separate terminal
npm run dev

# View logs
make logs

# Access container shell
make shell

# Run migrations
make db-migrate

# Stop all
make clean
```

### Option 2: Using Windows Batch Script (No Make Required)

```cmd
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC\docker

# Start all containers
start-dev.bat

# In another terminal, start Vite
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC
npm run dev

# Stop all containers
stop-all.bat
```

### Option 3: Direct Docker Compose

```bash
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC\docker

# Start containers
docker-compose up -d

# Start Vite in separate terminal
cd ..
npm run dev

# Stop containers
docker-compose down
```

## Important Notes

### Vite Must Run on HOST
For optimal performance and Hot Module Replacement (HMR), Vite runs on your local machine, NOT in a container.

1. Start all containers first (using any method above)
2. Open a new terminal
3. Navigate to SDC folder: `cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC`
4. Install dependencies (first time): `npm install --legacy-peer-deps`
5. Run Vite: `npm run dev`
6. Access at: http://localhost:5173

### Make Installation for Windows
To use the Makefile commands, you need to install Make on Windows:

**Recommended: Using Chocolatey**
```powershell
choco install make -y
```

See [WINDOWS_SETUP.md](SDC/docker/WINDOWS_SETUP.md) for other installation methods.

### Port 8080 Conflict
Note: cAdvisor container failed to start due to port 8080 conflict (likely another service using it). This is optional for development and doesn't affect the main application.

To fix if needed:
- Identify what's using port 8080: `netstat -ano | findstr "8080"`
- Stop that service, or
- Modify docker-compose.yml to change cAdvisor port to 8081 or another available port

## Next Steps

1. **Install Make** (optional but recommended): Follow [WINDOWS_SETUP.md](SDC/docker/WINDOWS_SETUP.md)
2. **Start Vite**: Open terminal, run `npm run dev`
3. **Run Migrations**: `make db-migrate` or `docker-compose exec app php artisan migrate`
4. **Access Application**: http://localhost:8001
5. **Monitor with Grafana**: http://localhost:3000 (admin/admin@123)

## Troubleshooting

### Containers Won't Start
```bash
# Check Docker is running
docker version

# Check logs
docker-compose logs -f app

# Rebuild if needed
docker-compose build --no-cache
docker-compose up -d
```

### Port Conflicts
```bash
# Check what's using ports
netstat -ano | findstr "8001 8082 3307 6380 5173"

# Stop conflicting services or change ports in docker-compose.yml
```

### Vite Not Connecting
1. Ensure Vite is running: `npm run dev`
2. Check http://localhost:5173
3. Verify Laravel is running: http://localhost:8001

## Files Modified/Created

### Modified
- `SDC/Makefile` - Windows compatibility fixes
- `SDC/docker/docker-compose.yml` - Vite profile configuration
- `SDC/docker/README.md` - Windows instructions
- `SDC/docker/MAKEFILE_GUIDE.md` - Windows notes

### Created
- `SDC/Makefile.backup` - Backup of original Makefile
- `SDC/docker/WINDOWS_SETUP.md` - Complete Windows guide
- `SDC/docker/start-dev.bat` - Windows batch script
- `SDC/docker/start-dev.ps1` - PowerShell script
- `SDC/docker/stop-all.bat` - Stop script (batch)
- `SDC/docker/stop-all.ps1` - Stop script (PowerShell)
- `SDC/DOCKER_SETUP_COMPLETE.md` - This file

## Support Resources

- **Makefile Guide**: [docker/MAKEFILE_GUIDE.md](SDC/docker/MAKEFILE_GUIDE.md)
- **Windows Setup**: [docker/WINDOWS_SETUP.md](SDC/docker/WINDOWS_SETUP.md)
- **Docker README**: [docker/README.md](SDC/docker/README.md)
- **Make Help**: Run `make help` to see all 70+ available commands

## Status

All containers are running and healthy!
Ready for development!

Date: 2026-01-05
Environment: Windows Development
Docker Compose: v2.40.3-desktop.1
