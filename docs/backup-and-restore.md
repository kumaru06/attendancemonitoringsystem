# Backup and restore

Keep backups off this computer when possible. Files that contain secrets must not go into git.

## What to copy

1. MySQL database `attendancemonitoringsystem`
2. Private student photos in `storage/app/private/student-photos`
3. `.env` (includes `APP_KEY`)

## Backup

With MySQL running:

```powershell
mysqldump -u root attendancemonitoringsystem > C:\backups\attendance-YYYYMMDD.sql
```

Copy the photo folder and `.env` to the same dated backup directory.

## Restore

```powershell
mysql -u root attendancemonitoringsystem < C:\backups\attendance-YYYYMMDD.sql
```

Restore `storage/app/private/student-photos` and `.env` to the project. The encryption key in `.env` must match the key used when the QR tokens were stored.

## After restore

Start MySQL and Apache (or `php artisan serve`). Do not run `migrate:fresh` against restored school records.
