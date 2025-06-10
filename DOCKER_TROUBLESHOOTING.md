# Docker Troubleshooting Guide

## Issues Fixed

The following issues have been resolved in the latest configuration:

1. **Volume Mount Issues**: Named volumes now preserve `vendor` and `node_modules` directories
2. **MySQL User Privileges**: Added proper database user permissions 
3. **Environment Variables**: Added missing `DATABASE_URL` and MySQL credentials
4. **Permission Issues**: Fixed startup script permissions

## Setup Instructions

1. **Clean up existing containers and volumes:**
   ```bash
   docker-compose down -v
   docker system prune -f
   docker volume prune -f
   ```

2. **Create environment file:**
   ```bash
   cp docker.env.example .env
   ```

3. **Build and start the containers:**
   ```bash
   docker-compose up --build
   ```

4. **Wait for containers to be healthy:**
   - MySQL will show "ready for connections"
   - Myddleware will show "Apache/2.4.62 configured -- resuming normal operations"

## Testing

Test the application:
```bash
curl -v http://localhost:30080/
```

## Key Changes Made

### docker-compose.yml
- Added `DATABASE_URL` environment variable
- Added MySQL user credentials to myddleware service
- Added named volumes for `vendor` and `node_modules`
- Added MySQL initialization script mount

### docker/mysql/init.sql
- Grants full privileges to myddleware user
- Ensures database exists with proper character set

### docker/script/myddleware-foreground.sh
- Added proper permission checks
- Improved dependency installation logic
- Fixed root/user privilege handling

### Dockerfile
- Added sudo configuration for www-data user
- Fixed directory creation and permissions
- Ensured proper user switching

## Common Issues

If you still see 500 errors:

1. **Check container logs:**
   ```bash
   docker-compose logs myddleware
   ```

2. **Check database connection:**
   ```bash
   docker-compose exec myddleware bash
   php bin/console doctrine:database:create
   ```

3. **Verify file permissions:**
   ```bash
   docker-compose exec myddleware bash
   ls -la vendor/ node_modules/
   ``` 