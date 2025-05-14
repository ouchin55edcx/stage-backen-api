# Laravel Application with Docker

This is a Laravel application set up with Docker for easy development and deployment.

## Running the Application

### Prerequisites

- Docker and Docker Compose installed on your system
- Git (optional, for cloning the repository)

### Starting the Application

1. Clone the repository (if you haven't already):
   ```bash
   git clone <repository-url>
   cd <repository-directory>
   ```

2. Start the Docker containers:
   ```bash
   docker compose up -d
   ```

3. Set proper permissions for Laravel storage:
   ```bash
   docker exec -it laravel-app chmod -R 777 /var/www/storage
   docker exec -it laravel-app chown -R www-data:www-data /var/www/storage
   docker exec -it laravel-app chmod -R 777 /var/www/bootstrap/cache
   docker exec -it laravel-app chown -R www-data:www-data /var/www/bootstrap/cache
   ```

4. Run database migrations:
   ```bash
   docker exec -it laravel-app php artisan migrate
   ```

5. Access the application in your browser:
   ```
   http://localhost:8080
   ```

### Stopping the Application

To stop the application:
```bash
docker compose down
```

## Accessing the Database Directly

### Using MySQL CLI

You can access the MySQL database directly using the MySQL CLI:

```bash
docker exec -it laravel-db mysql -u root -ppassword back_end
```

### Using a MySQL Client

You can also connect to the database using a MySQL client with these credentials:

- Host: localhost
- Port: 3308
- Database: back_end
- Username: root
- Password: password

## Common Commands

### Running Artisan Commands

```bash
docker exec -it laravel-app php artisan <command>
```

Examples:
```bash
# Clear cache
docker exec -it laravel-app php artisan cache:clear

# Create a new controller
docker exec -it laravel-app php artisan make:controller UserController

# List all available routes
docker exec -it laravel-app php artisan route:list
```

### Viewing Logs

```bash
# Application logs
docker logs laravel-app

# Web server logs
docker logs laravel-nginx

# Database logs
docker logs laravel-db
```

## Container Information

- **Web Server**: Nginx (Port 8080)
- **PHP Application**: PHP 8.2 with Laravel
- **Database**: MySQL 8.0 (Port 3308)

## Troubleshooting

If you encounter permission issues, run:
```bash
docker exec -it laravel-app chmod -R 777 /var/www/storage
docker exec -it laravel-app chmod -R 777 /var/www/bootstrap/cache
```

If you need to clear Laravel's cache:
```bash
docker exec -it laravel-app php artisan cache:clear
docker exec -it laravel-app php artisan view:clear
docker exec -it laravel-app php artisan config:clear
```# stage-backen-api
