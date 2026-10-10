export function filterFindings(findings, filters) {
    const query = (filters.query ?? '').trim().toLocaleLowerCase('id-ID');

    return findings.filter((finding) => {
        const matchesMachine = !filters.machine || finding.machineKey === filters.machine;
        const matchesStatus = !filters.status || finding.status === filters.status;
        const matchesDate = !filters.date || finding.date === filters.date;
        const searchableText = [
            finding.machine,
            finding.item,
            finding.description,
            finding.followUp,
        ].join(' ').toLocaleLowerCase('id-ID');

        return matchesMachine
            && matchesStatus
            && matchesDate
            && (!query || searchableText.includes(query));
    });
}

export function summarizeFindings(findings) {
    return {
        total: findings.length,
        open: findings.filter((finding) => finding.status === 'open').length,
        review: findings.filter((finding) => finding.status === 'review').length,
        resolved: findings.filter((finding) => finding.status === 'resolved').length,
    };
}

export function updateFindingStatus(findings, findingId, status) {
    return findings.map((finding) => finding.id === findingId ? { ...finding, status } : finding);
}

export function upsertFinding(findings, selectedFinding) {
    const findingIndex = findings.findIndex((finding) => finding.id === selectedFinding.id);

    if (findingIndex < 0) {
        return [...findings, selectedFinding];
    }

    return findings.map((finding, index) => index === findingIndex ? selectedFinding : finding);
}

export function createFindingSaveMessage(finding, isEditing) {
    const action = isEditing ? 'diperbarui' : 'ditambahkan';

    return `${finding.item} berhasil ${action}.`;
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

export function shouldCloseMobileSheet(viewportWidth) {
    return viewportWidth > 768;
}

export function isSupportedFindingPhoto(file) {
    return ['image/jpeg', 'image/png', 'image/webp'].includes(file?.type ?? '');
}

function initializeMobileFindings() {
    const root = document.querySelector('[data-mobile-findings]');

    if (!root) {
        return;
    }

    const statusLabels = {
        open: 'Open',
        review: 'Review',
        resolved: 'Resolved',
    };
    const machineDetails = {
        cabin: { machine: 'X-Ray Cabin', location: 'Terminal 2' },
        cargo: { machine: 'X-Ray Cargo', location: 'Area Cargo' },
        baggage: { machine: 'X-Ray Baggage HI-Scan 100100T', location: 'Terminal 1' },
    };
    const filterForm = root.querySelector('[data-mobile-findings-filter]');
    const resetFilterButton = root.querySelector('[data-mobile-findings-reset]');
    const findingsList = root.querySelector('[data-mobile-findings-list]');
    const emptyState = root.querySelector('[data-mobile-findings-empty]');
    const resultCount = root.querySelector('[data-mobile-findings-result-count]');
    const findingForm = root.querySelector('[data-mobile-finding-form]');
    const findingFormTitle = root.querySelector('[data-mobile-finding-form-title]');
    const statusForm = root.querySelector('[data-mobile-finding-status-form]');
    const statusItem = root.querySelector('[data-mobile-finding-status-item]');
    const photoInput = root.querySelector('[data-mobile-finding-photo-input]');
    const photoPreview = root.querySelector('[data-mobile-finding-photo-preview]');
    const photoPlaceholder = root.querySelector('[data-mobile-finding-photo-placeholder]');
    const photoError = root.querySelector('[data-mobile-finding-photo-error]');
    const toast = root.querySelector('[data-mobile-findings-toast]');
    const toastMessage = root.querySelector('[data-mobile-findings-toast-message]');
    const mobileShell = root.closest('[data-mobile-shell]');
    let findings = [...root.querySelectorAll('[data-mobile-finding-card]')].map(readFindingCard);
    let activeSheet = null;
    let lastFocusedElement = null;
    let backgroundInertStates = new Map();
    let editingFindingId = null;
    let statusFindingId = null;
    let detailFindingId = null;
    let pendingPhotoUrl = null;
    let createdFindingSequence = 1;
    let toastTimer = null;

    function readFindingCard(card) {
        return {
            id: card.dataset.id,
            machine: card.dataset.machine,
            machineKey: card.dataset.machineKey,
            location: card.dataset.location,
            date: card.dataset.date,
            dateLabel: card.dataset.dateLabel,
            item: card.dataset.item,
            criteria: card.dataset.criteria,
            description: card.dataset.description,
            followUp: card.dataset.followUp,
            status: card.dataset.status,
            statusLabel: card.dataset.statusLabel,
            officer: card.dataset.officer,
            hasPhoto: card.dataset.hasPhoto === 'true',
            photoLabel: card.dataset.photoLabel,
            photoUrl: card.dataset.photoUrl ?? '',
        };
    }

    function refreshIcons() {
        window.lucide?.createIcons();
    }

    function readFilters() {
        const formData = new FormData(filterForm);

        return {
            machine: formData.get('machine')?.toString() ?? '',
            status: formData.get('status')?.toString() ?? '',
            date: formData.get('date')?.toString() ?? '',
            query: formData.get('query')?.toString() ?? '',
        };
    }

    function updateSummary(visibleFindings) {
        const summary = summarizeFindings(visibleFindings);

        Object.entries(summary).forEach(([key, value]) => {
            const output = root.querySelector(`[data-mobile-findings-summary="${key}"]`);

            if (output) {
                output.textContent = value.toString();
            }
        });
    }

    function applyFilters() {
        const visibleFindings = filterFindings(findings, readFilters());
        const visibleIds = new Set(visibleFindings.map((finding) => finding.id));

        root.querySelectorAll('[data-mobile-finding-card]').forEach((card) => {
            card.hidden = !visibleIds.has(card.dataset.id);
        });

        if (findingsList) {
            findingsList.hidden = visibleFindings.length === 0;
        }

        if (emptyState) {
            emptyState.hidden = visibleFindings.length > 0;
        }

        if (resultCount) {
            resultCount.textContent = `${visibleFindings.length} temuan`;
        }

        updateSummary(visibleFindings);
    }

    function closeToast() {
        window.clearTimeout(toastTimer);

        if (toast) {
            toast.hidden = true;
        }
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

    function getBackgroundElements(sheet) {
        return [
            ...[...root.children].filter((element) => element !== sheet),
            ...[...(mobileShell?.children ?? [])].filter((element) => element !== root),
        ];
    }

    function openSheet(sheetName, trigger) {
        const sheet = root.querySelector(`[data-mobile-finding-sheet="${sheetName}"]`);

        if (!sheet) {
            return;
        }

        lastFocusedElement = trigger;
        activeSheet = sheet;
        backgroundInertStates = new Map();
        getBackgroundElements(sheet).forEach((element) => {
            backgroundInertStates.set(element, element.inert);
            element.inert = true;
        });

        sheet.hidden = false;
        sheet.setAttribute('aria-hidden', 'false');
        document.body.classList.add('mobile-finding-sheet-open');
        window.requestAnimationFrame(() => {
            sheet.classList.add('is-open');
            sheet.querySelector('[data-mobile-finding-sheet-panel]')?.focus();
        });
    }

    function discardPendingPhoto() {
        if (pendingPhotoUrl) {
            URL.revokeObjectURL(pendingPhotoUrl);
            pendingPhotoUrl = null;
        }
    }

    function closeSheet({ immediate = false, restoreFocus = true } = {}) {
        if (!activeSheet) {
            return;
        }

        const closingSheet = activeSheet;
        const focusTarget = lastFocusedElement;
        const closingInertStates = backgroundInertStates;
        const isFindingForm = closingSheet.dataset.mobileFindingSheet === 'form';
        closingSheet.classList.remove('is-open');
        closingSheet.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('mobile-finding-sheet-open');
        activeSheet = null;
        lastFocusedElement = null;
        backgroundInertStates = new Map();

        if (isFindingForm) {
            discardPendingPhoto();
        }

        const finishClosingSheet = () => {
            closingSheet.hidden = true;
            closingInertStates.forEach((wasInert, element) => {
                element.inert = wasInert;
            });
            closingInertStates.clear();

            if (restoreFocus) {
                const isFocusTargetVisible = focusTarget
                    && !focusTarget.closest('[hidden]')
                    && focusTarget.getClientRects().length > 0;
                const fallbackFocusTarget = root.querySelector('[data-mobile-finding-create]');
                (isFocusTargetVisible ? focusTarget : fallbackFocusTarget)?.focus();
            }
        };

        if (immediate) {
            finishClosingSheet();
        } else {
            window.setTimeout(finishClosingSheet, 220);
        }
    }

    function switchSheet(sheetName, trigger) {
        closeSheet({ immediate: true, restoreFocus: false });
        openSheet(sheetName, trigger);
    }

    function findFinding(findingId) {
        return findings.find((finding) => finding.id === findingId) ?? null;
    }

    function updateStatusBadge(badge, status) {
        if (!badge) {
            return;
        }

        badge.textContent = statusLabels[status];
        badge.className = `mobile-finding-badge mobile-finding-badge--${status}`;
    }

    function openDetail(findingId, trigger) {
        const finding = findFinding(findingId);

        if (!finding) {
            return;
        }

        detailFindingId = finding.id;
        root.querySelectorAll('[data-mobile-finding-detail-field]').forEach((output) => {
            const fieldName = output.dataset.mobileFindingDetailField;
            output.textContent = finding[fieldName] || '-';
        });
        updateStatusBadge(
            root.querySelector('[data-mobile-finding-sheet="detail"] [data-mobile-finding-detail-field="statusLabel"]'),
            finding.status,
        );

        const photoContainer = root.querySelector('[data-mobile-finding-detail-photo]');
        const photoEmpty = root.querySelector('[data-mobile-finding-detail-photo-empty]');
        const photoImage = root.querySelector('[data-mobile-finding-detail-photo-image]');
        const detailPhotoPlaceholder = root.querySelector('[data-mobile-finding-detail-photo-placeholder]');

        if (photoContainer) {
            photoContainer.hidden = !finding.hasPhoto;
        }

        if (photoEmpty) {
            photoEmpty.hidden = finding.hasPhoto;
        }

        if (photoImage) {
            photoImage.hidden = !finding.photoUrl;
            photoImage.src = finding.photoUrl || '';
        }

        if (detailPhotoPlaceholder) {
            detailPhotoPlaceholder.hidden = Boolean(finding.photoUrl);
        }

        openSheet('detail', trigger);
    }

    function setFormValue(fieldName, value) {
        const field = findingForm?.elements.namedItem(fieldName);

        if (field) {
            field.value = value ?? '';
        }
    }

    function resetPhotoPreview() {
        if (photoInput) {
            photoInput.value = '';
        }

        if (photoPreview) {
            photoPreview.src = '';
            photoPreview.hidden = true;
        }

        if (photoPlaceholder) {
            photoPlaceholder.hidden = false;
            const label = photoPlaceholder.querySelector('strong');

            if (label) {
                label.textContent = 'Pilih foto';
            }
        }

        if (photoError) {
            photoError.hidden = true;
        }
    }

    function showPhotoError(message) {
        if (!photoError) {
            return;
        }

        photoError.textContent = message;
        photoError.hidden = false;
    }

    function showExistingPhoto(finding) {
        resetPhotoPreview();

        if (finding?.photoUrl && photoPreview) {
            photoPreview.src = finding.photoUrl;
            photoPreview.hidden = false;

            if (photoPlaceholder) {
                photoPlaceholder.hidden = true;
            }
        } else if (finding?.hasPhoto && photoPlaceholder) {
            const label = photoPlaceholder.querySelector('strong');

            if (label) {
                label.textContent = 'Foto tersimpan · pilih untuk mengganti';
            }
        }
    }

    function openCreateForm(trigger) {
        editingFindingId = null;
        discardPendingPhoto();
        findingForm?.reset();
        resetPhotoPreview();
        setFormValue('status', 'open');

        if (findingFormTitle) {
            findingFormTitle.textContent = 'Tambah Temuan';
        }

        openSheet('form', trigger);
    }

    function openEditForm(findingId, trigger) {
        const finding = findFinding(findingId);

        if (!finding) {
            return;
        }

        editingFindingId = finding.id;
        discardPendingPhoto();
        setFormValue('machineKey', finding.machineKey);
        setFormValue('item', finding.item);
        setFormValue('description', finding.description);
        setFormValue('followUp', finding.followUp);
        setFormValue('status', finding.status);
        showExistingPhoto(finding);

        if (findingFormTitle) {
            findingFormTitle.textContent = 'Edit Temuan';
        }

        if (activeSheet) {
            switchSheet('form', trigger);
        } else {
            openSheet('form', trigger);
        }
    }

    function openStatusForm(findingId, trigger) {
        const finding = findFinding(findingId);

        if (!finding) {
            return;
        }

        statusFindingId = finding.id;
        const statusSelect = statusForm?.elements.namedItem('status');

        if (statusSelect) {
            statusSelect.value = finding.status;
        }

        if (statusItem) {
            statusItem.textContent = finding.item;
        }

        openSheet('status', trigger);
    }

    function createFindingCard() {
        const card = document.createElement('article');
        card.className = 'mobile-finding-card';
        card.setAttribute('data-mobile-finding-card', '');
        card.innerHTML = `
            <header class="mobile-finding-card__header">
                <span class="mobile-finding-card__machine-icon" aria-hidden="true"><i data-lucide="scan-line"></i></span>
                <div><p data-mobile-finding-card-location></p><h3 data-mobile-finding-card-machine></h3></div>
                <span class="mobile-finding-badge" data-mobile-finding-card-status></span>
            </header>
            <div class="mobile-finding-card__issue"><span>Item tidak sesuai</span><strong data-mobile-finding-card-item></strong></div>
            <dl class="mobile-finding-card__details">
                <div><dt>Keterangan</dt><dd data-mobile-finding-card-description></dd></div>
                <div><dt>Tindak lanjut</dt><dd data-mobile-finding-card-follow-up></dd></div>
            </dl>
            <div class="mobile-finding-card__meta">
                <span><i data-lucide="calendar-days" aria-hidden="true"></i><time data-mobile-finding-card-date></time></span>
                <div data-mobile-finding-card-photo hidden><img data-mobile-finding-card-photo-image alt=""><span aria-hidden="true"><i data-lucide="image"></i></span><small>Foto tersedia</small></div>
            </div>
            <footer class="mobile-finding-card__actions">
                <button type="button" data-mobile-finding-detail><i data-lucide="eye" aria-hidden="true"></i>Detail</button>
                <button type="button" data-mobile-finding-status><i data-lucide="refresh-cw" aria-hidden="true"></i>Ubah Status</button>
            </footer>`;

        return card;
    }

    function writeFindingToCard(finding, prepend = false) {
        let card = root.querySelector(`[data-mobile-finding-card][data-id="${finding.id}"]`);

        if (!card) {
            card = createFindingCard();

            if (prepend) {
                findingsList?.prepend(card);
            } else {
                findingsList?.append(card);
            }
        }

        Object.assign(card.dataset, {
            id: finding.id,
            machine: finding.machine,
            machineKey: finding.machineKey,
            location: finding.location,
            date: finding.date,
            dateLabel: finding.dateLabel,
            item: finding.item,
            criteria: finding.criteria,
            description: finding.description,
            followUp: finding.followUp,
            status: finding.status,
            statusLabel: finding.statusLabel,
            officer: finding.officer,
            hasPhoto: finding.hasPhoto.toString(),
            photoLabel: finding.photoLabel,
            photoUrl: finding.photoUrl,
        });

        card.querySelector('[data-mobile-finding-card-location]').textContent = finding.location;
        card.querySelector('[data-mobile-finding-card-machine]').textContent = finding.machine;
        card.querySelector('[data-mobile-finding-card-item]').textContent = finding.item;
        card.querySelector('[data-mobile-finding-card-description]').textContent = finding.description;
        card.querySelector('[data-mobile-finding-card-follow-up]').textContent = finding.followUp;
        const cardDate = card.querySelector('[data-mobile-finding-card-date]');
        cardDate.textContent = finding.dateLabel;
        cardDate.dateTime = finding.date;
        updateStatusBadge(card.querySelector('[data-mobile-finding-card-status]'), finding.status);

        const cardPhoto = card.querySelector('[data-mobile-finding-card-photo]');
        const cardPhotoImage = card.querySelector('[data-mobile-finding-card-photo-image]');
        cardPhoto.hidden = !finding.hasPhoto;

        if (cardPhotoImage) {
            cardPhotoImage.hidden = !finding.photoUrl;
            cardPhotoImage.src = finding.photoUrl || '';
            cardPhotoImage.alt = finding.photoUrl ? finding.photoLabel : '';
        }

        card.querySelectorAll('[data-mobile-finding-detail], [data-mobile-finding-status]').forEach((button) => {
            button.dataset.findingId = finding.id;
        });
    }

    function formatDateLabel(date) {
        return new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: 'UTC',
        }).format(new Date(`${date}T00:00:00Z`));
    }

    function readFindingForm() {
        const formData = new FormData(findingForm);
        const existingFinding = editingFindingId ? findFinding(editingFindingId) : null;
        const machineKey = formData.get('machineKey')?.toString() ?? 'cabin';
        const machine = machineDetails[machineKey] ?? machineDetails.cabin;
        const status = formData.get('status')?.toString() ?? 'open';
        const date = existingFinding?.date ?? '2026-10-10';
        const selectedPhoto = photoInput?.files?.[0] ?? null;
        const photoUrl = pendingPhotoUrl || existingFinding?.photoUrl || '';

        return {
            id: editingFindingId ?? `mobile-finding-created-${createdFindingSequence++}`,
            machine: machine.machine,
            machineKey,
            location: machine.location,
            date,
            dateLabel: existingFinding?.dateLabel ?? formatDateLabel(date),
            item: formData.get('item')?.toString().trim() ?? '',
            criteria: existingFinding?.criteria ?? 'Komponen memenuhi standar pemeriksaan dan berfungsi normal.',
            description: formData.get('description')?.toString().trim() ?? '',
            followUp: formData.get('followUp')?.toString().trim() ?? '',
            status,
            statusLabel: statusLabels[status],
            officer: existingFinding?.officer ?? 'Petugas Aktif',
            hasPhoto: Boolean(photoUrl) || existingFinding?.hasPhoto === true,
            photoLabel: selectedPhoto?.name || existingFinding?.photoLabel || '',
            photoUrl,
        };
    }

    filterForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        applyFilters();
    });

    resetFilterButton?.addEventListener('click', () => {
        filterForm.reset();
        applyFilters();
    });

    photoInput?.addEventListener('change', () => {
        const photo = photoInput.files?.[0];

        discardPendingPhoto();

        if (!photo) {
            showExistingPhoto(editingFindingId ? findFinding(editingFindingId) : null);
            return;
        }

        if (!isSupportedFindingPhoto(photo)) {
            photoInput.value = '';
            showExistingPhoto(editingFindingId ? findFinding(editingFindingId) : null);
            showPhotoError('Gunakan foto berformat JPG, PNG, atau WEBP.');
            return;
        }

        if (photoError) {
            photoError.hidden = true;
        }

        pendingPhotoUrl = URL.createObjectURL(photo);

        if (photoPreview) {
            photoPreview.src = pendingPhotoUrl;
            photoPreview.hidden = false;
        }

        if (photoPlaceholder) {
            photoPlaceholder.hidden = true;
        }
    });

    statusForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        const status = new FormData(statusForm).get('status')?.toString() ?? 'open';
        const finding = findFinding(statusFindingId);

        if (!finding) {
            return;
        }

        findings = updateFindingStatus(findings, finding.id, status).map((entry) => entry.id === finding.id
            ? { ...entry, statusLabel: statusLabels[status] }
            : entry);
        const updatedFinding = findFinding(finding.id);
        writeFindingToCard(updatedFinding);
        applyFilters();
        closeSheet();
        showToast(`Status ${finding.item} diubah menjadi ${statusLabels[status]}.`);
    });

    findingForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        const wasEditing = editingFindingId !== null;
        const existingFinding = wasEditing ? findFinding(editingFindingId) : null;
        const finding = readFindingForm();

        if (pendingPhotoUrl && existingFinding?.photoUrl && existingFinding.photoUrl !== pendingPhotoUrl) {
            URL.revokeObjectURL(existingFinding.photoUrl);
        }

        pendingPhotoUrl = null;
        findings = upsertFinding(findings, finding);
        writeFindingToCard(finding, !wasEditing);
        applyFilters();
        refreshIcons();
        closeSheet();
        showToast(createFindingSaveMessage(finding, wasEditing));
    });

    root.addEventListener('click', (event) => {
        const detailButton = event.target.closest('[data-mobile-finding-detail]');
        const statusButton = event.target.closest('[data-mobile-finding-status]');
        const createButton = event.target.closest('[data-mobile-finding-create]');
        const editButton = event.target.closest('[data-mobile-finding-edit]');
        const closeButton = event.target.closest('[data-mobile-finding-sheet-close]');
        const toastCloseButton = event.target.closest('[data-mobile-findings-toast-close]');

        if (detailButton) {
            openDetail(detailButton.dataset.findingId, detailButton);
        }

        if (statusButton) {
            openStatusForm(statusButton.dataset.findingId, statusButton);
        }

        if (createButton) {
            openCreateForm(createButton);
        }

        if (editButton && detailFindingId) {
            openEditForm(detailFindingId, editButton);
        }

        if (closeButton) {
            closeSheet();
        }

        if (toastCloseButton) {
            closeToast();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeSheet) {
            closeSheet();
        }

        if (event.key === 'Tab' && activeSheet && !shouldCloseMobileSheet(window.innerWidth)) {
            const panel = activeSheet.querySelector('[data-mobile-finding-sheet-panel]');
            const focusableElements = [...panel.querySelectorAll(
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

    window.addEventListener('resize', () => {
        if (activeSheet && shouldCloseMobileSheet(window.innerWidth)) {
            closeSheet({ immediate: true, restoreFocus: false });
        }
    });

    window.addEventListener('beforeunload', () => {
        discardPendingPhoto();
        findings.forEach((finding) => {
            if (finding.photoUrl.startsWith('blob:')) {
                URL.revokeObjectURL(finding.photoUrl);
            }
        });
    });

    applyFilters();
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeMobileFindings, { once: true });
    } else {
        initializeMobileFindings();
    }
}
