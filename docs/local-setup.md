# Local setup (Laragon on Windows)

Use Laragon only. Point the site document root at this project's `public` directory. Never expose the project root.

## Required versions

- PHP 8.3 or newer (Laragon PHP, not XAMPP PHP 8.2)
- Composer 2
- Node.js 20 LTS
- MySQL 8

Confirm PHP from a terminal:

```powershell
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -v
```

If `php -v` shows 8.2, add the Laragon PHP 8.3 folder to the session PATH before running Artisan or Composer.

## First install

1. Start Laragon **Apache** and **MySQL**.
2. Create an empty MySQL database named `attendancemonitoringsystem`.
3. Copy environment settings:

```powershell
copy .env.example .env
```

4. Set `APP_KEY` and install dependencies:

```powershell
$env:Path = "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64;C:\laragon\bin\composer;C:\laragon\bin\nodejs;" + $env:Path
composer install
php artisan key:generate
php artisan migrate
php artisan admin:create
npm ci
npm run build
```

5. Open `http://attendancemonitoringsystem.test` (Laragon vhost; document root is already `public/`). You can also use `php artisan serve` at `http://localhost:8000`.
6. Sign in with the administrator you created.
7. Add a section, register a student, print the QR card, then open Scanner.
8. For the webcam, use `http://127.0.0.1:8000/scanner` (`php artisan serve`) or HTTPS. Browsers block the camera on `http://attendancemonitoringsystem.test`.

Optional demo records (never run this against real school data):

```powershell
php artisan db:seed --class=DemoDataSeeder
```

`DatabaseSeeder` does not load demo data.

## Everyday services

Leave these running:

- MySQL
- Apache (Laragon vhost) or `php artisan serve`

Do not start `npm run dev` for daily attendance. Use the files produced by `npm run build`.

## Webcam notes

The primary scanner is this same Windows computer. Use `http://127.0.0.1:8000` or HTTPS. Grant camera permission in the browser. Do not use `http://attendancemonitoringsystem.test` for scanning; Chrome blocks the webcam there.

On Serv00 the scanner uses HTTPS. See [Serv00 free hosting](serv00-deploy.md).

Phone scanning over a LAN IP on plain HTTP usually cannot access the camera. That needs a separate trusted HTTPS setup.

## Application key

`APP_KEY` encrypts stored QR tokens. Back it up with the database. If the key changes, existing cards cannot be regenerated from stored tokens and must be replaced.
