# Deploy on Serv00 (free hosting)

Serv00 gives you PHP 8.3, MySQL 8, SSH, and HTTPS. That is enough for this Laravel app and for the webcam scanner. Register yourself at [serv00.com](https://www.serv00.com/). Activation can take minutes or sit on a waitlist. Nobody else can create that account for you.

Your welcome email has the values you will copy into the commands below:

- login (example: `juan`)
- SSH host (`s2.serv00.com`, `s3.serv00.com`, …)
- panel URL (`https://panel2.serv00.com/` when the host is `s2`)
- MySQL host (`mysql2.serv00.com` when the host is `s2`)
- free site `https://LOGIN.serv00.net/`

Replace `LOGIN`, `sX`, and `mysqlX` everywhere.

## 1. Panel: website and MySQL

1. Sign in to DevilWEB.
2. Open **WWW websites**. If `LOGIN.serv00.net` is missing, add it as a **PHP** site.
3. Open **Details** for that site and set:
   - GZIP: on
   - Force SSL: on (you can turn this on after step 6 if the certificate is not ready yet)
   - PHP `exec()`: **on** (`php_exec`)
   - PHP `eval()`: off
   - Cache: **off** (Laravel pages must not be cached as static HTML)
   - Open basedir: `/usr/home/LOGIN/domains/`
4. Open **MySQL** → **Add database**. Create a database and user. Save the name, user, and password. Do not use `127.0.0.1` as the host.

## 2. Build assets on your Windows PC

`public/build` is not in git. Compile it locally, then upload it.

```powershell
cd C:\laragon\www\attendancemonitoringsystem
npm ci
npm run build
```

Leave `node_modules` on the PC. Do not copy it to Serv00.

## 3. SSH: put Laravel in the domain folder

From Windows Terminal or PuTTY:

```bash
ssh LOGIN@sX.serv00.com
```

On the server, keep Laravel's `public` folder and point Serv00 at it with a symlink so later `git pull` still works:

```bash
cd ~/domains/LOGIN.serv00.net
rm -rf public_html
git clone https://github.com/kumaru06/attendancemonitoringsystem.git .
ln -sfn public public_html
chmod -R u+rwX storage bootstrap/cache
```

If `git clone` refuses a non-empty folder, move leftover files aside first, then clone.

If the site returns 403 after the symlink, use a rename instead:

```bash
mv public public_html
```

The app already treats `public_html` as the public path when that directory exists.

This repo must already contain that path support (`bootstrap/app.php` and `AppServiceProvider`). Clone after those changes are on GitHub, or upload the project with WinSCP instead.

## 4. Environment file

```bash
cd ~/domains/LOGIN.serv00.net
cp .env.example .env
nano .env
```

Set at least:

```env
APP_NAME="Student Attendance"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://LOGIN.serv00.net
APP_TIMEZONE=Asia/Manila

DB_CONNECTION=mysql
DB_HOST=mysqlX.serv00.com
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
LOG_LEVEL=error
```

- Fresh school on Serv00: generate a new key in the next step.
- Moving students and QR cards from Laragon: copy the local `APP_KEY` into this `.env`. A new key invalidates stored QR tokens.

Optional PHP limits (create `~/domains/LOGIN.serv00.net/public_html/.user.ini`):

```ini
memory_limit = 256M
upload_max_filesize = 8M
post_max_size = 8M
max_execution_time = 180
```

If the site is not running PHP 8.3, uncomment this line in `public_html/.htaccess`:

```apache
AddType application/x-httpd-php83 .php
```

Do not uncomment that line in the Laragon copy of the project.

## 5. Composer, key, migrate, admin

```bash
cd ~/domains/LOGIN.serv00.net
php83 /usr/local/bin/composer2 install --no-dev --optimize-autoloader
php83 artisan key:generate --force
php83 artisan migrate --force
php83 artisan admin:create
php83 artisan config:cache
php83 artisan route:cache
php83 artisan view:cache
```

Skip `key:generate --force` if you pasted a Laragon `APP_KEY`.

Create scanner staff in the admin **Users** page after you can sign in.

## 6. Upload `public/build` and turn on HTTPS

From PowerShell on the PC (after `npm run build`):

```powershell
scp -r C:\laragon\www\attendancemonitoringsystem\public\build LOGIN@sX.serv00.com:domains/LOGIN.serv00.net/public_html/build
```

WinSCP works the same: upload the local `public\build` folder into server `public_html/build`.

In DevilWEB: **SSL** → **WWW websites** → **Manage** → **Add certificate** → **Generate Let's Encrypt certificate** for `LOGIN.serv00.net`.

Then:

```bash
devil www options LOGIN.serv00.net sslonly on
```

Or use **Force SSL** in website details.

## 7. Check the live site

1. Open `https://LOGIN.serv00.net/` and sign in as admin.
2. Add a section and a student, print a QR card.
3. Open `https://LOGIN.serv00.net/scanner` on the same HTTPS origin. Allow the camera.
4. Visit `/up` if you need a quick health check.

The camera works on Serv00 because the site is HTTPS. It still will not work on plain `http://LOGIN.serv00.net`.

## Later updates

On the PC: commit, push, `npm run build`.

On the server:

```bash
cd ~/domains/LOGIN.serv00.net
git pull
php83 /usr/local/bin/composer2 install --no-dev --optimize-autoloader
php83 artisan migrate --force
php83 artisan config:cache
php83 artisan route:cache
php83 artisan view:cache
```

Upload `public/build` again whenever frontend files change.

If you renamed `public` to `public_html` instead of using a symlink, `git pull` may look for `public/` again. Recreate the symlink or copy new files from `public/` into `public_html/`.

## Common errors

| Symptom | Fix |
| --- | --- |
| 500 / `open_basedir` | Set open basedir to `/usr/home/LOGIN/domains/` |
| Composer / Artisan say PHP is too old | Always use `php83` and `php83 /usr/local/bin/composer2` |
| Blank CSS / `Unable to locate file in Vite manifest` | Upload `public/build` from the PC |
| Camera list empty | Use `https://`, not `http://` |
| Login page looks stuck / old HTML | Website cache must be off |
| Cannot write logs or sessions | `chmod -R u+rwX storage bootstrap/cache` |
| QR cards stop working after deploy | Restore the original `APP_KEY` |

Official references: [WWW](https://docs.serv00.com/WWW/), [PHP](https://docs.serv00.com/PHP/), [MySQL](https://docs.serv00.com/MySQL/), [SSL](https://docs.serv00.com/SSL/).
