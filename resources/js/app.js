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

const bindModalTriggers = (root = document) => {
    root.querySelectorAll('[data-open-modal]').forEach((button) => {
        if (button.dataset.modalBound === 'true') {
            return;
        }

        button.dataset.modalBound = 'true';
        button.addEventListener('click', () => {
            button.closest('[data-action-menu]')?.removeAttribute('open');
            const modal = document.getElementById(button.getAttribute('data-open-modal'));
            modal?.classList.remove('hidden');
            modal?.classList.add('flex');
        });
    });

    root.querySelectorAll('[data-modal-close]').forEach((button) => {
        if (button.dataset.modalCloseBound === 'true') {
            return;
        }

        button.dataset.modalCloseBound = 'true';
        button.addEventListener('click', () => {
            const modal = button.closest('[role="dialog"]');
            modal?.classList.add('hidden');
            modal?.classList.remove('flex');
        });
    });
};

bindModalTriggers();

const studentPanel = document.getElementById('student-panel');

if (studentPanel) {
    const panelBody = studentPanel.querySelector('[data-student-panel-body]');
    const panelTitle = studentPanel.querySelector('#student-panel-title');
    const closeButton = studentPanel.querySelector('[data-student-panel-close][aria-label="Close"]');
    let panelRequest = null;

    const closeNestedPanelModals = () => {
        studentPanel.querySelectorAll('[data-student-panel-modal]').forEach((modal) => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        });
    };

    const closeStudentPanel = () => {
        panelRequest?.abort();
        closeNestedPanelModals();
        studentPanel.classList.remove('is-open');
        studentPanel.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    };

    const openStudentPanel = async (url) => {
        const panelUrl = new URL(url, window.location.origin);
        panelUrl.searchParams.set('panel', '1');

        panelRequest?.abort();
        panelRequest = new AbortController();

        studentPanel.classList.add('is-open');
        studentPanel.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        closeButton?.focus();

        if (panelTitle) {
            panelTitle.textContent = 'Student profile';
        }

        studentPanel.querySelectorAll('[data-student-panel-modal]').forEach((modal) => {
            modal.remove();
        });

        if (panelBody) {
            panelBody.innerHTML = '<div class="grid gap-5 lg:grid-cols-[19rem_minmax(0,1fr)]"><div class="h-80 animate-pulse rounded-[1.75rem] bg-white/80"></div><div class="h-80 animate-pulse rounded-[1.75rem] bg-white/80"></div></div>';
        }

        try {
            const response = await fetch(panelUrl.toString(), {
                signal: panelRequest.signal,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
            });

            if (! response.ok) {
                throw new Error('Unable to load student');
            }

            if (panelBody) {
                panelBody.innerHTML = await response.text();

                panelBody.querySelectorAll('[role="dialog"]').forEach((modal) => {
                    modal.setAttribute('data-student-panel-modal', 'true');
                    studentPanel.appendChild(modal);
                });

                bindModalTriggers(studentPanel);

                const heading = panelBody.querySelector('h2');

                if (heading && panelTitle) {
                    panelTitle.textContent = heading.textContent.trim();
                }
            }
        } catch (error) {
            if (error?.name === 'AbortError') {
                return;
            }

            if (panelBody) {
                panelBody.innerHTML = '<p class="text-sm text-rose-700">The student record could not be opened. Try again.</p>';
            }
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-student-panel]');

        if (! link) {
            return;
        }

        event.preventDefault();
        link.closest('[data-action-menu]')?.removeAttribute('open');
        openStudentPanel(link.href);
    });

    studentPanel.querySelectorAll('[data-student-panel-close]').forEach((control) => {
        control.addEventListener('click', closeStudentPanel);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || ! studentPanel.classList.contains('is-open')) {
            return;
        }

        const nested = studentPanel.querySelector('[data-student-panel-modal].flex');

        if (nested) {
            nested.classList.add('hidden');
            nested.classList.remove('flex');

            return;
        }

        closeStudentPanel();
    });
}

const sectionPanel = document.getElementById('section-panel');

if (sectionPanel) {
    const sectionForm = sectionPanel.querySelector('[data-section-panel-form]');
    const sectionTitle = sectionPanel.querySelector('#section-panel-title');
    const sectionMeta = sectionPanel.querySelector('[data-section-panel-meta]');
    const sectionName = sectionPanel.querySelector('#section-panel-name');
    const sectionLevel = sectionPanel.querySelector('#section-panel-level');

    const closeSectionPanel = () => {
        sectionPanel.classList.remove('is-open');
        sectionPanel.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    };

    const openSectionPanel = (button) => {
        if (sectionForm) {
            sectionForm.action = button.dataset.action ?? '';
        }

        if (sectionName) {
            sectionName.value = button.dataset.name ?? '';
        }

        if (sectionLevel) {
            sectionLevel.value = button.dataset.level ?? '';
        }

        if (sectionTitle) {
            sectionTitle.textContent = button.dataset.name ?? 'Edit section';
        }

        if (sectionMeta) {
            const count = Number(button.dataset.students ?? 0);
            sectionMeta.textContent = `${count} ${count === 1 ? 'student' : 'students'}`;
        }

        sectionPanel.classList.add('is-open');
        sectionPanel.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        sectionName?.focus();
        sectionName?.select();
    };

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-section-panel]');

        if (! button) {
            return;
        }

        event.preventDefault();
        openSectionPanel(button);
    });

    sectionPanel.querySelectorAll('[data-section-panel-close]').forEach((control) => {
        control.addEventListener('click', closeSectionPanel);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || ! sectionPanel.classList.contains('is-open')) {
            return;
        }

        closeSectionPanel();
    });
}

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

document.querySelectorAll('[data-action-menu]').forEach((menu) => {
    menu.addEventListener('toggle', () => {
        if (! menu.open) {
            return;
        }

        document.querySelectorAll('[data-action-menu]').forEach((other) => {
            if (other !== menu) {
                other.removeAttribute('open');
            }
        });
    });
});

document.addEventListener('click', (event) => {
    document.querySelectorAll('[data-action-menu][open]').forEach((menu) => {
        if (! menu.contains(event.target)) {
            menu.removeAttribute('open');
        }
    });
});

