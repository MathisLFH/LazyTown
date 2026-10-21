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

Run tests:

```bash
composer test
```

## CI/CD

### Continuous integration

GitHub Actions runs `.github/workflows/tests.yml` on pushes to `master` and on
pull requests. It starts a temporary PostgreSQL 16 service, uses PHP 8.5 and
Node.js 22, then runs `composer ci:check`. This includes ESLint, Prettier,
TypeScript, Pint, PHPStan, and the Laravel test suite.

### Production deployment (planned, not active)

The current workflow only runs CI; it does **not** deploy the application.
The following values describe the planned deployment and must be configured
before enabling a deploy job:

| Setting | Planned value | Notes |
| --- | --- | --- |
| Server | `2.31.56.13` | Ubuntu 26.04.1 LTS |
| Domain | `lazytown.schmidtflo.de` | Configure DNS and HTTPS before production |
| SSH user | `deploy` | Intended restricted account; not `root` and not `lazytown` |
| Application path | `/var/www/lazytown` | Active release would be `/var/www/lazytown/current` |
| Web server | Nginx | Web root should be `/var/www/lazytown/current/public` |
| Runtime | PHP 8.5 and Composer | Reported as installed on the server |
| Database | PostgreSQL 16 via Docker Compose | Compose currently names the database `lazytown` and its user `admin`; replace its example credentials and restrict port 5432 to localhost before production |

When a deploy job is added, configure these GitHub repository variables:

- `DEPLOY_HOST`: `2.31.56.13`
- `DEPLOY_USER`: `deploy`
- `DEPLOY_PATH`: `/var/www/lazytown`
- `DEPLOY_ENABLED`: leave `false` (or unset) until the server and deploy job
  are ready; enable only after a successful CI run is confirmed

Create a GitHub Actions environment named `production` and store these
environment secrets there:

- `DEPLOY_SSH_KEY`: private SSH key for the restricted `deploy` user
- `DEPLOY_KNOWN_HOSTS`: verified SSH host-key line for the server

Generate the key pair on an administrator's computer, not in the repository.
For example, in Windows PowerShell:

```powershell
ssh-keygen -t ed25519 -a 100 -C "lazytown-actions" -f "$env:USERPROFILE\.ssh\lazytown-actions"
```

Install only `lazytown-actions.pub` in the server user's
`/home/deploy/.ssh/authorized_keys`. Add the private `lazytown-actions` key
only to the GitHub `DEPLOY_SSH_KEY` secret; never commit or share it. Verify
the server host key before adding it as `DEPLOY_KNOWN_HOSTS`. Do not use root
SSH access for automated deployment.

Before activating deployment, create the `deploy` account and release
directories, configure Nginx and HTTPS, prepare the production `.env` with a
unique `APP_KEY` and strong database credentials, and ensure PostgreSQL data
persists across container restarts. Protect the `master` branch and the
`production` environment so unreviewed changes cannot deploy.

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
