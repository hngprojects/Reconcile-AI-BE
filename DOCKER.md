# Reconcile AI Backend - Docker Setup

This guide will help you set up the Reconcile AI backend using Docker with proper Redis connectivity.

## Prerequisites

- Docker Desktop installed and running
- Docker Compose v2.0 or higher
- Git

## Quick Start

1. **Clone and navigate to the project:**
   ```bash
   git clone <repository-url>
   cd Reconcile-AI-BE
   ```

2. **Build and start the containers:**
   ```bash
   docker-compose up --build -d
   ```

3. **Check container status:**
   ```bash
   docker-compose ps
   ```

4. **View logs:**
   ```bash
   docker-compose logs -f
   ```

5. **Access the application:**
   - API: http://localhost:8000
   - API Documentation: http://localhost:8000/api/docs
   - WebSocket (Reverb): ws://localhost:8080

## Services

The Docker setup includes:

- **reconxi-be**: Laravel application (PHP 8.2 + Nginx)
- **db**: PostgreSQL with pgvector extension
- **redis**: Redis for caching, sessions, and queues

## Environment Configuration

The Docker setup uses `.env.docker` for container-specific configuration:
- Database host: `db` (container name)
- Redis host: `redis` (container name)
- All caching, sessions, and queues use Redis

## Useful Commands

### Using Docker Compose directly:
```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f reconxi-be

# Access application shell
docker-compose exec reconxi-be bash

# Access Redis CLI
docker-compose exec redis redis-cli

# Access PostgreSQL
docker-compose exec db psql -U reconxi -d reconxi
```

### Using the provided Makefile:
```bash
# Build containers
make -f Makefile.docker build

# Start services
make -f Makefile.docker up

# View logs
make -f Makefile.docker logs

# Access shell
make -f Makefile.docker shell

# Test Redis connection
make -f Makefile.docker test-redis

# Complete reset
make -f Makefile.docker reset
```

## Troubleshooting

### Redis Connection Issues
1. Check if Redis container is running:
   ```bash
   docker-compose ps redis
   ```

2. Test Redis connection:
   ```bash
   docker-compose exec reconxi-be redis-cli -h redis ping
   ```

3. Check Redis logs:
   ```bash
   docker-compose logs redis
   ```

### Database Connection Issues
1. Check if database is ready:
   ```bash
   docker-compose exec reconxi-be pg_isready -h db -p 5432 -U reconxi -d reconxi
   ```

2. Check database logs:
   ```bash
   docker-compose logs db
   ```

### Application Issues
1. Check application logs:
   ```bash
   docker-compose logs reconxi-be
   ```

2. Access application shell for debugging:
   ```bash
   docker-compose exec reconxi-be bash
   ```

3. Clear Laravel caches:
   ```bash
   docker-compose exec reconxi-be php artisan config:clear
   docker-compose exec reconxi-be php artisan cache:clear
   ```

## Development

### Making Changes
- Code changes are automatically reflected (volume mounted)
- For configuration changes, restart containers:
  ```bash
  docker-compose restart reconxi-be
  ```

### Running Artisan Commands
```bash
docker-compose exec reconxi-be php artisan migrate
docker-compose exec reconxi-be php artisan queue:work
docker-compose exec reconxi-be php artisan tinker
```

### Accessing Logs
- Application logs: `docker-compose logs reconxi-be`
- Database logs: `docker-compose logs db`
- Redis logs: `docker-compose logs redis`

## Production Considerations

For production deployment:
1. Use proper environment variables
2. Set up SSL/TLS certificates
3. Configure proper backup strategies
4. Use Docker secrets for sensitive data
5. Set up monitoring and logging