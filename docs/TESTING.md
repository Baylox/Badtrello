# Testing Guide

Quick guide for running tests in Taskio.

## Running Tests

**With Docker:**
```bash
docker compose exec app php bin/phpunit
```

**Without Docker:**
```bash
php bin/phpunit
```

## Run Specific Tests

**Unit tests only:**
```bash
php bin/phpunit tests/Unit
```

**Functional tests only:**
```bash
php bin/phpunit tests/Functional
```

**A specific test file:**
```bash
php bin/phpunit tests/Unit/Security/Voter/BoardVoterTest.php
```

## First Time Setup

If tests fail because the test database doesn't exist:

```bash
# Create test database and run migrations
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
```

## What the suite covers

- **Unit tests** (`tests/Unit`): services (`BoardService`, `LaneService`, `CardService`,
  `AccountService`, `RegistrationService`), DTO validation, form ↔ DTO mapping, entities and voters.
- **Functional tests** (`tests/Functional`): the full HTTP chain
  `Controller → DTO → Service → Repository` for Board, Lane, Card (incl. the JSON
  move endpoint), profile, registration and the admin panel. They use
  `zenstruck/foundry` factories with the `ResetDatabase` trait.

The test environment routes mail to `null://` and disables the
`NotCompromisedPassword` check (`config/packages/test/validator.yaml`), so the
suite never makes outbound network calls. Asset rendering is stubbed via
`config/packages/test/pentatrion_vite.yaml`, so no `npm run build` is required.

### Running offline (SQLite)

The functional tests are database-agnostic. To run them without a MySQL/MariaDB
server, point the test env at a local SQLite file using an (uncommitted)
`.env.test.local`:

```dotenv
DATABASE_URL="sqlite:///%kernel.project_dir%/var/test.sqlite"
```

Foundry's `ResetDatabase` recreates the schema automatically.

## That's It!

Just run `php bin/phpunit` before pushing to make sure you didn't break anything.

---

[← Back to README](../README.md)
