const sidebar = document.getElementById('app-sidebar');
const backdrop = document.getElementById('sidebar-backdrop');

const openSidebar = () => {
    sidebar?.classList.remove('-translate-x-full');
    backdrop?.classList.remove('hidden');
};

const closeSidebar = () => {
    sidebar?.classList.add('-translate-x-full');
    backdrop?.classList.add('hidden');
};

document.querySelectorAll('[data-sidebar-open]').forEach((button) => {
    button.addEventListener('click', openSidebar);
});

document.querySelectorAll('[data-sidebar-close]').forEach((button) => {
    button.addEventListener('click', closeSidebar);
});

document.querySelectorAll('[data-open-modal]').forEach((button) => {
    button.addEventListener('click', () => {
        const modal = document.getElementById(button.getAttribute('data-open-modal'));
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
    });
});

document.querySelectorAll('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => {
        const modal = button.closest('[role="dialog"]');
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    });
});

document.querySelectorAll('[data-photo-input]').forEach((input) => {
    input.addEventListener('change', () => {
        const preview = document.getElementById('photo-preview');
        const file = input.files?.[0];

        if (! preview || ! file) {
            return;
        }

        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
        preview.classList.remove('hidden');
    });
});

const clock = document.getElementById('ph-clock');

if (clock?.dataset.iso) {
    const started = Date.parse(clock.dataset.iso);
    const offset = started - Date.now();

    const tick = () => {
        const now = new Date(Date.now() + offset);
        clock.textContent = new Intl.DateTimeFormat('en-PH', {
            weekday: 'long',
            month: 'long',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
            timeZone: 'Asia/Manila',
        }).format(now);
    };

    tick();
    window.setInterval(tick, 30000);
}
