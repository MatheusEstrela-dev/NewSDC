# Windows Setup Guide for SDC Docker Environment

## Prerequisites

### 1. Docker Desktop
- Download and install from [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop)
- Ensure Docker Desktop is running before starting containers

### 2. Make for Windows (REQUIRED for Makefile)

Choose ONE of the following methods to install Make:

#### Option A: Chocolatey (Recommended)
```powershell
# Install Chocolatey (if not installed)
# Run in PowerShell as Administrator
Set-ExecutionPolicy Bypass -Scope Process -Force
[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

# Install Make
choco install make -y

# Verify installation
make --version
```

#### Option B: Winget (Windows Package Manager)
```powershell
# Run in PowerShell or CMD
winget install GnuWin32.Make

# Add to PATH (may require restart)
# C:\Program Files (x86)\GnuWin32\bin
```

#### Option C: Git Bash (comes with Git for Windows)
```bash
# If you have Git for Windows, you may already have mingw32-make
# Test with:
mingw32-make --version

# If available, create an alias:
echo "alias make='mingw32-make'" >> ~/.bashrc
source ~/.bashrc
```

#### Option D: Manual Download
1. Download Make from [GnuWin32](http://gnuwin32.sourceforge.net/packages/make.htm)
2. Install to `C:\Program Files (x86)\GnuWin32`
3. Add to PATH: `C:\Program Files (x86)\GnuWin32\bin`
4. Restart terminal and verify: `make --version`

## Quick Start

### Method 1: Using Makefile (Recommended)

After installing Make, navigate to the project directory:

```bash
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC

# See all available commands
make help

# Start all containers (default development setup)
make dev

# Start all containers INCLUDING monitoring stack
make up-all

# View logs
make logs

# Access container shell
make shell

# Run migrations
make db-migrate

# Stop all containers
make clean
```

### Method 2: Using Windows Batch Scripts

If you don't want to install Make, use the batch scripts:

```cmd
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC\docker

# Start all containers
start-dev.bat

# Stop all containers
stop-all.bat
```

### Method 3: Using PowerShell Scripts

```powershell
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC\docker

# Start all containers
.\start-dev.ps1

# Stop all containers
.\stop-all.ps1
```

### Method 4: Direct Docker Compose

```bash
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC\docker

# Start development environment
docker compose -f docker-compose.yml up -d

# Stop all
docker compose -f docker-compose.yml down
```

## Starting Vite (Frontend)

IMPORTANT: Vite runs on the HOST machine, not in a container.

### Option 1: Separate Terminal
```bash
# Open a NEW terminal
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC

# Install dependencies (first time only)
npm install --legacy-peer-deps

# Start Vite dev server
npm run dev
```

### Option 2: Background Process (PowerShell)
```powershell
cd C:\Users\x24679188\Documents\GitHub\NewSDC\SDC
Start-Process npm -ArgumentList "run dev" -WindowStyle Minimized
```

## Makefile Commands Reference

### Essential Commands
```bash
make help          # Show all available commands
make dev           # Start development environment
make up-all        # Start everything (with monitoring)
make down-all      # Stop everything
make status        # Show container status
make logs          # View all logs
make urls          # Show all available URLs
```

### Database Commands
```bash
make db-migrate    # Run migrations
make db-seed       # Run seeders
make db-backup     # Backup database
make db-fresh      # Fresh migrations + seed
```

### Container Access
```bash
make shell         # App container shell
make shell-db      # MySQL CLI
make shell-redis   # Redis CLI
make shell-queue   # Queue worker shell
```

### Cleanup
```bash
make clean         # Stop and remove containers
make clean-volumes # Remove containers + volumes (deletes data!)
make nuke          # Complete cleanup (USE WITH CAUTION!)
```

### Monitoring
```bash
make monitor       # Start monitoring stack
make monitor-down  # Stop monitoring
```

## Available Services

After running `make dev` or `start-dev.bat`, the following services are available:

### Application
- Laravel App: http://localhost:8001
- Nginx: http://localhost:8082
- Mailhog (Email Testing): http://localhost:8026

### Database
- MySQL: localhost:3307 (user: sdc, pass: secret)
- Redis: localhost:6380

### Development Tools (with `make dev-full` or `--profile tools`)
- phpMyAdmin: http://localhost:8083
- Redis Commander: http://localhost:8084

### Monitoring (with `make up-all` or `make monitor`)
- Grafana: http://localhost:3000 (admin/admin@123)
- Prometheus: http://localhost:9090
- Alertmanager: http://localhost:9093
- cAdvisor: http://localhost:8080

### Frontend (Run on HOST)
- Vite HMR: http://localhost:5173 (after running `npm run dev`)

## Troubleshooting

### Make command not found
- Install Make using one of the methods above
- Restart your terminal after installation
- Verify with: `make --version`

### Docker containers fail to start
```bash
# Check Docker is running
docker version

# Check network
docker network ls | grep sdc_network

# Create network manually if needed
docker network create --driver bridge --subnet 172.26.0.0/16 sdc_network

# Check for port conflicts
netstat -ano | findstr "8001 8082 3307 6380 5173"
```

### Vite not connecting to Laravel
1. Ensure Vite is running: `npm run dev`
2. Check Vite URL: http://localhost:5173
3. Verify Laravel is running: http://localhost:8001
4. Check .env file has correct Vite configuration

### Permission issues
- Windows Docker Desktop handles permissions differently
- UID/GID are set to 1000 by default in Makefile
- Usually not an issue on Windows

### Slow performance
- Ensure WSL2 backend is enabled in Docker Desktop
- Allocate more resources in Docker Desktop settings
- Use named volumes for node_modules and vendor (already configured)

## Tips

1. **Use Make**: The Makefile is the recommended way to manage containers
2. **Keep Vite on HOST**: Better HMR performance and easier debugging
3. **Use Docker Dashboard**: Docker Desktop GUI is helpful for monitoring
4. **Check logs**: Use `make logs` or `make logs-app` for debugging
5. **Regular cleanup**: Use `make clean` to free up resources

## Next Steps

1. Install Make for Windows
2. Run `make dev` to start containers
3. Open new terminal and run `npm run dev` for Vite
4. Access application at http://localhost:8001
5. Develop and enjoy!

## Support

For issues or questions:
1. Check `make help` for available commands
2. View logs: `make logs`
3. Check Docker Desktop dashboard
4. Verify all prerequisites are installed
