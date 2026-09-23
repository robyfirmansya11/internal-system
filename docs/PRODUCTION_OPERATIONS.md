# Production Operations Checklist

Use this checklist when deploying to the Windows Server/IIS environment.

## Before deployment

1. Back up the production database and `storage/app/private`.
2. Confirm a restore can be performed on a non-production database.
3. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`.
4. Deploy `composer.json` and `composer.lock`, then run `composer install --no-dev --optimize-autoloader`.
5. Run `php artisan migrate --force`, then `php artisan optimize`.
6. Verify login, private file download, an approval action, a PDF report, and an Excel export.

## Scheduler

Create a Windows Task Scheduler task that runs every minute under the IIS application's service account:

- Program: the same PHP executable used by the IIS application.
- Arguments: `artisan schedule:run --no-interaction`
- Start in: the project root directory.
- Enable "Run whether user is logged on or not" and retry on failure.

This runs the configured `workflow:send-reminders` schedule. Use `php artisan schedule:list` after deployment to confirm it is registered.

## Backup policy

Back up both of these data sets; neither alone is sufficient:

1. MySQL database, using a restricted backup account.
2. Private documents in `storage/app/private` (attendance photos, leave attachments, user documents, and similar files).

Keep encrypted backups outside the web root, define retention, and test a database-plus-files restore at least quarterly. Never expose the backup directory through IIS.

## Post-deployment checks

Run `php artisan workflow:send-reminders --dry-run` once, review the count, and monitor Laravel logs and failed queue jobs for the first business day.
