# Taskio - Collaborative Board Management System

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7.3-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![MariaDB](https://img.shields.io/badge/MariaDB-11.4-003545?logo=mariadb&logoColor=white)](https://mariadb.org/)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A professional, full-featured Kanban-style board management system built with Symfony 7.3, designed for team collaboration and project management. Create boards, organize tasks into lanes, manage cards with drag-and-drop functionality, and collaborate with team members in real-time.

## Features

- **Board Management**: Create, edit, and organize project boards with customizable workflows
- **Drag & Drop Interface**: Intuitive card and lane management with SortableJS
- **Real-time Collaboration**: Share boards and work together with team members
- **Role-Based Access Control**: Owner, Collaborator, and Admin roles with fine-grained permissions
- **Responsive Design**: Modern UI built with Tailwind CSS and DaisyUI
- **Email Notifications**: Integrated invitation system with Symfony Mailer
- **Comprehensive Testing**: Full test coverage with PHPUnit

## Quick Start

### Using Docker (Recommended)

```bash
# Clone the repository
git clone https://github.com/Baylox/taskio.git
cd taskio

# Start Docker containers
docker compose up -d --build

# Install dependencies and set up database
docker compose exec app bash
composer install
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console foundry:load-fixtures main

# Access the application
open http://localhost:8080
```

**Default credentials** (after loading fixtures):
- Admin: `admin@example.com` / `adminpassword`
- User: `user@example.com` / `userpassword`

### Using Symfony CLI

```bash
# Clone and install dependencies
git clone https://github.com/Baylox/taskio.git
cd taskio
composer install
npm install

# Set up database
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Start development servers (in separate terminals)
symfony server:start
npm run dev
```

For detailed installation instructions, see the [Installation Guide](docs/INSTALLATION.md).

## Documentation

Comprehensive documentation is available in the `docs/` directory:

- **[Installation Guide](docs/INSTALLATION.md)** - Detailed setup instructions for Docker and Symfony CLI
- **[Usage Guide](docs/USAGE.md)** - How to use Taskio's features
- **[Testing Guide](docs/TESTING.md)** - Running and writing tests
- **[Architecture](docs/ARCHITECTURE.md)** - Technical overview and design patterns

## Technology Stack

**Backend**: Symfony 7.3 | PHP 8.2+ | MariaDB 11.4 | Doctrine ORM 3.5

**Frontend**: Vite 6.3 | Tailwind CSS 3.4 | DaisyUI 4.12 | Stimulus | Turbo | SortableJS

**DevOps**: Docker | Docker Compose | Apache | PHPUnit 11.5

**Design Patterns**: Repository Pattern | Voter Pattern | Service Layer | Stimulus Controllers

## Project Structure

```
taskio/
├── assets/              # Frontend assets (JS, CSS, Stimulus controllers)
├── config/              # Symfony configuration
├── docs/                # Documentation
├── migrations/          # Database migrations
├── src/
│   ├── Controller/      # HTTP controllers
│   ├── Entity/          # Doctrine entities
│   ├── Form/            # Form types
│   ├── Repository/      # Data repositories
│   ├── Security/        # Voters and security
│   └── Service/         # Business logic
├── templates/           # Twig templates
├── tests/               # PHPUnit tests
├── CONTRIBUTING.md      # Contribution guidelines
├── LICENSE              # MIT License
└── SECURITY.md          # Security policy
```

### Development Workflow

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes and add tests
4. Run tests (`php bin/phpunit`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Code Standards

- Follow **PSR-12** coding standards
- Write **tests** for new features
- Update **documentation** as needed
- Keep commits **atomic** and well-described

## Testing

```bash
# Run all tests
php bin/phpunit

# Run specific test suite
php bin/phpunit tests/Unit

# Generate coverage report
XDEBUG_MODE=coverage php bin/phpunit --coverage-html coverage
```

See the [Testing Guide](docs/TESTING.md) for more information.

## Security

Security is a top priority for Taskio. If you discover a security vulnerability, please review our [Security Policy](SECURITY.md) and report it responsibly.

**Security Features**:
- Argon2i password hashing
- CSRF protection
- XSS prevention
- SQL injection protection
- Role-based access control
- Email verification

**Under the condition:**
- Include the original copyright notice and license in any copy of the software/source

## Support & Community

- **Documentation**: Check the [docs/](docs/) folder
- **Issues**: Report bugs or request features on [GitHub Issues](https://github.com/Baylox/taskio/issues)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

**You are free to:**
- Use commercially
- Modify
- Distribute
- Use privately
- Sublicense

---

**Ready to get started?** Follow the [Installation Guide](docs/INSTALLATION.md) and begin organizing your projects with Taskio!
