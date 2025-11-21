# Development Guide

## 🖥️ Development Environment Setup

### System Requirements
- **Operating System**: Windows 10/11 with WSL2
- **Docker Desktop**: Latest version with WSL2 backend enabled
- **WSL Distribution**: Ubuntu or similar
- **Git**: Installed in WSL

### Technology Stack
- **Backend**: Laravel 11.x (PHP 8.4)
- **Frontend**: Bootstrap 5.2.3
- **Database**: MySQL 8.0
- **Cache**: Redis
- **Mail**: Mailpit (local development)
- **Container Management**: Laravel Sail (Docker)

## 🚀 Getting Started

### Initial Setup

1. **Clone the repository** (if not already done):
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

4. **Install PHP dependencies**:
```bash
sail composer install
```

5. **Install JavaScript dependencies**:
```bash
sail npm install
```

6. **Generate application key**:
```bash
sail artisan key:generate
```

7. **Run database migrations**:
```bash
sail artisan migrate --seed
```

8. **Build frontend assets**:
```bash
sail npm run build
```

### Daily Development Workflow

1. **Start the development environment**:
```bash
sail up -d
```

2. **Run Vite development server** (for hot reload):
```bash
sail npm run dev
```

3. **Monitor logs** (optional, in separate terminal):
```bash
sail artisan pail
```

4. **Stop the environment** (when done):
```bash
sail down
```

## 🎯 Laravel Sail Commands

### Essential Commands

| Task | Command |
|------|---------|
| Start containers | `sail up -d` |
| Stop containers | `sail down` |
| Access container shell | `sail shell` |
| Run Artisan commands | `sail artisan [command]` |
| Run Composer | `sail composer [command]` |
| Run NPM | `sail npm [command]` |
| Access MySQL | `sail mysql` |
| Run tests | `sail test` |
| View logs | `sail logs` |

### Creating Aliases (Optional but Recommended)

Add to your `~/.bashrc` or `~/.zshrc`:
```bash
alias sail='./vendor/bin/sail'
```

Then reload:
```bash
source ~/.bashrc
```

## 🗄️ Database Management

### MySQL Access

**Via Sail CLI**:
```bash
sail mysql
```

**Via PHPMyAdmin**:
- URL: http://localhost:8082
- Username: From `.env` (`DB_USERNAME`)
- Password: From `.env` (`DB_PASSWORD`)

### Database Commands

```bash
# Create a new migration
sail artisan make:migration create_users_table

# Run migrations
sail artisan migrate

# Rollback last migration
sail artisan migrate:rollback

# Rollback all migrations
sail artisan migrate:reset

# Fresh migration (drop all tables and re-run)
sail artisan migrate:fresh

# Fresh migration with seeding
sail artisan migrate:fresh --seed

# Create a seeder
sail artisan make:seeder UserSeeder

# Run seeders
sail artisan db:seed
```

### Backups
- Automatic daily backups configured (2 AM)
- Stored in: `./mysql-backups/`
- Retention: 7 days

## 🎨 Frontend Development (Bootstrap)

### Bootstrap 5.2.3

The project uses Bootstrap as the primary CSS framework:

**Available Bootstrap features**:
- Grid System (12-column)
- Components (buttons, cards, modals, etc.)
- Utilities (spacing, colors, display, etc.)
- Form controls
- Icons (if Bootstrap Icons added)

### Asset Compilation

**Development mode** (with hot reload):
```bash
sail npm run dev
```

**Production build**:
```bash
sail npm run build
```

### File Structure
- `/resources/views/` - Blade templates
- `/resources/js/` - JavaScript files
- `/resources/sass/` - Sass/SCSS files
- `/public/` - Compiled assets

### Creating Views with Bootstrap

Example Blade template:
```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>
                <div class="card-body">
                    <p class="mb-0">Bootstrap is working!</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

## 🧪 Testing

### Running Tests

```bash
# Run all tests
sail test

# Run specific test file
sail test tests/Feature/ExampleTest.php

# Run tests with coverage
sail test --coverage

# Run tests with filter
sail test --filter=test_user_can_login
```

### Creating Tests

```bash
# Create a feature test
sail artisan make:test UserTest

# Create a unit test
sail artisan make:test UserTest --unit
```

## 🔧 Common Development Tasks

### Creating a New Controller

```bash
# Basic controller
sail artisan make:controller UserController

# Resource controller (with CRUD methods)
sail artisan make:controller UserController --resource

# API resource controller
sail artisan make:controller UserController --api
```

### Creating a New Model

```bash
# Model only
sail artisan make:model User

# Model with migration
sail artisan make:model User -m

# Model with migration, controller, and resource
sail artisan make:model User -mcr

# Model with everything (migration, factory, seeder, controller)
sail artisan make:model User -mfsc
```

### Creating Routes

Edit `/routes/web.php` for web routes:
```php
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
```

### Cache Management

```bash
# Clear all caches
sail artisan cache:clear

# Clear config cache
sail artisan config:clear

# Clear route cache
sail artisan route:clear

# Clear view cache
sail artisan view:clear

# Optimize for production
sail artisan optimize
```

## 🐛 Debugging & Troubleshooting

### View Logs

```bash
# Real-time log monitoring (Laravel Pail)
sail artisan pail

# Docker container logs
sail logs

# Specific service logs
sail logs mysql
sail logs redis
```

### Laravel Tinker (REPL)

```bash
sail artisan tinker
```

Examples in Tinker:
```php
// Query users
User::all();

// Create a user
User::create(['name' => 'Test', 'email' => 'test@test.com']);

// Test relationships
$user = User::find(1);
$user->posts;
```

### Common Issues

**Issue**: "Permission denied" errors
```bash
# Fix permissions
sudo chown -R $USER:$USER .
```

**Issue**: Port already in use
```bash
# Check ports in use
netstat -tuln | grep 8081

# Stop other services or change port in docker-compose.yml
```

**Issue**: Database connection errors
```bash
# Restart MySQL container
sail down
sail up -d

# Check MySQL is running
sail mysql
```

## 📦 Managing Dependencies

### PHP Dependencies (Composer)

```bash
# Install package
sail composer require vendor/package

# Install dev package
sail composer require --dev vendor/package

# Remove package
sail composer remove vendor/package

# Update all packages
sail composer update

# Update specific package
sail composer update vendor/package
```

### JavaScript Dependencies (NPM)

```bash
# Install package
sail npm install package-name

# Install dev package
sail npm install --save-dev package-name

# Remove package
sail npm uninstall package-name

# Update packages
sail npm update
```

## 🌐 Accessing Services

| Service | URL | Notes |
|---------|-----|-------|
| Laravel App | http://localhost:8081 | Main application |
| PHPMyAdmin | http://localhost:8082 | Database GUI |
| Mailpit | http://localhost:8025 | Email testing |
| Vite Dev Server | http://localhost:5174 | Hot reload assets |

## 📝 Code Quality

### Laravel Pint (Code Formatter)

```bash
# Format all files
sail pint

# Dry run (show changes without applying)
sail pint --test

# Format specific files
sail pint app/Models
```

### Best Practices
1. Follow PSR-12 coding standards
2. Use type hints and return types
3. Write meaningful variable and function names
4. Comment complex logic
5. Write tests for new features
6. Use Laravel conventions (naming, folder structure)
7. Keep controllers thin, use services for business logic
8. Use form requests for validation

## 🔐 Security Notes

- Never commit `.env` file
- Keep dependencies updated
- Use environment variables for sensitive data
- Enable CSRF protection (Laravel default)
- Validate and sanitize all user input
- Use Laravel's authentication features

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs/11.x)
- [Laravel Sail Documentation](https://laravel.com/docs/11.x/sail)
- [Bootstrap Documentation](https://getbootstrap.com/docs/5.2/)
- [Blade Templates](https://laravel.com/docs/11.x/blade)

## 💡 Tips for AI-Assisted Development

When working with AI tools (like Cursor AI):
- Always mention you're using Laravel Sail
- Specify that commands need `sail` prefix
- Mention Bootstrap version (5.2.3) for frontend work
- Provide context about WSL/Docker environment
- Share relevant code snippets for better suggestions

## 🆘 Getting Help

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check Docker logs: `sail logs`
3. Review Laravel documentation
4. Check the project's GitHub issues
5. Use `sail artisan tinker` to test queries

---

**Happy Coding! 🚀**

