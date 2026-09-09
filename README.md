# Student Daily Attendance System

Local Laravel 13 application for school entrance time-in using hashed QR credentials. One attendance record per student per Philippine calendar day. No time-out, no student login, and no per-subject attendance.

## Stack

- PHP 8.3+, Laravel 13, MySQL 8
- Blade, Tailwind CSS 4, Vite 8
- `html5-qrcode` (bundled locally)
- `bacon/bacon-qr-code` for SVG QR cards

## Daily use

After the first install, compiled assets in `public/build` are enough. Do not run the Vite development server on school days. Keep Apache or `php artisan serve` and MySQL running.

See:

- [Local setup](docs/local-setup.md)
- [Daily operation](docs/daily-operation.md)
- [Backup and restore](docs/backup-and-restore.md)
