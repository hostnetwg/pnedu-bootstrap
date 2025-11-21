# 🎓 PNEDU Bootstrap - Laravel Application

<p align="center">
<a href="https://laravel.com" target="_blank">
<img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</a>
</p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## 📋 About This Project

Laravel application with Bootstrap 5 frontend framework, running in a Dockerized environment using Laravel Sail.

### 🛠️ Technology Stack

- **Backend**: Laravel 11.x (PHP 8.4)
- **Frontend**: Bootstrap 5.2.3
- **Database**: MySQL 8.0
- **Cache**: Redis
- **Container Management**: Laravel Sail + Docker
- **Development Environment**: Windows WSL2 + Docker

### 🌐 Services & Ports

| Service | Port | URL | Description |
|---------|------|-----|-------------|
| Laravel App | 8081 | http://localhost:8081 | Main application |
| PHPMyAdmin | 8082 | http://localhost:8082 | Database management GUI |
| Mailpit | 8025 | http://localhost:8025 | Email testing interface |
| Vite Dev Server | 5174 | http://localhost:5174 | Frontend hot reload |
| MySQL | 3306 | localhost:3306 | Database server |
| Redis | 6379 | localhost:6379 | Cache server |

## 🚀 Quick Start

### Prerequisites
- Windows 10/11 with WSL2 installed
- Docker Desktop with WSL2 backend enabled
- Git (installed in WSL)

### Installation

1. **Clone the repository**:
```bash
git clone <repository-url>
cd pnedu-bootstrap
```

2. **Copy environment file**:
```bash
cp .env.example .env
```

3. **Start Docker containers**:
```bash
./vendor/bin/sail up -d
```

4. **Install dependencies**:
```bash
sail composer install
sail npm install
```

5. **Generate application key**:
```bash
sail artisan key:generate
```

6. **Run migrations**:
```bash
sail artisan migrate --seed
```

7. **Build frontend assets**:
```bash
sail npm run build
```

8. **Access the application**:
- Open browser: http://localhost:8081

### Creating Sail Alias (Recommended)

Add to your `~/.bashrc` or `~/.zshrc`:
```bash
alias sail='./vendor/bin/sail'
```

Reload shell:
```bash
source ~/.bashrc
```

## 📝 Essential Commands

### Starting & Stopping

```bash
# Start all services
sail up -d

# Stop all services
sail down

# Restart services
sail restart

# View logs
sail logs
```

### Development

```bash
# Run development server with hot reload
sail npm run dev

# Build for production
sail npm run build

# Run tests
sail test

# Access container shell
sail shell

# Monitor logs in real-time
sail artisan pail
```

### Database

```bash
# Access MySQL CLI
sail mysql

# Run migrations
sail artisan migrate

# Rollback migrations
sail artisan migrate:rollback

# Fresh migration with seeding
sail artisan migrate:fresh --seed
```

### Artisan Commands

```bash
# Run any Artisan command
sail artisan [command]

# Examples:
sail artisan make:controller UserController
sail artisan make:model Post -m
sail artisan route:list
sail artisan tinker
```

### Composer & NPM

```bash
# Install PHP package
sail composer require vendor/package

# Install JavaScript package
sail npm install package-name

# Update dependencies
sail composer update
sail npm update
```

## 🎨 Frontend Development

### Bootstrap 5.2.3

The project uses Bootstrap for UI components and styling. Bootstrap is pre-configured and ready to use.

**Key files**:
- `/resources/views/` - Blade templates
- `/resources/js/` - JavaScript files
- `/resources/sass/` - Sass/SCSS files

**Development workflow**:
```bash
# Start Vite dev server (hot reload)
sail npm run dev

# Build for production
sail npm run build
```

## 🗄️ Database Management

### PHPMyAdmin Access
- URL: http://localhost:8082
- Credentials: Use values from your `.env` file

### Automatic Backups
- Daily backups at 2:00 AM
- Location: `./mysql-backups/`
- Retention: 7 days

## 📧 Email Testing

Mailpit is configured for local email testing:
- URL: http://localhost:8025
- All emails sent by the application are captured here
- No emails are actually sent externally

## 🧪 Testing

```bash
# Run all tests
sail test

# Run specific test
sail test --filter=TestName

# Run with coverage
sail test --coverage
```

## 📚 Documentation

- **[DEVELOPMENT.md](./DEVELOPMENT.md)** - Comprehensive development guide
- **[README-COURSES.md](./README-COURSES.md)** - Course-specific documentation
- **[.cursorrules](./.cursorrules)** - Cursor AI configuration

## 🔧 Troubleshooting

### Common Issues

**Containers won't start**:
```bash
sail down
docker system prune -f
sail up -d
```

**Permission issues**:
```bash
sudo chown -R $USER:$USER .
```

**Port conflicts**:
Check if ports 8081, 8082, 3306, 6379 are available or modify `docker-compose.yml`

**Database connection errors**:
```bash
sail down
sail up -d
```

### View Logs
```bash
# All services
sail logs

# Specific service
sail logs mysql
sail logs redis

# Laravel logs
sail artisan pail
# or check: storage/logs/laravel.log
```

## 🔐 Security

- Never commit `.env` file to repository
- Keep dependencies updated regularly
- Use environment variables for sensitive data
- Review `.env.example` for required configuration

## 📦 Project Structure

```
pnedu-bootstrap/
├── app/                    # Application code
│   ├── Http/              # Controllers, Middleware
│   ├── Models/            # Eloquent models
│   └── ...
├── bootstrap/             # Framework bootstrap files
├── config/                # Configuration files
├── database/              # Migrations, Seeders, Factories
├── public/                # Public assets
├── resources/             # Views, raw assets
│   ├── js/               # JavaScript files
│   ├── sass/             # Sass/SCSS files
│   └── views/            # Blade templates
├── routes/                # Route definitions
├── storage/               # Logs, cache, uploads
├── tests/                 # Automated tests
├── vendor/                # Composer dependencies
├── .cursorrules          # Cursor AI configuration
├── docker-compose.yml    # Docker services configuration
├── DEVELOPMENT.md        # Development guide
└── README.md             # This file
```

## 🤝 Contributing

1. Create a feature branch
2. Make your changes
3. Write/update tests
4. Run tests: `sail test`
5. Format code: `sail pint`
6. Commit and push
7. Create pull request

## 📄 License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 🔗 Resources

### Laravel
- [Documentation](https://laravel.com/docs/11.x)
- [Laravel Sail](https://laravel.com/docs/11.x/sail)
- [Blade Templates](https://laravel.com/docs/11.x/blade)
- [Eloquent ORM](https://laravel.com/docs/11.x/eloquent)

### Bootstrap
- [Bootstrap 5.2 Docs](https://getbootstrap.com/docs/5.2/)
- [Bootstrap Examples](https://getbootstrap.com/docs/5.2/examples/)

### Tools
- [Docker Desktop](https://www.docker.com/products/docker-desktop)
- [WSL2 Documentation](https://docs.microsoft.com/en-us/windows/wsl/)

---

## 💡 Working with AI (Cursor)

This project includes `.cursorrules` file that helps AI assistants understand the development environment:

**Key points for AI assistance**:
- ✅ Always use `sail` prefix for Laravel/PHP commands
- ✅ Use Bootstrap 5.2.3 components for UI
- ✅ Follow Laravel 11 conventions
- ✅ Remember we're in Docker/Sail environment
- ❌ Never run `php`, `composer`, `artisan` directly without `sail`

**For detailed development workflows, see [DEVELOPMENT.md](./DEVELOPMENT.md)**

---

**Happy Coding! 🚀**
