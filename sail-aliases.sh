#!/bin/bash

# Laravel Sail Aliases Configuration
# This file provides convenient shortcuts for Laravel Sail commands
#
# Usage:
# 1. Make this file executable: chmod +x sail-aliases.sh
# 2. Source this file in your shell: source sail-aliases.sh
# 3. Or add to ~/.bashrc or ~/.zshrc:
#    source /path/to/pnedu-bootstrap/sail-aliases.sh

# Basic Sail alias
alias sail='./vendor/bin/sail'

# Container Management
alias sup='sail up -d'
alias sdown='sail down'
alias srestart='sail restart'
alias sps='sail ps'
alias slogs='sail logs'
alias sshell='sail shell'

# Artisan Commands
alias sa='sail artisan'
alias sam='sail artisan migrate'
alias samf='sail artisan migrate:fresh'
alias samfs='sail artisan migrate:fresh --seed'
alias samr='sail artisan migrate:rollback'
alias samc='sail artisan make:controller'
alias samm='sail artisan make:model'
alias sams='sail artisan make:seeder'
alias sarl='sail artisan route:list'
alias sac='sail artisan cache:clear'
alias sacc='sail artisan config:clear'
alias savc='sail artisan view:clear'
alias saoc='sail artisan optimize:clear'
alias sat='sail artisan tinker'
alias sapail='sail artisan pail'
alias sapint='sail artisan pint'

# Database
alias sdb='sail mysql'
alias sdbs='sail artisan db:seed'

# Composer
alias sc='sail composer'
alias sci='sail composer install'
alias scu='sail composer update'
alias scr='sail composer require'
alias scda='sail composer dump-autoload'

# NPM
alias sn='sail npm'
alias sni='sail npm install'
alias snd='sail npm run dev'
alias snb='sail npm run build'

# Testing
alias st='sail test'
alias stf='sail test --filter'
alias stc='sail test --coverage'

# Git shortcuts (bonus)
alias gs='git status'
alias ga='git add'
alias gc='git commit -m'
alias gp='git push'
alias gl='git pull'
alias gco='git checkout'
alias gb='git branch'

# Combined operations
alias sstart='sail up -d && sail npm run dev'
alias sfresh='sail artisan migrate:fresh --seed && sail artisan optimize:clear'
alias sdeploy='sail down && git pull && sail up -d && sail composer install && sail npm install && sail npm run build && sail artisan migrate && sail artisan optimize:clear && sail up -d'

echo "✅ Laravel Sail aliases loaded!"
echo ""
echo "📋 Available aliases:"
echo "   sup        - Start containers (sail up -d)"
echo "   sdown      - Stop containers"
echo "   sa         - Sail artisan"
echo "   sam        - Run migrations"
echo "   sat        - Artisan tinker"
echo "   st         - Run tests"
echo "   sni        - NPM install"
echo "   snd        - NPM run dev"
echo ""
echo "💡 Run 'alias | grep ^s' to see all Sail aliases"
echo "💡 Add 'source $(pwd)/sail-aliases.sh' to your ~/.bashrc for permanent setup"

