# Docker Setup Guide

Este guia explica como configurar e executar a aplicação usando Docker.

## 📋 Pré-requisitos

- Docker Engine 20.10+
- Docker Compose 2.0+

## 🚀 Início Rápido

### Windows (PowerShell)

Use o script helper:

```powershell
# Ver ajuda
.\docker\docker.ps1

# Setup completo
.\docker\docker.ps1 setup

# Iniciar containers
.\docker\docker.ps1 up

# Ver logs
.\docker\docker.ps1 logs app

# Executar comandos
.\docker\docker.ps1 artisan migrate
.\docker\docker.ps1 npm run build
```

### Linux/Mac

Use o Makefile:

```bash
# Ver ajuda
make help

# Setup completo
make setup

# Iniciar containers
make up

# Executar comandos
make artisan CMD="migrate"
make npm CMD="run build"
```

### 1. Configurar variáveis de ambiente

Copie o arquivo `.env.example` para `.env` e configure as variáveis:

```bash
cp .env.example .env
```

**Importante**: Configure as seguintes variáveis no `.env` para funcionar com Docker:

```env
APP_NAME="EAD"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=db          # Nome do serviço no docker-compose
DB_PORT=3306
DB_DATABASE=ead
DB_USERNAME=ead_user
DB_PASSWORD=ead_password

REDIS_HOST=redis    # Nome do serviço no docker-compose
REDIS_PORT=6379
```

### 2. Executar setup automático

```bash
chmod +x docker/setup.sh
./docker/setup.sh
```

Ou execute manualmente:

### 3. Build das imagens

```bash
docker-compose build
```

### 4. Iniciar containers

```bash
docker-compose up -d
```

### 5. Instalar dependências

```bash
# PHP
docker-compose exec app composer install --no-interaction --prefer-dist --optimize-autoloader

# Node (para build de assets)
docker-compose exec node npm ci
docker-compose exec node npm run build
```

### 6. Configurar aplicação

```bash
# Gerar chave da aplicação
docker-compose exec app php artisan key:generate

# Executar migrações
docker-compose exec app php artisan migrate --force

# Popular banco de dados (opcional)
docker-compose exec app php artisan db:seed --force

# Configurar permissões
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache

# Cache para produção
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

## 🏗️ Estrutura dos Serviços

### Serviços Disponíveis

- **app**: Aplicação PHP 8.3 (Laravel)
- **nginx**: Servidor web Nginx
- **node**: Node.js para build de assets
- **db**: Banco de dados MariaDB 11.3
- **redis**: Cache Redis 7
- **queue**: Worker de filas
- **scheduler**: Agendador de tarefas (Cron)

### Portas

- **80**: Nginx (HTTP)
- **443**: Nginx (HTTPS - configurar SSL)
- **3306**: MariaDB
- **6379**: Redis

## 📝 Comandos Úteis

### Gerenciar containers

```bash
# Iniciar
docker-compose up -d

# Parar
docker-compose stop

# Parar e remover
docker-compose down

# Ver logs
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f queue

# Rebuild após mudanças
docker-compose up -d --build
```

### Executar comandos Artisan

```bash
docker-compose exec app php artisan [comando]
```

### Executar comandos Composer

```bash
docker-compose exec app composer [comando]
```

### Executar comandos NPM

```bash
docker-compose exec node npm [comando]
```

### Acessar banco de dados

```bash
docker-compose exec db mysql -u ead_user -pead_password ead
```

### Acessar Redis CLI

```bash
docker-compose exec redis redis-cli
```

### Rebuild de assets

```bash
docker-compose exec node npm run build
```

## 🔧 Configurações

### PHP

Configurações em `docker/php/php.ini`

### Nginx

Configurações em:
- `docker/nginx/nginx.conf` (configuração principal)
- `docker/nginx/default.conf` (virtual host)

### MariaDB

Configurações em `docker/mysql/my.cnf`

## 🔒 SSL/HTTPS

Para habilitar HTTPS:

1. Coloque os certificados em `docker/nginx/ssl/`:
   - `cert.pem`
   - `key.pem`

2. Descomente o bloco SSL em `docker/nginx/default.conf`

3. Reinicie o Nginx:
```bash
docker-compose restart nginx
```

## 🐛 Troubleshooting

### Ver logs

```bash
# Logs da aplicação
docker-compose logs app

# Logs do Nginx
docker-compose logs nginx

# Logs do banco
docker-compose logs db

# Todos os logs
docker-compose logs -f
```

### Limpar cache

```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan view:clear
```

### Reinstalar dependências

```bash
# PHP
docker-compose exec app composer install --no-interaction

# Node
docker-compose exec node npm ci
```

### Resetar banco de dados

```bash
docker-compose exec app php artisan migrate:fresh --seed
```

### Verificar saúde dos serviços

```bash
docker-compose ps
```

## 📦 Produção

Para produção, certifique-se de:

1. Configurar `APP_ENV=production` e `APP_DEBUG=false` no `.env`
2. Configurar SSL/HTTPS
3. Ajustar limites de recursos no `docker-compose.yml`
4. Configurar backups do banco de dados
5. Configurar monitoramento e logs
6. Usar secrets para senhas e tokens

## 🔄 Atualizações

Para atualizar a aplicação:

```bash
# Pull do código
git pull

# Rebuild
docker-compose build

# Restart
docker-compose restart

# Executar migrações
docker-compose exec app php artisan migrate --force

# Rebuild assets
docker-compose exec node npm run build
```
