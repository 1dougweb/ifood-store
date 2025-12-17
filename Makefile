.PHONY: help build up down restart logs shell artisan composer npm setup clean

help: ## Mostra esta mensagem de ajuda
	@echo "Comandos disponíveis:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

build: ## Build das imagens Docker
	docker-compose build

up: ## Iniciar containers
	docker-compose up -d

down: ## Parar e remover containers
	docker-compose down

restart: ## Reiniciar containers
	docker-compose restart

logs: ## Ver logs (use: make logs SERVICE=app)
	docker-compose logs -f $(SERVICE)

shell: ## Abrir shell no container app
	docker-compose exec app sh

artisan: ## Executar comando artisan (use: make artisan CMD="migrate")
	docker-compose exec app php artisan $(CMD)

composer: ## Executar comando composer (use: make composer CMD="install")
	docker-compose exec app composer $(CMD)

npm: ## Executar comando npm (use: make npm CMD="run build")
	docker-compose exec node npm $(CMD)

db: ## Acessar MySQL CLI
	docker-compose exec db mysql -u ead_user -pead_password ead

redis: ## Acessar Redis CLI
	docker-compose exec redis redis-cli

setup: ## Setup completo da aplicação
	@echo "Setting up application..."
	@if [ ! -f .env ]; then cp .env.example .env; fi
	docker-compose build
	docker-compose up -d
	@sleep 10
	docker-compose exec -T app composer install --no-interaction --prefer-dist --optimize-autoloader
	docker-compose exec -T node npm ci
	docker-compose exec -T app php artisan key:generate --force
	docker-compose exec -T app php artisan migrate --force
	docker-compose exec -T node npm run build
	docker-compose exec -T app chmod -R 775 storage bootstrap/cache
	docker-compose exec -T app chown -R www-data:www-data storage bootstrap/cache
	docker-compose exec -T app php artisan config:cache
	docker-compose exec -T app php artisan route:cache
	docker-compose exec -T app php artisan view:cache
	@echo "Setup complete! Application available at http://localhost"

clean: ## Limpar volumes e imagens
	docker-compose down -v
	docker system prune -f
