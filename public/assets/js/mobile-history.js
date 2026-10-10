export function filterHistory(historyRecords, filters) {
    const startDate = filters.startDate ?? '';
    const endDate = filters.endDate ?? '';

    if (startDate !== '' && endDate !== '' && startDate > endDate) {
        return [];
    }

    const query = (filters.query ?? '').trim().toLocaleLowerCase('id-ID');

    return historyRecords.filter((record) => {
        const matchesStartDate = startDate === '' || record.date >= startDate;
        const matchesEndDate = endDate === '' || record.date <= endDate;
        const matchesMachine = !filters.machine || record.machine === filters.machine;
        const matchesOfficer = !filters.officer || record.officer === filters.officer;
        const matchesStatus = !filters.status
            || record.resultStatus === filters.status
            || record.verificationStatus === filters.status;
        const matchesQuery = query === ''
            || record.searchableText.toLocaleLowerCase('id-ID').includes(query);

        return matchesStartDate
            && matchesEndDate
            && matchesMachine
            && matchesOfficer
            && matchesStatus
            && matchesQuery;
    });
}

export function summarizeHistory(historyRecords) {
    return historyRecords.reduce((summary, record) => {
        summary.total += 1;

        if (record.resultStatus === 'normal') {
            summary.normal += 1;
        }

        if (record.resultStatus === 'finding') {
            summary.finding += 1;
        }

        if (record.verificationStatus === 'pending') {
            summary.pending += 1;
        }

        return summary;
    }, {
        total: 0,
        normal: 0,
        finding: 0,
        pending: 0,
    });
}

export function findHistoryById(historyRecords, historyId) {
    return historyRecords.find((record) => record.id === historyId) ?? null;
}

export function createPrintMessage(historyRecord) {
    return `Dokumen pemeriksaan ${historyRecord.machineName} sedang disiapkan.`;
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

function parseChecklist(serializedChecklist) {
    try {
        const checklist = JSON.parse(serializedChecklist ?? '[]');

        return Array.isArray(checklist) ? checklist : [];
    } catch {
        return [];
    }
}

function initializeMobileHistory() {
    const page = document.querySelector('[data-mobile-history]');

    if (!page) {
        return;
    }

    const filterForm = page.querySelector('[data-mobile-history-filter]');
    const resetButton = page.querySelector('[data-mobile-history-reset]');
    const historyCards = [...page.querySelectorAll('[data-mobile-history-card]')];
    const historyList = page.querySelector('[data-mobile-history-list]');
    const emptyState = page.querySelector('[data-mobile-history-empty]');
    const resultCount = page.querySelector('[data-mobile-history-result-count]');
    const sheet = page.querySelector('[data-mobile-history-sheet]');
    const sheetPanel = sheet?.querySelector('[data-mobile-history-sheet-panel]');
    const sheetCloseButtons = sheet ? [...sheet.querySelectorAll('[data-mobile-history-sheet-close]')] : [];
    const sheetPrintButton = sheet?.querySelector('[data-mobile-history-sheet-print]');
    const checklistList = sheet?.querySelector('[data-mobile-history-checklist]');
    const checklistCount = sheet?.querySelector('[data-mobile-history-checklist-count]');
    const toast = page.querySelector('[data-mobile-history-toast]');
    const toastMessage = toast?.querySelector('[data-mobile-history-toast-message]');
    const toastCloseButton = toast?.querySelector('[data-mobile-history-toast-close]');
    const mobileShell = page.closest('[data-mobile-shell]');
    const backgroundInertStates = new Map();
    const backgroundElements = [
        ...[...page.children].filter((element) => element !== sheet && element !== toast),
        ...[...(mobileShell?.children ?? [])].filter((element) => element !== page),
    ];
    let activeHistoryRecord = null;
    let lastFocusedElement = null;
    let toastTimer = null;

    const historyRecords = historyCards.map((card) => ({
        id: card.dataset.id,
        date: card.dataset.date,
        dateLabel: card.dataset.dateLabel,
        machine: card.dataset.machine,
        machineName: card.dataset.machineName,
        brand: card.dataset.brand,
        type: card.dataset.type,
        serialNumber: card.dataset.serialNumber,
        location: card.dataset.location,
        frequency: card.dataset.frequency,
        officer: card.dataset.officer,
        officerName: card.dataset.officerName,
        resultStatus: card.dataset.resultStatus,
        resultLabel: card.dataset.resultLabel,
        verificationStatus: card.dataset.verificationStatus,
        verificationLabel: card.dataset.verificationLabel,
        note: card.dataset.note,
        supervisorNote: card.dataset.supervisorNote,
        searchableText: card.dataset.searchableText ?? '',
        checklist: parseChecklist(card.dataset.checklist),
    }));

    function readFilters() {
        const formData = new FormData(filterForm);

        return {
            startDate: formData.get('startDate')?.toString() ?? '',
            endDate: formData.get('endDate')?.toString() ?? '',
            machine: formData.get('machine')?.toString() ?? '',
            status: formData.get('status')?.toString() ?? '',
            officer: formData.get('officer')?.toString() ?? '',
            query: formData.get('query')?.toString() ?? '',
        };
    }

    function updateSummary(visibleHistoryRecords) {
        const summary = summarizeHistory(visibleHistoryRecords);

        Object.entries(summary).forEach(([key, value]) => {
            const output = page.querySelector(`[data-mobile-history-summary="${key}"]`);

            if (output) {
                output.textContent = value.toString();
            }
        });
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

    function applyFilters() {
        const filters = readFilters();
        const visibleHistoryRecords = filterHistory(historyRecords, filters);
        const visibleIds = new Set(visibleHistoryRecords.map((record) => record.id));

        historyCards.forEach((card) => {
            card.hidden = !visibleIds.has(card.dataset.id);
        });

        updateSummary(visibleHistoryRecords);

        if (historyList) {
            historyList.hidden = visibleHistoryRecords.length === 0;
        }

        if (emptyState) {
            emptyState.hidden = visibleHistoryRecords.length > 0;
        }

        if (resultCount) {
            resultCount.textContent = `${visibleHistoryRecords.length} pemeriksaan`;
        }

        if (filters.startDate !== '' && filters.endDate !== '' && filters.startDate > filters.endDate) {
            showToast('Tanggal mulai tidak boleh melewati tanggal akhir.');
        }
    }

    function setDetailValue(field, value) {
        const output = sheet?.querySelector(`[data-mobile-history-detail="${field}"]`);

        if (output) {
            output.textContent = value;
        }
    }

    function createChecklistCard(checklistItem) {
        const card = document.createElement('article');
        const header = document.createElement('header');
        const category = document.createElement('small');
        const result = document.createElement('span');
        const item = document.createElement('h4');
        const criteria = document.createElement('p');
        const note = document.createElement('p');
        const criteriaLabel = document.createElement('strong');
        const noteLabel = document.createElement('strong');

        category.textContent = checklistItem.category;
        result.className = `mobile-history-badge mobile-history-badge--${checklistItem.result_key}`;
        result.textContent = checklistItem.result;
        item.textContent = checklistItem.item;
        criteriaLabel.textContent = 'Kriteria: ';
        noteLabel.textContent = 'Catatan: ';
        criteria.append(criteriaLabel, checklistItem.criteria);
        note.append(noteLabel, checklistItem.note);
        header.append(category, result);
        card.append(header, item, criteria, note);

        return card;
    }

    function renderChecklist(checklist) {
        if (!checklistList) {
            return;
        }

        checklistList.replaceChildren(...checklist.map(createChecklistCard));

        if (checklistCount) {
            checklistCount.textContent = `${checklist.length} item`;
        }
    }

    function openDetail(historyId, trigger) {
        const historyRecord = findHistoryById(historyRecords, historyId);

        if (!sheet || !historyRecord) {
            return;
        }

        activeHistoryRecord = historyRecord;
        lastFocusedElement = trigger;
        setDetailValue('id', historyRecord.id);
        setDetailValue('machineName', historyRecord.machineName);
        setDetailValue('brand', historyRecord.brand);
        setDetailValue('type', historyRecord.type);
        setDetailValue('serialNumber', historyRecord.serialNumber);
        setDetailValue('location', historyRecord.location);
        setDetailValue('dateLabel', historyRecord.dateLabel);
        setDetailValue('frequency', historyRecord.frequency);
        setDetailValue('officerName', historyRecord.officerName);
        setDetailValue('verificationLabel', historyRecord.verificationLabel);
        setDetailValue('note', historyRecord.note);
        setDetailValue('supervisorNote', historyRecord.supervisorNote);

        const resultBadge = sheet.querySelector('[data-mobile-history-detail="result"]');
        const verificationBadge = sheet.querySelector('[data-mobile-history-detail="verification"]');

        if (resultBadge) {
            resultBadge.textContent = historyRecord.resultLabel;
            resultBadge.className = `mobile-history-badge mobile-history-badge--${historyRecord.resultStatus}`;
        }

        if (verificationBadge) {
            verificationBadge.textContent = historyRecord.verificationLabel;
            verificationBadge.className = `mobile-history-badge mobile-history-badge--${historyRecord.verificationStatus}`;
        }

        renderChecklist(historyRecord.checklist);
        sheet.hidden = false;
        sheet.setAttribute('aria-hidden', 'false');
        backgroundElements.forEach((element) => {
            backgroundInertStates.set(element, element.inert);
            element.inert = true;
        });
        document.body.classList.add('mobile-history-sheet-open');
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
        document.body.classList.remove('mobile-history-sheet-open');

        window.setTimeout(() => {
            sheet.hidden = true;
            backgroundElements.forEach((element) => {
                element.inert = backgroundInertStates.get(element) ?? false;
            });
            backgroundInertStates.clear();
            lastFocusedElement?.focus();
            lastFocusedElement = null;
            activeHistoryRecord = null;
        }, 220);
    }

    function printHistory(historyRecord) {
        if (historyRecord) {
            showToast(createPrintMessage(historyRecord));
        }
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
        const detailButton = event.target.closest('[data-mobile-history-detail-open]');
        const printButton = event.target.closest('[data-mobile-history-print]');

        if (detailButton) {
            openDetail(detailButton.dataset.historyId, detailButton);
        }

        if (printButton) {
            printHistory(findHistoryById(historyRecords, printButton.dataset.historyId));
        }
    });

    sheetCloseButtons.forEach((button) => button.addEventListener('click', closeDetail));
    sheetPrintButton?.addEventListener('click', () => printHistory(activeHistoryRecord));
    toastCloseButton?.addEventListener('click', closeToast);

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
        document.addEventListener('DOMContentLoaded', initializeMobileHistory, { once: true });
    } else {
        initializeMobileHistory();
    }
}
