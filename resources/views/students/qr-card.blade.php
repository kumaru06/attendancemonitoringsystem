<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR card | {{ $student->student_number }}</title>
    <style>
        body { font-family: "Segoe UI", ui-sans-serif, system-ui, sans-serif; background: #f1f5f9; color: #0f172a; margin: 0; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { width: 360px; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 28px; text-align: center; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06); }
        .qr { margin: 16px auto; width: 240px; }
        .qr svg { width: 100%; height: auto; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        p { margin: 0; color: #475569; }
        .actions { margin-top: 20px; display: flex; gap: 8px; justify-content: center; }
        button, a { border: 1px solid #cbd5e1; background: #fff; border-radius: 8px; padding: 8px 12px; font-size: 14px; text-decoration: none; color: #0f172a; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .actions { display: none; }
            .card { box-shadow: none; border-color: #cbd5e1; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <p style="font-size:12px; letter-spacing:.08em; text-transform:uppercase; color:#64748b;">Student QR card</p>
            <div class="qr">{!! $svg !!}</div>
            <h1>{{ $student->full_name }}</h1>
            <p>{{ $student->student_number }}</p>
            <p>{{ $student->section?->name }}</p>
            <div class="actions">
                <button type="button" onclick="window.print()">Print</button>
                <a href="{{ route('students.show', $student) }}">Back</a>
            </div>
        </div>
    </div>
</body>
</html>
