# Internal System

Internal System is a Laravel and Filament application for managing internal HR, finance, operational requests, and approval workflows. It also provides a secured API for the mobile attendance application.

> This repository contains application source code only. Never commit production credentials, database exports, personal documents, private keys, or private storage files.

## Core capabilities

- **HRIS** — employee profiles, departments, leave quotas, holidays, attendance, overtime, and late-working permits.
- **Approval workflows** — leave, overtime, travel reimbursement, loan notes, payment applications, stamp applications, and other internal requests.
- **Finance operations** — payment applications, loan notes, travel reimbursement, expense reimbursement notes, and finance summary reports.
- **Reports and exports** — printable PDF documents and formatted Excel reports.
- **Auditability** — approval histories, activity logs, workflow notifications, and scheduled reminders.
- **Mobile API** — Laravel Sanctum authentication and protected API endpoints for attendance and request workflows.

## Technology stack

| Component | Version / technology |
| --- | --- |
| PHP | 8.3 or later |
| Framework | Laravel 12 |
| Admin panel | Filament 5 |
| Database | MySQL or MariaDB |
| Authentication | Laravel Sanctum |
| Reports | Dompdf, Laravel Excel, PhpSpreadsheet |
| Frontend tooling | Vite and Tailwind CSS |

## Requirements

- PHP 8.3+ with Laravel-required extensions (`pdo_mysql`, `mbstring`, `xml`, `zip`, `gd`, and `fileinfo`)
- Composer 2
- Node.js and npm
- MySQL 8+ or a supported MariaDB version
- Git

## Local installation

```bash
git clone https://github.com/robyfirmansya11/internal-system.git
cd internal-system
composer install
```

Create the local environment file. Do not copy a production `.env` into a developer machine.

```bash
# Linux / macOS
cp .env.example .env

# Windows PowerShell
Copy-Item .env.example .env
```

Set local database and mail values in `.env`, then run:

```bash
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Start the application:

```bash
php artisan serve
```

For the local development process, use:

```bash
composer run dev
```

## Environment and secret handling

The following values are sensitive and must remain in `.env`, a server secret store, or the hosting control panel:

- `APP_KEY`
- Database credentials and `DB_PASSWORD`
- Mail provider credentials
- Storage, cloud, Google, Apple, and third-party API credentials
- Sanctum tokens and mobile-attestation credentials

Before committing, check that no secret is staged:

```bash
git status
git diff --cached
```

The repository ignores `.env`, `.env.production`, `auth.json`, private storage keys, IIS `public/web.config`, logs, `vendor`, and `node_modules`. Do not force-add any of them.

## Security controls

- Sensitive uploads are stored on a private disk and served through authorized endpoints.
- API endpoints use Sanctum authentication; API login is rate-limited.
- Role and record-level authorization restrict private documents and approval actions.
- Attendance stores audit metadata and rejects reported mock-location, rooted, emulator, failed, or compromised device states.
- Approval histories and activity logs retain workflow and sensitive-data changes.
- Dependencies are pinned and should be checked regularly with `composer audit`.

Read [Mobile Attendance Integrity](docs/MOBILE_ATTENDANCE_INTEGRITY.md) before changing the mobile application contract.

## Testing and quality checks

Run the automated suite before merging or deploying:

```bash
php artisan test
composer audit --locked
composer validate --no-check-publish
```

Format PHP code when needed:

```bash
./vendor/bin/pint
```

## Database migrations

Use the normal migration command locally:

```bash
php artisan migrate
```

On production, create verified database and private-file backups first, then run:

```bash
php artisan migrate --force
php artisan optimize
```

Never run destructive migration commands such as `migrate:fresh` against production.

## Operations and deployment

The production server runs on Windows Server/IIS. The deployment checklist covers private-file backups, IIS-safe configuration, scheduler setup, and post-deployment checks.

See [Production Operations Checklist](docs/PRODUCTION_OPERATIONS.md).

The scheduler must run every minute in production so scheduled workflow reminders can execute. Confirm the schedule with:

```bash
php artisan schedule:list
```

## Security reporting

Do not open public GitHub issues for vulnerabilities or exposed data. Report security concerns privately to the repository owner, with reproduction steps and the affected area. Do not include credentials or personal data in the report.

## License

Proprietary and confidential. This software is intended for authorized internal use only.
