export const MOBILE_REPORT_MONTHS = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const MOBILE_REPORT_PROFILES = {
    baggage: {
        machineName: 'X-Ray Baggage HI-Scan 100100T',
        totalActivities: 12,
        completed: 186,
        conform: 172,
        nonconform: 14,
        notScheduled: 42,
        completionPercentage: 92,
    },
    cabin: {
        machineName: 'X-Ray Cabin',
        totalActivities: 10,
        completed: 154,
        conform: 145,
        nonconform: 9,
        notScheduled: 36,
        completionPercentage: 91,
    },
    cargo: {
        machineName: 'X-Ray Cargo',
        totalActivities: 9,
        completed: 118,
        conform: 110,
        nonconform: 8,
        notScheduled: 48,
        completionPercentage: 88,
    },
};

const MOBILE_REPORT_DETAILS = {
    'lead-curtain': {
        key: 'lead-curtain',
        name: 'Lead curtain',
        criteria: 'Tidak robek dan menutup dengan baik.',
        conform: 26,
        nonconform: 1,
        notScheduled: 4,
        status: 'Perlu Review',
        statusModifier: 'review',
        dates: [
            { day: 1, status: 'conform', label: 'Sesuai' },
            { day: 2, status: 'conform', label: 'Sesuai' },
            { day: 3, status: 'not-scheduled', label: 'Tidak Dijadwalkan' },
            { day: 4, status: 'nonconform', label: 'Tidak Sesuai' },
            { day: 5, status: 'pending', label: 'Belum Diperiksa' },
        ],
    },
    'conveyor-belt': {
        key: 'conveyor-belt',
        name: 'Conveyor belt',
        criteria: 'Berjalan normal dan tidak tersendat.',
        conform: 28,
        nonconform: 0,
        notScheduled: 3,
        status: 'Normal',
        statusModifier: 'normal',
        dates: [
            { day: 1, status: 'conform', label: 'Sesuai' },
            { day: 2, status: 'conform', label: 'Sesuai' },
            { day: 3, status: 'conform', label: 'Sesuai' },
            { day: 4, status: 'conform', label: 'Sesuai' },
            { day: 5, status: 'pending', label: 'Belum Diperiksa' },
        ],
    },
    ups: {
        key: 'ups',
        name: 'UPS',
        criteria: 'Backup daya bekerja baik.',
        conform: 24,
        nonconform: 2,
        notScheduled: 5,
        status: 'Ada Temuan',
        statusModifier: 'finding',
        dates: [
            { day: 1, status: 'not-scheduled', label: 'Tidak Dijadwalkan' },
            { day: 2, status: 'not-scheduled', label: 'Tidak Dijadwalkan' },
            { day: 3, status: 'not-scheduled', label: 'Tidak Dijadwalkan' },
            { day: 4, status: 'nonconform', label: 'Tidak Sesuai' },
            { day: 5, status: 'pending', label: 'Belum Diperiksa' },
        ],
    },
};

const MOBILE_REPORT_ACTION_MESSAGES = {
    pdf: 'PDF lebih disarankan dicetak melalui tampilan desktop.',
    excel: 'Export Excel berhasil disimulasikan.',
};

const MOBILE_REPORT_MODE_LIST = ['summary', 'detail', 'table'];
const MOBILE_REPORT_MODES = new Set(MOBILE_REPORT_MODE_LIST);

export const getMobileReportSnapshot = ({ machine, month, year }) => {
    const machineKey = Object.hasOwn(MOBILE_REPORT_PROFILES, machine) ? machine : 'baggage';
    const numericMonth = Number(month);
    const selectedMonth = MOBILE_REPORT_MONTHS[numericMonth - 1] ? numericMonth : 10;
    const numericYear = Number(year);
    const selectedYear = Number.isInteger(numericYear) ? numericYear : 2026;
    const profile = MOBILE_REPORT_PROFILES[machineKey];
    const monthName = MOBILE_REPORT_MONTHS[selectedMonth - 1];

    return {
        machineKey,
        machineName: profile.machineName,
        month: selectedMonth,
        monthName,
        year: selectedYear,
        period: `${monthName} ${selectedYear}`,
        totalActivities: profile.totalActivities,
        completed: profile.completed,
        conform: profile.conform,
        nonconform: profile.nonconform,
        notScheduled: profile.notScheduled,
        completionPercentage: profile.completionPercentage,
    };
};

export const normalizeMobileReportMode = (mode) => (
    MOBILE_REPORT_MODES.has(mode) ? mode : 'summary'
);

export const getNextMobileReportMode = (currentMode, key) => {
    const normalizedMode = normalizeMobileReportMode(currentMode);
    const currentIndex = MOBILE_REPORT_MODE_LIST.indexOf(normalizedMode);

    if (key === 'Home') {
        return MOBILE_REPORT_MODE_LIST[0];
    }

    if (key === 'End') {
        return MOBILE_REPORT_MODE_LIST[MOBILE_REPORT_MODE_LIST.length - 1];
    }

    if (key === 'ArrowRight' || key === 'ArrowDown') {
        return MOBILE_REPORT_MODE_LIST[(currentIndex + 1) % MOBILE_REPORT_MODE_LIST.length];
    }

    if (key === 'ArrowLeft' || key === 'ArrowUp') {
        return MOBILE_REPORT_MODE_LIST[(currentIndex - 1 + MOBILE_REPORT_MODE_LIST.length) % MOBILE_REPORT_MODE_LIST.length];
    }

    return normalizedMode;
};

export const getMobileReportDetail = (activityKey) => (
    MOBILE_REPORT_DETAILS[activityKey] ?? MOBILE_REPORT_DETAILS['lead-curtain']
);

export const getMobileReportActionMessage = (action) => (
    MOBILE_REPORT_ACTION_MESSAGES[action] ?? 'Aksi laporan berhasil disimulasikan.'
);

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-mobile-report]');

        if (!root) {
            return;
        }

        const filterForm = root.querySelector('[data-mobile-report-filter]');
        const modeButtons = Array.from(root.querySelectorAll('[data-mobile-report-mode]'));
        const panels = Array.from(root.querySelectorAll('[data-mobile-report-panel]'));
        const toast = root.querySelector('[data-mobile-report-toast]');
        const toastMessage = root.querySelector('[data-mobile-report-toast-message]');
        const detailStatus = root.querySelector('[data-mobile-report-detail-status]');
        const progress = root.querySelector('[data-mobile-report-progress]');
        const progressBar = root.querySelector('[data-mobile-report-progress-bar]');
        let activeMode = 'summary';
        let activeDetailKey = 'lead-curtain';
        let currentSnapshot;
        let toastTimer;

        const setText = (selector, value) => {
            root.querySelectorAll(selector).forEach((element) => {
                element.textContent = value;
            });
        };

        const showToast = (message) => {
            if (!toast || !toastMessage) {
                return;
            }

            window.clearTimeout(toastTimer);
            toastMessage.textContent = message;
            toast.hidden = false;
            toastTimer = window.setTimeout(() => {
                toast.hidden = true;
            }, 4000);
        };

        const getFilters = () => ({
            machine: filterForm?.elements.machine.value ?? 'baggage',
            month: Number(filterForm?.elements.month.value ?? 10),
            year: Number(filterForm?.elements.year.value ?? 2026),
        });

        const renderSnapshot = (snapshot) => {
            currentSnapshot = snapshot;
            setText('[data-mobile-report-machine]', snapshot.machineName);
            setText('[data-mobile-report-period]', snapshot.period);
            setText('[data-mobile-report-total-activities]', snapshot.totalActivities);
            setText('[data-mobile-report-completed]', snapshot.completed);
            setText('[data-mobile-report-conform]', snapshot.conform);
            setText('[data-mobile-report-nonconform]', snapshot.nonconform);
            setText('[data-mobile-report-not-scheduled]', snapshot.notScheduled);
            setText('[data-mobile-report-percentage]', `${snapshot.completionPercentage}%`);

            if (progress) {
                progress.setAttribute('aria-valuenow', String(snapshot.completionPercentage));
            }

            if (progressBar) {
                progressBar.style.width = `${snapshot.completionPercentage}%`;
            }

            renderDetail(activeDetailKey);
        };

        const renderDateGrid = (detail) => {
            const dateGrid = root.querySelector('[data-mobile-report-date-grid]');

            if (!dateGrid) {
                return;
            }

            dateGrid.replaceChildren(...detail.dates.map((date) => {
                const item = document.createElement('article');
                const symbol = document.createElement('span');
                const content = document.createElement('div');
                const dateLabel = document.createElement('strong');
                const statusLabel = document.createElement('small');
                const symbols = {
                    conform: '✓',
                    nonconform: '×',
                    'not-scheduled': '−',
                    pending: '•',
                };

                item.className = 'mobile-report-date-item';
                symbol.className = `mobile-report-symbol mobile-report-symbol--${date.status}`;
                symbol.textContent = symbols[date.status];
                symbol.setAttribute('aria-hidden', 'true');
                dateLabel.textContent = `${String(date.day).padStart(2, '0')} ${currentSnapshot?.monthName ?? 'Oktober'}`;
                statusLabel.textContent = date.label;
                content.append(dateLabel, statusLabel);
                item.append(symbol, content);

                return item;
            }));
        };

        const renderDetail = (activityKey) => {
            activeDetailKey = activityKey;
            const detail = getMobileReportDetail(activityKey);

            setText('[data-mobile-report-detail-name]', detail.name);
            setText('[data-mobile-report-detail-criteria]', detail.criteria);
            setText('[data-mobile-report-detail-period]', currentSnapshot?.period ?? 'Oktober 2026');
            setText('[data-mobile-report-detail-conform]', detail.conform);
            setText('[data-mobile-report-detail-nonconform]', detail.nonconform);
            setText('[data-mobile-report-detail-not-scheduled]', detail.notScheduled);

            if (detailStatus) {
                detailStatus.textContent = detail.status;
                detailStatus.className = `mobile-report-badge mobile-report-badge--${detail.statusModifier}`;
            }

            renderDateGrid(detail);
        };

        const setMode = (requestedMode) => {
            activeMode = normalizeMobileReportMode(requestedMode);

            modeButtons.forEach((button) => {
                const isActive = button.dataset.mobileReportMode === activeMode;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', String(isActive));
                button.tabIndex = isActive ? 0 : -1;
            });

            panels.forEach((panel) => {
                panel.hidden = panel.dataset.mobileReportPanel !== activeMode;
            });

            if (activeMode === 'detail') {
                renderDetail(activeDetailKey);
            }

            if (activeMode === 'table') {
                const tableScroll = root.querySelector('[data-mobile-report-table-scroll]');

                if (tableScroll) {
                    tableScroll.scrollLeft = 0;
                }
            }
        };

        const openDetail = (activityKey) => {
            renderDetail(activityKey);
            setMode('detail');

            window.requestAnimationFrame(() => {
                const detailPanel = root.querySelector('[data-mobile-report-panel="detail"]');
                const closeButton = root.querySelector('[data-mobile-report-detail-close]');

                detailPanel?.scrollIntoView({ behavior: 'auto', block: 'start' });
                closeButton?.focus({ preventScroll: true });
            });
        };

        filterForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            const snapshot = getMobileReportSnapshot(getFilters());

            renderSnapshot(snapshot);
            showToast(`Laporan ${snapshot.period} berhasil ditampilkan.`);
        });

        filterForm?.addEventListener('reset', () => {
            window.setTimeout(() => {
                renderSnapshot(getMobileReportSnapshot(getFilters()));
                setMode('summary');
                showToast('Filter laporan dikembalikan ke periode awal.');
            }, 0);
        });

        modeButtons.forEach((button) => {
            button.addEventListener('click', () => setMode(button.dataset.mobileReportMode));
            button.addEventListener('keydown', (event) => {
                const nextMode = getNextMobileReportMode(button.dataset.mobileReportMode, event.key);

                if (nextMode === button.dataset.mobileReportMode) {
                    return;
                }

                event.preventDefault();
                setMode(nextMode);
                modeButtons.find((candidate) => candidate.dataset.mobileReportMode === nextMode)?.focus();
            });
        });

        root.querySelectorAll('[data-mobile-report-detail]').forEach((button) => {
            button.addEventListener('click', () => {
                openDetail(button.dataset.mobileReportDetail);
            });
        });

        root.querySelector('[data-mobile-report-detail-close]')?.addEventListener('click', () => {
            setMode('summary');
            root.querySelector(`[data-mobile-report-detail="${activeDetailKey}"]`)?.focus();
        });

        root.querySelectorAll('[data-mobile-report-action]').forEach((button) => {
            button.addEventListener('click', () => {
                showToast(getMobileReportActionMessage(button.dataset.mobileReportAction));
            });
        });

        root.querySelector('[data-mobile-report-toast-close]')?.addEventListener('click', () => {
            window.clearTimeout(toastTimer);

            if (toast) {
                toast.hidden = true;
            }
        });

        renderSnapshot(getMobileReportSnapshot(getFilters()));
        setMode(activeMode);
    });
}
