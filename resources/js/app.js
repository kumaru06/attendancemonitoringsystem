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

const studentFormPanel = document.getElementById('student-form-panel');

if (studentFormPanel) {
    const studentNumberInput = studentFormPanel.querySelector('#create-student_number');

    const closeStudentFormPanel = () => {
        studentFormPanel.classList.remove('is-open');
        studentFormPanel.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    };

    const openStudentFormPanel = () => {
        studentFormPanel.classList.add('is-open');
        studentFormPanel.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        studentNumberInput?.focus();
    };

    if (studentFormPanel.classList.contains('is-open')) {
        document.body.classList.add('overflow-hidden');
        studentNumberInput?.focus();
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-student-form-panel]');

        if (! trigger) {
            return;
        }

        event.preventDefault();
        openStudentFormPanel();
    });

    studentFormPanel.querySelectorAll('[data-student-form-panel-close]').forEach((control) => {
        control.addEventListener('click', closeStudentFormPanel);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || ! studentFormPanel.classList.contains('is-open')) {
            return;
        }

        closeStudentFormPanel();
    });
}

const studentDirectoryHost = document.getElementById('student-directory-host');

if (studentDirectoryHost) {
    const levelNav = document.querySelector('[data-student-levels]');
    const searchInput = document.getElementById('student-search');
    let directoryRequest = null;
    let searchTimer = null;

    const withDirectoryParam = (href) => {
        const url = new URL(href, window.location.origin);
        url.searchParams.set('directory', '1');

        return url.toString();
    };

    const publicDirectoryPath = (href) => {
        const url = new URL(href, window.location.origin);
        url.searchParams.delete('directory');

        return `${url.pathname}${url.search}`;
    };

    const applySearchToHref = (href, { resetPage = false } = {}) => {
        const url = new URL(href, window.location.origin);
        const query = searchInput?.value.trim() || '';

        if (query) {
            url.searchParams.set('search', query);
        } else {
            url.searchParams.delete('search');
        }

        if (resetPage) {
            url.searchParams.delete('page');
        }

        return url.toString();
    };

    const paintLevelButtons = (selectedLevel) => {
        levelNav?.querySelectorAll('[data-student-level]').forEach((link) => {
            const isActive = (link.dataset.studentLevel || '') === selectedLevel;
            link.className = isActive ? link.dataset.activeClass : link.dataset.idleClass;
            link.setAttribute('aria-current', isActive ? 'page' : 'false');
        });
    };

    const loadStudentDirectory = async (href, push = true) => {
        directoryRequest?.abort();
        directoryRequest = new AbortController();

        const current = document.getElementById('student-directory');
        current?.classList.add('pointer-events-none', 'opacity-50');

        try {
            const response = await fetch(withDirectoryParam(href), {
                headers: {
                    Accept: 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: directoryRequest.signal,
            });

            if (! response.ok) {
                return;
            }

            const html = await response.text();
            const wrap = document.createElement('div');
            wrap.innerHTML = html.trim();
            const next = wrap.querySelector('#student-directory') || wrap.firstElementChild;

            if (next && current) {
                current.replaceWith(next);
                bindModalTriggers(next);
            }

            if (push) {
                window.history.pushState({ studentDirectory: true }, '', publicDirectoryPath(href));
            }

            const nextUrl = new URL(href, window.location.origin);
            paintLevelButtons(nextUrl.searchParams.get('level') || '');

            if (searchInput && document.activeElement !== searchInput) {
                searchInput.value = nextUrl.searchParams.get('search') || '';
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                current?.classList.remove('pointer-events-none', 'opacity-50');
            }
        }
    };

    document.addEventListener('click', (event) => {
        const levelLink = event.target.closest('[data-student-levels] [data-student-level]');

        if (levelLink) {
            event.preventDefault();
            loadStudentDirectory(applySearchToHref(levelLink.href, { resetPage: true }));
            return;
        }

        const pageLink = event.target.closest('#student-directory [data-student-directory-pager] a[href]');

        if (pageLink) {
            event.preventDefault();
            loadStudentDirectory(pageLink.href);
        }
    });

    searchInput?.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => {
            loadStudentDirectory(applySearchToHref(window.location.href, { resetPage: true }));
        }, 280);
    });

    window.addEventListener('popstate', () => {
        if (! document.getElementById('student-directory-host')) {
            return;
        }

        loadStudentDirectory(window.location.href, false);
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

const sectionFormPanel = document.getElementById('section-form-panel');

if (sectionFormPanel) {
    const createNameInput = sectionFormPanel.querySelector('#create-section-name');

    const closeSectionFormPanel = () => {
        sectionFormPanel.classList.remove('is-open');
        sectionFormPanel.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    };

    const openSectionFormPanel = () => {
        sectionFormPanel.classList.add('is-open');
        sectionFormPanel.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        createNameInput?.focus();
    };

    if (sectionFormPanel.classList.contains('is-open')) {
        document.body.classList.add('overflow-hidden');
        createNameInput?.focus();
    }

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-section-form-panel]');

        if (! trigger) {
            return;
        }

        event.preventDefault();
        openSectionFormPanel();
    });

    sectionFormPanel.querySelectorAll('[data-section-form-panel-close]').forEach((control) => {
        control.addEventListener('click', closeSectionFormPanel);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || ! sectionFormPanel.classList.contains('is-open')) {
            return;
        }

        closeSectionFormPanel();
    });
}

document.querySelectorAll('[data-photo-input]').forEach((input) => {
    input.addEventListener('change', () => {
        const preview = input.form?.querySelector('[data-photo-preview]') ?? document.getElementById('photo-preview');
        const file = input.files?.[0];

        if (! preview || ! file) {
            return;
        }

        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
        preview.classList.remove('hidden');
    });
});

const clockFormats = {
    datetime: {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
        timeZone: 'Asia/Manila',
    },
    date: {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        timeZone: 'Asia/Manila',
    },
    time: {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
        timeZone: 'Asia/Manila',
    },
};

document.querySelectorAll('[data-ph-clock]').forEach((clock) => {
    if (! clock.dataset.iso) {
        return;
    }

    const started = Date.parse(clock.dataset.iso);
    const offset = started - Date.now();
    const options = clockFormats[clock.dataset.clockFormat] ?? clockFormats.datetime;

    const tick = () => {
        const now = new Date(Date.now() + offset);
        clock.textContent = new Intl.DateTimeFormat('en-PH', options).format(now);
    };

    tick();
    window.setInterval(tick, 30000);
});

const commandPalette = document.getElementById('command-palette');
const commandInput = document.getElementById('command-palette-input');
const commandResults = document.getElementById('command-palette-results');

if (commandPalette && commandInput && commandResults) {
    const searchUrl = commandPalette.getAttribute('data-search-url');
    let searchTimer = null;
    let searchRequest = null;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';

        return div.innerHTML;
    };

    const renderGroup = (title, items) => {
        if (! items.length) {
            return '';
        }

        const rows = items.map((item) => {
            const hint = item.hint ? `<span class="block truncate text-xs text-slate-400">${escapeHtml(item.hint)}</span>` : '';

            return `<a href="${escapeHtml(item.url)}" class="block px-4 py-2.5 hover:bg-slate-50"><span class="block truncate font-medium text-slate-800">${escapeHtml(item.name ?? item.label)}</span>${hint}</a>`;
        }).join('');

        return `<p class="px-4 pb-1 pt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">${escapeHtml(title)}</p>${rows}`;
    };

    const renderResults = (payload) => {
        const html = [
            renderGroup('Pages', payload.jumps ?? []),
            renderGroup('Students', payload.students ?? []),
            renderGroup('Sections', payload.sections ?? []),
        ].join('');

        commandResults.innerHTML = html || '<p class="px-4 py-6 text-center text-slate-400">No matches.</p>';
    };

    const runSearch = async (query) => {
        if (! searchUrl) {
            return;
        }

        searchRequest?.abort();
        searchRequest = new AbortController();

        try {
            const url = new URL(searchUrl, window.location.origin);
            if (query) {
                url.searchParams.set('q', query);
            }

            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: searchRequest.signal,
            });

            if (! response.ok) {
                throw new Error('Search failed');
            }

            renderResults(await response.json());
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            commandResults.innerHTML = '<p class="px-4 py-6 text-center text-slate-400">Search is unavailable.</p>';
        }
    };

    const openPalette = () => {
        commandPalette.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        commandInput.value = '';
        commandResults.innerHTML = '<p class="px-4 py-6 text-center text-slate-400">Type to search students, sections, or pages.</p>';
        window.setTimeout(() => commandInput.focus(), 0);
        runSearch('');
    };

    const closePalette = () => {
        searchRequest?.abort();
        commandPalette.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    document.querySelectorAll('[data-search-open]').forEach((button) => {
        button.addEventListener('click', openPalette);
    });

    commandPalette.querySelectorAll('[data-search-close]').forEach((button) => {
        button.addEventListener('click', closePalette);
    });

    commandInput.addEventListener('input', () => {
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => {
            runSearch(commandInput.value.trim());
        }, 200);
    });

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            openPalette();
        }

        if (event.key === 'Escape' && ! commandPalette.classList.contains('hidden')) {
            closePalette();
        }
    });
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

