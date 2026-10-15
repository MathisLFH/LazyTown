# LazyTown

A modern Laravel application with Inertia.js, Team management, Two-Factor Authentication, and Passkeys support.

## DB Model

```bash
https://drive.google.com/file/d/138h61cUHgIPvP5xJYaqKCmR0bhETEy24/view?usp=sharing
```

Datei -> Öffnen -> draw.io

## Permission System

```bash
composer dump-autoload --optimize
```

## Quickstart

Start the local development environment

```bash
npm run start
```

## Features

- 🔐 **Authentication**: Complete auth system with Laravel Fortify
- 👥 **Team Management**: Multi-tenant team support with roles and permissions
- 🔑 **Passkeys Support**: WebAuthn/Passkeys authentication
- 🛡️ **Two-Factor Authentication**: 2FA with TOTP and Recovery Codes
- 🎨 **Modern UI**: Built with React, TypeScript, and Shadcn UI
- ⚡ **Vite + Inertia.js**: Fast development experience with Inertia.js

## Prerequisites

- PHP 8.2+
- Node.js 18+
- Composer
- npm or pnpm

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/MathisLFH/LazyTown.git
cd LazyTown
```

### 2. Install dependencies

**Backend (PHP)**:

```bash
composer install
```

**Frontend (JavaScript/TypeScript)**:

```bash
npm install
# or
pnpm install
```

### 3. Environment setup

Copy the example environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

### 4. Database setup

Create a database and update your `.env` file with the database credentials.

Run migrations:

```bash
php artisan migrate
```

### 5. Build frontend assets

Development:

```bash
npm run dev
# or
pnpm dev
```

Production:

```bash
npm run build
# or
pnpm build
```

## Development

### Running the development server

Laravel Herd serves the application at `https://lazytown.test`. Start the
frontend development server in a terminal:

```bash
nvm use 20.20.2
npm run dev
# or
pnpm dev
```

On Windows, open a new terminal after changing the Node version and verify
that `node --version` reports `v20.20.2`. This project uses Node 20 because
the current Vite HTTPS/HMR path fails with Node 22 in this environment.

The application will be available at `https://lazytown.test`. Vite serves
frontend assets and provides hot module replacement while the terminal stays
running. Docker Compose is only used for infrastructure services such as the
PostgreSQL database.

## Project Structure

```
├── app/                    # Laravel application code
│   ├── Actions/           # Action classes
│   ├── Models/            # Eloquent models
│   ├── Http/              # Controllers, Requests, Middleware
│   └── ...
├── resources/
│   ├── js/                # React components and pages
│   │   ├── components/    # Reusable React components
│   │   ├── pages/         # Page components
│   │   ├── layouts/       # Layout components
│   │   └── ...
│   └── css/               # Stylesheets
├── database/
│   ├── migrations/        # Database migrations
│   ├── factories/         # Model factories
│   └── seeders/           # Database seeders
├── routes/                # Application routes
├── tests/                 # Test files
└── ...
```

## Available Scripts

- `php artisan` - Laravel Artisan commands
- `php artisan serve` - Start development server
- `php artisan migrate` - Run database migrations
- `php artisan tinker` - Interactive shell
- `npm run dev` - Watch frontend in development
- `npm run build` - Build frontend for production
- `npm run lint` - Run ESLint
- `npm run format` - Format code with Prettier

## Testing

See [CICD_INFO.MD](./CICD_INFO.MD) for the CI/CD lifecycle, production database behavior, rollback limits, and branch guidance.

Run tests:

```bash
composer test
```

GitHub Actions runs CI on pull requests and pushes to `master`. It starts a
temporary PostgreSQL 16 service, builds the frontend, and runs `composer
ci:check` (ESLint, Prettier, TypeScript checks, Pint, PHPStan, and the Laravel
test suite). A push or manual workflow run on `master` can deploy after CI
passes when the repository variable `DEPLOY_ENABLED` is set to `true`. Pull
requests and workflow runs on other branches never deploy.

Configure the `production` GitHub Environment with these variables:

- `DEPLOY_HOST`: production SSH host
- `DEPLOY_USER`: SSH user
- `DEPLOY_PATH`: exactly `/var/www/lazytown`
- `DEPLOY_URL`: production URL used by the post-deploy health check

Configure these secrets on the same environment:

- `DEPLOY_SSH_KEY`: private SSH key for the deployment user
- `DEPLOY_KNOWN_HOSTS`: pinned SSH host key entry for the production host

Keep `DEPLOY_ENABLED` disabled until the production environment is ready. Add
required reviewers to the `production` Environment to require approval before
the deploy job starts. Never store deployment credentials in the repository.

The server must provide Bash, PHP CLI 8.3 or newer (but below 9.0), and the
standard `tar`, `ln`, `mv`, `chgrp`, `chmod`, and `find` commands. Prepare
`/var/www/lazytown/releases`, `/var/www/lazytown/shared/.env`, and
`/var/www/lazytown/shared/storage` before the first deployment, and configure
the web server to serve `/var/www/lazytown/current/public`. Each deployment
installs a versioned release, runs migrations, and atomically switches the
`current` symlink. The deployment user needs write access to `releases` and
`shared`, and must be root or a member of `www-data`. The shared `.env` file
must be owned by the deployment user, belong to `www-data`, and have mode `640`
so the PHP web process can read it without making it world-readable. If the
production health check fails, the workflow switches back to the previous
release when one exists; the first deployment has no previous release to
restore. Database migrations must remain compatible with the previous
application release because rollback does not reverse database changes. Old
release directories are not automatically removed, so monitor server disk
usage and decide on a retention policy before frequent deployments.

## Database

### Migrations

Create new migration:

```bash
php artisan make:migration migration_name
```

### Seeders

Run seeders:

```bash
php artisan db:seed
```

Create or update the demo test accounts (only allowed in the `local` and
`testing` environments):

```bash
php artisan db:seed --class=DemoAccountsSeeder
```

The accounts `premium@example.com`, `free@example.com`, `trainer@example.com`,
and `player@example.com` all use the password `12345678`. The seeder can be run
again safely to recreate or update these demo accounts.
The home page uses the selected team's saved matches and training sessions, as
well as news assigned to the signed-in user. If a section has no matching
records, it displays "Keine Daten verfügbar."

## Authentication

The application includes:

- Email/Password authentication
- Two-Factor Authentication
- Passkeys (WebAuthn)
- Social authentication ready (via Laravel Fortify)

## Team Management

- Create and manage teams
- Invite team members
- Assign roles and permissions
- Team-specific features and data

## Environment Variables

Key variables to configure in `.env`:

```
APP_NAME=LazyTown
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lazytown
DB_USERNAME=root
DB_PASSWORD=

MAIL_DRIVER=log
```

## Contributing

1. Create a feature branch
2. Make your changes
3. Run tests and linting
4. Submit a pull request

## License

This project is open-sourced software licensed under the MIT license.

## Support

For issues and questions, please open an issue on the GitHub repository.
