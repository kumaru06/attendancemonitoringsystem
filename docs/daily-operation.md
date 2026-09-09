# Daily operation

## Before students arrive

1. Confirm the Windows clock is correct and set to Philippine time. Attendance dates use server time in `Asia/Manila`.
2. Start Laragon MySQL and Apache, or run `php artisan serve`.
3. Sign in as administrator or scanner staff.
4. Open Scanner on a camera-safe URL: `http://127.0.0.1:8000` (`php artisan serve`) or HTTPS. Chrome blocks the webcam on `http://attendancemonitoringsystem.test`.
5. Choose the webcam, click **Start scanning**, and allow camera access if the browser asks.

Do not run `npm run dev`. Do not run migrations or seeders as part of morning startup.

## Recording attendance

- First valid scan of the day stores **Present** and the time-in.
- A repeat scan keeps the original time-in and shows **Already checked in today**.
- Invalid or replaced QR codes show **Invalid QR code**.
- Inactive students are rejected. Their history stays in reports.

Staff should compare the on-screen photo with the person at the gate. A printed QR can be shared; the photo is a visual check, not proof of identity.

## After dismissal

Sign out. Stopping the scanner also stops the camera. You can leave MySQL and Apache running if this computer stays dedicated to attendance.

## HTTPS later

Local HTTP sessions use `SESSION_SECURE_COOKIE=false`. If you later serve the app on trusted HTTPS, set `SESSION_SECURE_COOKIE=true` so the session cookie is sent only over HTTPS.
