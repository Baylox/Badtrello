# Installation Guide

This guide provides detailed instructions for installing and running Taskio on your system.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation with Docker (Recommended)](#installation-with-docker-recommended)
- [Installation with Symfony CLI](#installation-with-symfony-cli)
- [Post-Installation](#post-installation)
- [Troubleshooting](#troubleshooting)

## Prerequisites

### For Docker Installation (Recommended)

- **Docker Engine** 20.10 or higher
- **Docker Compose** 2.0 or higher

### For Symfony CLI Installation

- **PHP** 8.2 or higher
- **Composer** 2.x
- **Node.js** 18+ and npm
- **MariaDB** 11.4 or **MySQL** 8.0+
- **Symfony CLI** (optional but recommended)

## Installation with Docker (Recommended)

Docker provides the easiest way to run the application with all dependencies pre-configured.

### 1. Clone the Repository

```bash
git clone https://github.com/Baylox/taskio.git
cd taskio
```

### 2. Configure Environment Variables

```bash
cp .env .env.local
```

Edit `.env.local` and update the following if needed:

```env
# Database (already configured for Docker)
DATABASE_URL="mysql://app:!ChangeMe!@database:3306/app"

# Mailer (Mailpit for development)
MAILER_DSN=smtp://mailer:1025
```

### 3. Build and Start Docker Containers

```bash
docker compose up -d --build
```

This will start the following services:

- **app**: Symfony application (http://localhost:8080)
- **database**: MariaDB database server
- **vite**: Vite dev server with hot reload (http://localhost:3000)
- **mailer**: Mailpit for email testing (http://localhost:8025)

### 4. Install Dependencies and Set Up Database

```bash
# Access the app container
docker compose exec app bash

# Inside the container:
composer install
php bin/console doctrine:migrations:migrate --no-interaction

# Load sample data (optional)
php bin/console foundry:load-fixtures main
```

### 5. Access the Application

- **Application**: http://localhost:8080
- **Mailpit** (Email testing): http://localhost:8025

### 6. Default Credentials

If fixtures were loaded, you can use these credentials:

- **Admin**: `admin@example.com` / `adminpassword`
- **User**: `user@example.com` / `userpassword`

## Installation with Symfony CLI

For local development without Docker:

### 1. Clone the Repository

```bash
git clone https://github.com/Baylox/taskio.git
cd taskio
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node.js Dependencies

```bash
npm install
```

### 4. Configure Environment

```bash
cp .env .env.local
```

Update `.env.local` with your local database credentials:

```env
DATABASE_URL="mysql://username:password@127.0.0.1:3306/taskio"
MAILER_DSN=smtp://localhost:1025
```

### 5. Create and Configure the Database

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Load sample data (optional)
php bin/console foundry:load-fixtures main
```

### 6. Start the Development Servers

In separate terminal windows:

```bash
# Terminal 1: Symfony server
symfony server:start

# Terminal 2: Vite dev server
npm run dev

# Terminal 3: Mailpit (optional, for email testing)
mailpit
```

### 7. Access the Application

- **Application**: http://localhost:8000 (or the port shown by Symfony)
- **Mailpit**: http://localhost:8025

## Post-Installation

### Verify Installation

After installation, verify that everything is working:

1. Access the application in your browser
2. Register a new account or log in with default credentials
3. Try creating a board and adding cards
4. Check Mailpit to verify emails are being sent

### Production Setup

For production deployment, additional steps are required:

1. Set `APP_ENV=prod` in `.env.local`
2. Configure a production database
3. Set up a real SMTP server for emails
4. Build assets for production: `npm run build`
5. Clear and warm up the cache: `php bin/console cache:clear`

## Troubleshooting

### Docker Issues

**Containers won't start:**
```bash
# Check logs
docker compose logs

# Rebuild containers
docker compose down
docker compose up -d --build
```

**Port conflicts:**
- Edit `compose.yaml` to change ports if 8080, 3306, or 8025 are already in use

**Database connection errors:**
- Ensure the database container is running: `docker compose ps`
- Wait a few seconds after starting for the database to initialize

### Symfony CLI Issues

**Database connection errors:**
- Verify MariaDB/MySQL is running
- Check database credentials in `.env.local`
- Ensure the database exists: `php bin/console doctrine:database:create`

**Permission errors:**
- Ensure `var/` directory is writable: `chmod -R 777 var/`

---

[← Back to README](../README.md) | [Next: Usage Guide →](USAGE.md)
