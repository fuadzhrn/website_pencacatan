export function filterSchedules(schedules, filters) {
    const filterKeys = ['month', 'year', 'machine', 'status'];

    return schedules.filter((schedule) => filterKeys.every((key) => {
        const selectedValue = filters[key] ?? '';

        return selectedValue === '' || schedule[key] === selectedValue;
    }));
}

export function summarizeSchedules(schedules) {
    return schedules.reduce((summary, schedule) => {
        summary.total += 1;

        if (schedule.status === 'scheduled') {
            summary.scheduled += 1;
        }

        if (schedule.status === 'completed') {
            summary.completed += 1;
        }

        if (schedule.status === 'overdue') {
            summary.overdue += 1;
        }

        return summary;
    }, {
        total: 0,
        scheduled: 0,
        completed: 0,
        overdue: 0,
    });
}

export function findScheduleById(schedules, scheduleId) {
    return schedules.find((schedule) => schedule.id === scheduleId) ?? null;
}

export function createStartMessage(schedule) {
    return `Pemeriksaan ${schedule.machineName} siap dimulai.`;
}

export function getTrappedFocusIndex(currentIndex, focusableCount, backwards) {
    if (focusableCount <= 0) {
        return -1;
    }

    if (currentIndex < 0) {
        return backwards ? focusableCount - 1 : 0;
    }

    return backwards
        ? (currentIndex - 1 + focusableCount) % focusableCount
        : (currentIndex + 1) % focusableCount;
}

function initializeMobileSchedules() {
    const page = document.querySelector('[data-mobile-schedules]');

    if (!page) {
        return;
    }

    const filterForm = page.querySelector('[data-mobile-schedules-filter]');
    const resetButton = page.querySelector('[data-mobile-schedules-reset]');
    const scheduleCards = [...page.querySelectorAll('[data-mobile-schedule-card]')];
    const scheduleList = page.querySelector('[data-mobile-schedules-list]');
    const emptyState = page.querySelector('[data-mobile-schedules-empty]');
    const resultCount = page.querySelector('[data-mobile-schedules-result-count]');
    const sheet = page.querySelector('[data-mobile-schedule-sheet]');
    const sheetPanel = sheet?.querySelector('[data-mobile-schedule-sheet-panel]');
    const sheetCloseButtons = sheet ? [...sheet.querySelectorAll('[data-mobile-schedule-sheet-close]')] : [];
    const toast = page.querySelector('[data-mobile-schedules-toast]');
    const toastMessage = toast?.querySelector('[data-mobile-schedules-toast-message]');
    const toastClose = toast?.querySelector('[data-mobile-schedules-toast-close]');
    let lastFocusedElement = null;
    let toastTimer = null;
    const backgroundInertStates = new Map();
    const mobileShell = page.closest('[data-mobile-shell]');
    const backgroundElements = [
        ...[...page.children].filter((element) => element !== sheet),
        ...[...(mobileShell?.children ?? [])].filter((element) => element !== page),
    ];

    const schedules = scheduleCards.map((card) => ({
        id: card.dataset.id,
        month: card.dataset.month,
        year: card.dataset.year,
        machine: card.dataset.machine,
        status: card.dataset.status,
        machineName: card.dataset.machineName,
        brand: card.dataset.brand,
        type: card.dataset.type,
        serialNumber: card.dataset.serialNumber,
        location: card.dataset.location,
        date: card.dataset.date,
        time: card.dataset.time,
        frequency: card.dataset.frequency,
        officer: card.dataset.officer,
        statusLabel: card.dataset.statusLabel,
        note: card.dataset.note,
    }));

    function readFilters() {
        const formData = new FormData(filterForm);

        return {
            month: formData.get('month')?.toString() ?? '',
            year: formData.get('year')?.toString() ?? '',
            machine: formData.get('machine')?.toString() ?? '',
            status: formData.get('status')?.toString() ?? '',
        };
    }

    function updateSummary(visibleSchedules) {
        const summary = summarizeSchedules(visibleSchedules);

        Object.entries(summary).forEach(([key, value]) => {
            const output = page.querySelector(`[data-mobile-schedules-summary="${key}"]`);

            if (output) {
                output.textContent = value.toString();
            }
        });
    }

    function applyFilters() {
        const visibleSchedules = filterSchedules(schedules, readFilters());
        const visibleIds = new Set(visibleSchedules.map((schedule) => schedule.id));

        scheduleCards.forEach((card) => {
            card.hidden = !visibleIds.has(card.dataset.id);
        });

        updateSummary(visibleSchedules);

        if (emptyState) {
            emptyState.hidden = visibleSchedules.length > 0;
        }

        if (scheduleList) {
            scheduleList.hidden = visibleSchedules.length === 0;
        }

        if (resultCount) {
            resultCount.textContent = `${visibleSchedules.length} jadwal ditampilkan`;
        }
    }

    function closeToast() {
        if (!toast) {
            return;
        }

        window.clearTimeout(toastTimer);
        toast.hidden = true;
    }

    function showToast(message) {
        if (!toast || !toastMessage) {
            return;
        }

        window.clearTimeout(toastTimer);
        toastMessage.textContent = message;
        toast.hidden = false;
        toastTimer = window.setTimeout(closeToast, 4200);
    }

    function setDetailValue(field, value) {
        const output = sheet?.querySelector(`[data-mobile-schedule-detail="${field}"]`);

        if (output) {
            output.textContent = value;
        }
    }

    function openDetail(scheduleId, trigger) {
        const schedule = findScheduleById(schedules, scheduleId);

        if (!sheet || !schedule) {
            return;
        }

        lastFocusedElement = trigger;
        setDetailValue('machineName', schedule.machineName);
        setDetailValue('brand', schedule.brand);
        setDetailValue('type', schedule.type);
        setDetailValue('serialNumber', schedule.serialNumber);
        setDetailValue('location', schedule.location);
        setDetailValue('date', `${schedule.date}, ${schedule.time}`);
        setDetailValue('frequency', schedule.frequency);
        setDetailValue('officer', schedule.officer);
        setDetailValue('note', schedule.note);

        const statusBadge = sheet.querySelector('[data-mobile-schedule-detail="status"]');

        if (statusBadge) {
            statusBadge.textContent = schedule.statusLabel;
            statusBadge.className = `mobile-schedules-badge mobile-schedules-badge--${schedule.status}`;
        }

        sheet.hidden = false;
        sheet.setAttribute('aria-hidden', 'false');
        backgroundElements.forEach((element) => {
            backgroundInertStates.set(element, element.inert);
            element.inert = true;
        });
        document.body.classList.add('mobile-schedules-sheet-open');
        window.requestAnimationFrame(() => {
            sheet.classList.add('is-open');
            sheetPanel?.focus();
        });
    }

    function closeDetail() {
        if (!sheet || sheet.hidden) {
            return;
        }

        sheet.classList.remove('is-open');
        sheet.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('mobile-schedules-sheet-open');

        window.setTimeout(() => {
            sheet.hidden = true;
            backgroundElements.forEach((element) => {
                element.inert = backgroundInertStates.get(element) ?? false;
            });
            backgroundInertStates.clear();
            lastFocusedElement?.focus();
            lastFocusedElement = null;
        }, 220);
    }

    filterForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        applyFilters();
    });

    resetButton?.addEventListener('click', () => {
        filterForm.reset();
        applyFilters();
    });

    page.addEventListener('click', (event) => {
        const detailButton = event.target.closest('[data-mobile-schedule-detail-open]');
        const startButton = event.target.closest('[data-mobile-schedule-start]');

        if (detailButton) {
            openDetail(detailButton.dataset.scheduleId, detailButton);
        }

        if (startButton) {
            const schedule = findScheduleById(schedules, startButton.dataset.scheduleId);

            if (schedule) {
                showToast(createStartMessage(schedule));
            }
        }
    });

    sheetCloseButtons.forEach((button) => button.addEventListener('click', closeDetail));
    toastClose?.addEventListener('click', closeToast);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sheet && !sheet.hidden) {
            closeDetail();
        }

        if (event.key === 'Tab' && sheetPanel && sheet && !sheet.hidden) {
            const focusableElements = [...sheetPanel.querySelectorAll(
                'button:not([disabled]):not([tabindex="-1"]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
            )];
            const currentIndex = focusableElements.indexOf(document.activeElement);
            const nextIndex = getTrappedFocusIndex(currentIndex, focusableElements.length, event.shiftKey);

            if (nextIndex >= 0) {
                event.preventDefault();
                focusableElements[nextIndex].focus();
            }
        }
    });

    applyFilters();
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeMobileSchedules, { once: true });
    } else {
        initializeMobileSchedules();
    }
}
