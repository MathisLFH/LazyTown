# LazyTown

A modern Laravel application with Inertia.js, Team management, Two-Factor Authentication, and Passkeys support.

## Quickstart

Script to run the project

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

Terminal 1 - Start the PHP server:

```bash
php artisan serve
```

Terminal 2 - Watch frontend assets:

```bash
npm run dev
# or
pnpm dev
```

The application will be available at `http://localhost:8000`

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
