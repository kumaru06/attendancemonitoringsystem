@props(['name', 'class' => 'h-5 w-5'])

@php
    $icons = [
        'home' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'users' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M12 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM20 19v-1a3.5 3.5 0 0 0-2.5-3.35M16.5 4.15a3 3 0 0 1 0 5.7"/>',
        'camera' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Zm8 9.5A3.5 3.5 0 1 0 12 10a3.5 3.5 0 0 0 0 7.5Z"/>',
        'clipboard' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 5h6a2 2 0 0 1 2 2v12H7V7a2 2 0 0 1 2-2Zm0 0V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1M8 11h8M8 15h5"/>',
        'qr' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 4h6v6H4zm10 0h6v6h-6zM4 14h6v6H4zm10 4h2m2 0h2m-6-4h2m2 0h2"/>',
        'cog' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm8.2-3.7-.9-.3a6.7 6.7 0 0 0-.4-1l.6-.8-1.4-1.4-.8.6a6.7 6.7 0 0 0-1-.4l-.3-.9h-2l-.3.9a6.7 6.7 0 0 0-1 .4l-.8-.6-1.4 1.4.6.8a6.7 6.7 0 0 0-.4 1l-.9.3v2l.9.3c.1.35.24.68.4 1l-.6.8 1.4 1.4.8-.6c.32.16.65.3 1 .4l.3.9h2l.3-.9c.35-.1.68-.24 1-.4l.8.6 1.4-1.4-.6-.8c.16-.32.3-.65.4-1l.9-.3v-2Z"/>',
        'logout' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12H4m0 0 3-3m-3 3 3 3m5-10h5a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-5"/>',
        'search' => '<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M17 10.5A6.5 6.5 0 1 1 4 10.5a6.5 6.5 0 0 1 13 0Z"/>',
        'plus' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>',
        'download' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0 4-4m-4 4-4-4M5 19h14"/>',
        'print' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 8V4h10v4M7 17H5a1 1 0 0 1-1-1v-5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v5a1 1 0 0 1-1 1h-2m-10 0v3h10v-3"/>',
        'refresh' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 12a8 8 0 0 1 13.7-5.6L20 8M20 4v4h-4M20 12a8 8 0 0 1-13.7 5.6L4 16m0 4v-4h4"/>',
        'check' => '<path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/>',
        'alert' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 4.3 2.8 17.1A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-2.9L13.7 4.3a2 2 0 0 0-3.4 0Z"/>',
        'menu' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/>',
        'clock' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>',
        'x' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18"/>',
        'user' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16 19v-1a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v1M12 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
    {!! $icons[$name] ?? $icons['alert'] !!}
</svg>
