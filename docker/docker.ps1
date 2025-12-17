# Docker Helper Scripts for Windows PowerShell

param(
    [Parameter(Position=0)]
    [string]$Command = "help"
)

function Show-Help {
    Write-Host @"
Docker Helper Scripts

Comandos disponíveis:
  build       - Build das imagens Docker
  up          - Iniciar containers
  down        - Parar e remover containers
  restart     - Reiniciar containers
  logs        - Ver logs dos containers
  exec        - Executar comando no container app
  artisan     - Executar comando artisan
  composer    - Executar comando composer
  npm         - Executar comando npm
  shell       - Abrir shell no container app
  db          - Acessar MySQL CLI
  redis       - Acessar Redis CLI
  setup       - Setup completo da aplicação
  clean       - Limpar volumes e imagens

Exemplos:
  .\docker.ps1 build
  .\docker.ps1 up
  .\docker.ps1 artisan migrate
  .\docker.ps1 npm run build
"@
}

function Build-Images {
    Write-Host "Building Docker images..." -ForegroundColor Green
    docker-compose build
}

function Start-Containers {
    Write-Host "Starting containers..." -ForegroundColor Green
    docker-compose up -d
}

function Stop-Containers {
    Write-Host "Stopping containers..." -ForegroundColor Yellow
    docker-compose down
}

function Restart-Containers {
    Write-Host "Restarting containers..." -ForegroundColor Yellow
    docker-compose restart
}

function Show-Logs {
    param([string]$Service = "")
    if ($Service) {
        docker-compose logs -f $Service
    } else {
        docker-compose logs -f
    }
}

function Invoke-Exec {
    param([string[]]$Args)
    docker-compose exec app $Args
}

function Invoke-Artisan {
    param([string[]]$Args)
    docker-compose exec app php artisan $Args
}

function Invoke-Composer {
    param([string[]]$Args)
    docker-compose exec app composer $Args
}

function Invoke-Npm {
    param([string[]]$Args)
    docker-compose exec node npm $Args
}

function Open-Shell {
    docker-compose exec app sh
}

function Open-Db {
    docker-compose exec db mysql -u ead_user -pead_password ead
}

function Open-Redis {
    docker-compose exec redis redis-cli
}

function Invoke-Setup {
    Write-Host "Setting up application..." -ForegroundColor Green
    
    # Copy .env if not exists
    if (-not (Test-Path .env)) {
        Write-Host "Copying .env.example to .env..." -ForegroundColor Yellow
        Copy-Item .env.example .env
    }
    
    # Build and start
    Build-Images
    Start-Containers
    
    Write-Host "Waiting for services..." -ForegroundColor Yellow
    Start-Sleep -Seconds 10
    
    # Install dependencies
    Write-Host "Installing PHP dependencies..." -ForegroundColor Yellow
    Invoke-Composer @("install", "--no-interaction", "--prefer-dist", "--optimize-autoloader")
    
    Write-Host "Installing Node dependencies..." -ForegroundColor Yellow
    Invoke-Npm @("ci")
    
    # Generate key
    Write-Host "Generating application key..." -ForegroundColor Yellow
    Invoke-Artisan @("key:generate", "--force")
    
    # Migrate
    Write-Host "Running migrations..." -ForegroundColor Yellow
    Invoke-Artisan @("migrate", "--force")
    
    # Build assets
    Write-Host "Building assets..." -ForegroundColor Yellow
    Invoke-Npm @("run", "build")
    
    # Permissions
    Write-Host "Setting permissions..." -ForegroundColor Yellow
    Invoke-Exec @("chmod", "-R", "775", "storage", "bootstrap/cache")
    Invoke-Exec @("chown", "-R", "www-data:www-data", "storage", "bootstrap/cache")
    
    # Cache
    Write-Host "Caching for production..." -ForegroundColor Yellow
    Invoke-Artisan @("config:cache")
    Invoke-Artisan @("route:cache")
    Invoke-Artisan @("view:cache")
    
    Write-Host "Setup complete! Application available at http://localhost" -ForegroundColor Green
}

function Clear-Docker {
    Write-Host "Cleaning Docker resources..." -ForegroundColor Yellow
    docker-compose down -v
    docker system prune -f
}

switch ($Command.ToLower()) {
    "build" { Build-Images }
    "up" { Start-Containers }
    "down" { Stop-Containers }
    "restart" { Restart-Containers }
    "logs" { Show-Logs $args[0] }
    "exec" { Invoke-Exec $args }
    "artisan" { Invoke-Artisan $args }
    "composer" { Invoke-Composer $args }
    "npm" { Invoke-Npm $args }
    "shell" { Open-Shell }
    "db" { Open-Db }
    "redis" { Open-Redis }
    "setup" { Invoke-Setup }
    "clean" { Clear-Docker }
    default { Show-Help }
}
