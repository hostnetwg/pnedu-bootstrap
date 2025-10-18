# 🖥️ Development Environment Information

> **This file is for reference and AI assistance. It describes the exact development setup for this project.**

## 📍 Environment Overview

### Operating System
- **Platform**: Windows 10/11
- **Subsystem**: WSL2 (Windows Subsystem for Linux)
- **WSL Distribution**: Ubuntu/Debian-based Linux
- **Kernel**: 6.6.87.2-microsoft-standard-WSL2

### Containerization
- **Container Engine**: Docker Desktop for Windows (WSL2 backend)
- **Orchestration**: Docker Compose
- **Laravel Integration**: Laravel Sail

### Project Location
- **WSL Path**: `/home/hostnet/WEB-APP/pnedu-bootstrap`
- **Windows Path**: Accessible via `\\wsl$\Ubuntu\home\hostnet\WEB-APP\pnedu-bootstrap`

## 🛠️ Technology Stack Details

### Backend Stack
| Component | Version | Purpose |
|-----------|---------|---------|
| PHP | 8.4 | Server-side language |
| Laravel | 11.x | PHP framework |
| Composer | Latest | PHP dependency manager |

### Frontend Stack
| Component | Version | Purpose |
|-----------|---------|---------|
| Bootstrap | 5.2.3 | CSS framework |
| Vite | 6.0.11 | Build tool & dev server |
| Sass | 1.56.1 | CSS preprocessor |
| JavaScript | ES6+ | Client-side scripting |

### Database & Cache
| Component | Version | Purpose |
|-----------|---------|---------|
| MySQL | 8.0 | Relational database |
| Redis | Alpine | Cache & session store |

### Development Services
| Service | Version | Purpose |
|---------|---------|---------|
| PHPMyAdmin | Latest | Database GUI |
| Mailpit | Latest | Email testing |
| Selenium | Chromium | Browser testing |

## 🐳 Docker Configuration

### Container Architecture
```
┌─────────────────────────────────────────────┐
│           Docker Desktop (WSL2)             │
├─────────────────────────────────────────────┤
│                                             │
│  ┌──────────┐  ┌─────────┐  ┌───────────┐ │
│  │ Laravel  │  │  MySQL  │  │   Redis   │ │
│  │   :80    │  │  :3306  │  │   :6379   │ │
│  └──────────┘  └─────────┘  └───────────┘ │
│                                             │
│  ┌──────────┐  ┌─────────┐  ┌───────────┐ │
│  │PHPMyAdmin│  │ Mailpit │  │ Selenium  │ │
│  │  :8082   │  │  :8025  │  │           │ │
│  └──────────┘  └─────────┘  └───────────┘ │
│                                             │
└─────────────────────────────────────────────┘
           ↓                    ↓
    Windows Host          WSL2 Host
    localhost:8081       localhost:8081
```

### Network Configuration
- **Network Name**: sail
- **Network Type**: bridge
- **Host Access**: All services accessible from both WSL and Windows

## 🔌 Port Mappings

| Container Port | Host Port | Service | Access URL |
|----------------|-----------|---------|------------|
| 80 | 8081 | Laravel App | http://localhost:8081 |
| 80 | 8082 | PHPMyAdmin | http://localhost:8082 |
| 3306 | 3306 | MySQL | localhost:3306 |
| 6379 | 6379 | Redis | localhost:6379 |
| 8025 | 8025 | Mailpit UI | http://localhost:8025 |
| 1025 | 1026 | Mailpit SMTP | localhost:1026 |
| 5174 | 5174 | Vite Dev | http://localhost:5174 |

## 📦 Volume Mappings

```yaml
Host Directory              → Container Directory
────────────────────────────────────────────────
./                          → /var/www/html
./mysql-backups             → /backup
sail-mysql (named volume)   → /var/lib/mysql
sail-redis (named volume)   → /data
```

## 🔧 Laravel Sail Commands

### Why "sail" prefix is REQUIRED

Laravel Sail is a wrapper around Docker Compose that:
1. Executes commands **inside Docker containers**
2. Ensures proper environment variables
3. Uses correct PHP version (8.4)
4. Accesses containerized services (MySQL, Redis)

**Without `sail` prefix**:
- Commands run on host machine (Windows/WSL)
- May use wrong PHP version
- Cannot connect to containerized database
- Missing Laravel environment variables

**Example**:
```bash
# ❌ WRONG - Runs on host, may fail
php artisan migrate

# ✅ CORRECT - Runs in container with proper setup
sail artisan migrate
```

## 🔑 Environment Variables

Key environment variables (from `.env`):

```bash
APP_NAME="PNEDU Bootstrap"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8081

DB_CONNECTION=mysql
DB_HOST=mysql                # ← Container hostname (not localhost!)
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

REDIS_HOST=redis             # ← Container hostname
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit           # ← Container hostname
MAIL_PORT=1025
```

### Important Notes:
- `DB_HOST=mysql` (not `localhost`) - Uses Docker network hostname
- `REDIS_HOST=redis` - Container name in Docker network
- `MAIL_HOST=mailpit` - Local email testing

## 🖥️ Shell Environment

### Default Shell
```bash
Shell: /bin/bash
```

### Recommended Shell Configuration

Add to `~/.bashrc`:
```bash
# Laravel Sail alias
alias sail='./vendor/bin/sail'

# Quick navigation
alias cdpnedu='cd /home/hostnet/WEB-APP/pnedu-bootstrap'

# Quick start
alias start-pnedu='cd /home/hostnet/WEB-APP/pnedu-bootstrap && sail up -d'
```

## 📁 File System Considerations

### WSL2 File System
- **Best Performance**: Keep project in WSL file system (`/home/...`)
- **Avoid**: Windows file system (`/mnt/c/...`) - slower I/O
- **Current Location**: ✅ `/home/hostnet/WEB-APP/pnedu-bootstrap`

### File Permissions
```bash
# Recommended ownership
chown -R $USER:$USER .

# Writable directories
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

## 🚀 Typical Development Session

```bash
# 1. Navigate to project (in WSL terminal)
cd /home/hostnet/WEB-APP/pnedu-bootstrap

# 2. Start Docker containers
sail up -d

# 3. In one terminal: Run Vite dev server
sail npm run dev

# 4. In another terminal: Monitor logs (optional)
sail artisan pail

# 5. Access application
# Open browser: http://localhost:8081

# 6. Make changes to code...
# Vite will auto-reload

# 7. When done for the day
sail down
```

## 🔍 Service Health Checks

### Check if services are running:
```bash
# All containers
sail ps

# Specific service status
docker ps | grep mysql
docker ps | grep redis

# Test database connection
sail mysql -e "SELECT 1"

# Test Redis connection
sail redis-cli ping
```

## 🐛 Debugging Environment

### Container Access
```bash
# Access Laravel container bash
sail shell

# Execute one-off commands
sail exec laravel.test ls -la

# Access MySQL container
sail exec mysql bash

# Access Redis CLI
sail redis-cli
```

### Logs
```bash
# All container logs
sail logs

# Follow logs
sail logs -f

# Specific service
sail logs mysql
sail logs redis

# Laravel application logs
sail artisan pail
# or
cat storage/logs/laravel.log
```

## 💻 IDE/Editor Configuration

### Cursor AI
- ✅ `.cursorrules` configured
- ✅ `.cursorignore` excludes large files
- ✅ Context aware of Docker/Sail environment

### VS Code (if used)
Recommended extensions:
- Laravel Extension Pack
- Docker
- WSL
- Bootstrap Snippets

## 🔄 Update Strategy

### Updating Dependencies
```bash
# PHP packages
sail composer update

# JavaScript packages
sail npm update

# Laravel framework
sail composer update laravel/framework
```

### Updating Docker Images
```bash
# Stop containers
sail down

# Pull latest images
docker pull mysql/mysql-server:8.0
docker pull redis:alpine

# Rebuild Sail images
sail build --no-cache

# Start fresh
sail up -d
```

## 📊 System Resources

### Typical Resource Usage
- **Docker Memory**: 2-4 GB
- **Docker CPU**: 2-4 cores
- **Disk Space**: ~5 GB (with node_modules & vendor)

### Docker Desktop Settings
Recommended minimum:
- Memory: 4 GB
- CPUs: 2
- Swap: 1 GB

## 🔐 Security Considerations

### Development Environment Security
- ✅ Services only accessible via localhost
- ✅ Database not exposed to internet
- ✅ `.env` file not committed to Git
- ⚠️ Debug mode enabled (only for development)

### Production Differences
When deploying to production:
- Use proper database credentials
- Disable debug mode (`APP_DEBUG=false`)
- Use environment-specific `.env`
- Configure proper cache/session drivers
- Set up proper backup strategy

## 🎯 AI Assistant Guidelines

### For AI Code Assistants (Cursor, GitHub Copilot, etc.)

When suggesting commands or code changes:

1. **ALWAYS** use `sail` prefix for:
   - `php` → `sail php`
   - `composer` → `sail composer`
   - `artisan` → `sail artisan`
   - `mysql` → `sail mysql`
   - `npm` → `sail npm`

2. **Database connections** in code use:
   ```php
   // ✅ CORRECT - Uses container hostname
   DB_HOST=mysql
   
   // ❌ WRONG
   DB_HOST=localhost
   DB_HOST=127.0.0.1
   ```

3. **File paths** should use:
   - Container paths: `/var/www/html/...`
   - Or relative paths from project root

4. **Bootstrap 5.2.3** specific:
   - Use Bootstrap 5 syntax (not Bootstrap 4)
   - Components available: all Bootstrap 5.2 components
   - Utilities: full Bootstrap 5.2 utility API

## 📚 Additional Resources

- Project documentation: [`README.md`](./README.md)
- Development guide: [`DEVELOPMENT.md`](./DEVELOPMENT.md)
- Quick reference: [`QUICK-REFERENCE.md`](./QUICK-REFERENCE.md)
- Cursor AI rules: [`.cursorrules`](./.cursorrules)
- Aliases setup: [`sail-aliases.sh`](./sail-aliases.sh)

---

**Last Updated**: October 2025  
**Maintained By**: Project Team  
**Purpose**: AI Assistant Context & Developer Reference

