# 📋 Quick Reference Guide - Laravel Sail Commands

## 🔥 Most Used Commands

### Container Management
```bash
sail up -d              # Start containers (detached)
sail down               # Stop containers
sail restart            # Restart containers
sail ps                 # List running containers
```

### Laravel Artisan
```bash
sail artisan migrate              # Run migrations
sail artisan migrate:fresh --seed # Fresh DB with seeds
sail artisan make:model Post -mcr # Model + Migration + Controller + Resource
sail artisan make:controller UserController --resource
sail artisan make:migration create_posts_table
sail artisan route:list           # List all routes
sail artisan tinker               # Laravel REPL
sail artisan pail                 # Real-time log monitoring
sail artisan optimize:clear       # Clear all caches
sail artisan pint                 # Format code
```

### Database
```bash
sail mysql                        # Access MySQL CLI
sail artisan db:seed              # Run seeders
sail artisan make:seeder UserSeeder
```

### Composer
```bash
sail composer install             # Install dependencies
sail composer require vendor/pkg  # Add package
sail composer update              # Update packages
sail composer dump-autoload      # Regenerate autoload
```

### NPM / Frontend
```bash
sail npm install                  # Install dependencies
sail npm run dev                  # Dev server (hot reload)
sail npm run build                # Production build
sail npm install package-name     # Add package
```

### Testing
```bash
sail test                         # Run all tests
sail test --filter=UserTest       # Run specific test
sail test --coverage              # With coverage
```

### Other Useful Commands
```bash
sail shell                        # Access container bash
sail logs                         # View all logs
sail logs mysql                   # View MySQL logs
sail redis-cli                    # Access Redis CLI
sail php -v                       # PHP version
```

## 🎯 Common Workflows

### Creating a New Feature
```bash
# 1. Create model with migration and controller
sail artisan make:model Article -mcr

# 2. Edit migration file
# database/migrations/xxxx_create_articles_table.php

# 3. Run migration
sail artisan migrate

# 4. Create views in resources/views/articles/

# 5. Define routes in routes/web.php

# 6. Test
sail test
```

### Database Reset
```bash
# Drop all tables and re-run migrations with seeding
sail artisan migrate:fresh --seed
```

### Clear All Caches
```bash
sail artisan cache:clear
sail artisan config:clear
sail artisan route:clear
sail artisan view:clear
# Or all at once:
sail artisan optimize:clear
```

### Install New Package
```bash
# PHP package
sail composer require vendor/package

# JavaScript package
sail npm install package-name

# After installing PHP packages
sail artisan optimize:clear
```

## 🚨 Command Comparison: DO's and DON'Ts

| ❌ DON'T USE | ✅ USE INSTEAD |
|-------------|---------------|
| `php artisan migrate` | `sail artisan migrate` |
| `composer install` | `sail composer install` |
| `npm install` | `sail npm install` |
| `php artisan make:model` | `sail artisan make:model` |
| `mysql -u root -p` | `sail mysql` |
| `phpunit` | `sail test` |
| `npm run dev` | `sail npm run dev` |

## 🌐 Service URLs

| Service | URL |
|---------|-----|
| Application | http://localhost:8081 |
| PHPMyAdmin | http://localhost:8082 |
| Mailpit | http://localhost:8025 |
| Vite Dev | http://localhost:5174 |

## 🐛 Troubleshooting Quick Fixes

### Containers won't start
```bash
sail down
docker system prune -f
sail up -d
```

### Permission errors
```bash
sudo chown -R $USER:$USER .
chmod -R 775 storage bootstrap/cache
```

### Database connection failed
```bash
sail down
sail up -d
sail artisan migrate
```

### Port already in use
```bash
# Find process using port 8081
sudo lsof -i :8081
# Kill it or change port in docker-compose.yml
```

### Cache issues
```bash
sail artisan optimize:clear
sail composer dump-autoload
```

## 📦 Artisan Make Commands

```bash
sail artisan make:model Post              # Model only
sail artisan make:model Post -m           # Model + Migration
sail artisan make:model Post -mc          # Model + Migration + Controller
sail artisan make:model Post -mcr         # Model + Migration + Controller (Resource)
sail artisan make:model Post -mfsc        # Model + Migration + Factory + Seeder + Controller

sail artisan make:controller PostController
sail artisan make:controller PostController --resource
sail artisan make:controller Api/PostController --api

sail artisan make:migration create_posts_table
sail artisan make:migration add_status_to_posts_table

sail artisan make:seeder PostSeeder
sail artisan make:factory PostFactory

sail artisan make:request StorePostRequest
sail artisan make:middleware CheckAge

sail artisan make:test PostTest              # Feature test
sail artisan make:test PostTest --unit       # Unit test
```

## 🔍 Useful Debugging Commands

```bash
# View routes
sail artisan route:list

# Interactive PHP (REPL)
sail artisan tinker

# Real-time logs
sail artisan pail

# Docker logs
sail logs
sail logs -f mysql

# Check Laravel version
sail artisan --version

# Check environment
sail artisan env

# List all Artisan commands
sail artisan list
```

## 💾 Database Quick Commands

```bash
# Create database dump
sail exec mysql mysqldump -u root -p${DB_PASSWORD} ${DB_DATABASE} > backup.sql

# Restore database
sail exec -T mysql mysql -u root -p${DB_PASSWORD} ${DB_DATABASE} < backup.sql

# Access database in Tinker
sail artisan tinker
>>> DB::table('users')->count()
>>> User::all()
>>> User::create(['name' => 'Test', 'email' => 'test@test.com'])
```

## 🎨 Bootstrap Classes Reminder

```html
<!-- Container -->
<div class="container">...</div>
<div class="container-fluid">...</div>

<!-- Grid -->
<div class="row">
  <div class="col-md-6">...</div>
  <div class="col-md-6">...</div>
</div>

<!-- Buttons -->
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>

<!-- Card -->
<div class="card">
  <div class="card-header">Header</div>
  <div class="card-body">Content</div>
</div>

<!-- Forms -->
<input type="text" class="form-control">
<select class="form-select">...</select>

<!-- Spacing -->
mt-3 (margin-top)
mb-4 (margin-bottom)
p-3 (padding)
mx-auto (margin horizontal auto)
```

## 📝 Git Quick Commands

```bash
# Check status
git status

# Stage changes
git add .

# Commit
git commit -m "Description of changes"

# Push
git push origin main

# Pull latest
git pull

# Create branch
git checkout -b feature-name

# Switch branch
git checkout main
```

## 🔄 Alias Setup (One-time)

Add to `~/.bashrc` or `~/.zshrc`:
```bash
alias sail='./vendor/bin/sail'
alias sa='sail artisan'
alias sac='sail artisan cache:clear'
alias sam='sail artisan migrate'
alias samc='sail artisan make:controller'
alias samm='sail artisan make:model'
alias sup='sail up -d'
alias sdown='sail down'
alias st='sail test'
```

Reload:
```bash
source ~/.bashrc
```

---

**Print this and keep it handy! 📌**

