.PHONY: rebuild clean start stop restart deploy-prod backup restore test

# Development commands
rebuild:
	docker compose -f docker-compose.dev.yml down
	docker compose -f docker-compose.dev.yml build --no-cache
	docker image prune -f
	docker compose -f docker-compose.dev.yml up -d

start:
	docker compose -f docker-compose.dev.yml up -d

stop:
	docker compose -f docker-compose.dev.yml down

restart:
	docker compose -f docker-compose.dev.yml down
	docker compose -f docker-compose.dev.yml up -d

# Production deployment
deploy-prod:
	docker compose pull
	docker compose up -d --no-deps reconxi-be
	@echo "Waiting for health check..."
	@sleep 10
	@curl -f http://localhost:8000/health || (echo "Health check failed" && exit 1)
	@echo "Deployment successful!"

# Database backup
backup:
	@mkdir -p ./backups
	docker exec reconxi_db pg_dump -U $${DB_USERNAME} -d $${DB_DATABASE} > ./backups/backup_$$(date +%Y%m%d_%H%M%S).sql
	@echo "Backup created in ./backups/"

# Database restore (usage: make restore BACKUP_FILE=backup_20231215_120000.sql)
restore:
	@if [ -z "$(BACKUP_FILE)" ]; then echo "Usage: make restore BACKUP_FILE=backup_file.sql"; exit 1; fi
	docker exec -i reconxi_db psql -U $${DB_USERNAME} -d $${DB_DATABASE} < ./backups/$(BACKUP_FILE)

# Run tests
test:
	docker exec reconxi-be php artisan test

# Clean unused Docker resources
clean:
	docker system prune -a --volumes -f

# View logs
logs:
	docker compose logs -f

logs-dev:
	docker compose -f docker-compose.dev.yml logs -f