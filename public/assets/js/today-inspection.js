export const calculateInspectionSummary = (results) => {
    const total = results.length;
    const conform = results.filter((result) => result === 'conform').length;
    const nonconform = results.filter((result) => result === 'nonconform').length;
    const answered = conform + nonconform;
    const remaining = total - answered;
    const percentage = total === 0 ? 0 : Math.round((answered / total) * 100);

    return {
        total,
        answered,
        conform,
        nonconform,
        remaining,
        percentage,
    };
};

export const resolveFinalStatus = (summary) => {
    if (summary.remaining > 0) {
        return {
            key: 'incomplete',
            label: 'Belum Lengkap',
            note: 'Lengkapi seluruh item sebelum menyimpan pemeriksaan.',
        };
    }

    if (summary.nonconform > 0) {
        return {
            key: 'findings',
            label: 'Terdapat Temuan',
            note: 'Pemeriksaan lengkap dengan item yang memerlukan tindak lanjut.',
        };
    }

    return {
        key: 'ready',
        label: 'Siap Disimpan',
        note: 'Seluruh checklist sudah diisi dan tidak terdapat temuan.',
    };
};

export const validateInspection = (results, notes) => {
    const summary = calculateInspectionSummary(results);

    if (summary.remaining > 0) {
        return {
            code: 'incomplete',
            itemIndex: results.findIndex((result) => result !== 'conform' && result !== 'nonconform'),
            summary,
        };
    }

    const missingNoteIndex = results.findIndex((result, index) => {
        return result === 'nonconform' && (notes[index] ?? '').trim() === '';
    });

    if (missingNoteIndex >= 0) {
        return {
            code: 'missing-note',
            itemIndex: missingNoteIndex,
            summary,
        };
    }

    return {
        code: 'valid',
        itemIndex: -1,
        summary,
    };
};

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        const machineButtons = document.querySelectorAll('[data-machine-select]');
        const machineCards = document.querySelectorAll('[data-machine-card]');
        const detailFields = document.querySelectorAll('[data-machine-detail]');
        const checklistRows = Array.from(document.querySelectorAll('[data-checklist-item]'));
        const inspectionForm = document.querySelector('[data-inspection-form]');
        const markAllButton = document.querySelector('[data-mark-all-conform]');
        const saveDraftButton = document.querySelector('[data-save-draft]');
        const cancelButton = document.querySelector('[data-cancel-inspection]');
        const generalNote = document.querySelector('[data-general-note]');
        const progressPercentage = document.querySelector('[data-progress-percentage]');
        const progressCaption = document.querySelector('[data-progress-caption]');
        const progressTrack = document.querySelector('[data-progress-track]');
        const progressBar = document.querySelector('[data-progress-bar]');
        const conformCount = document.querySelector('[data-conform-count]');
        const nonconformCount = document.querySelector('[data-nonconform-count]');
        const remainingCount = document.querySelector('[data-remaining-count]');
        const finalStatus = document.querySelector('[data-final-status]');
        const finalStatusNote = document.querySelector('[data-final-status-note]');
        const summaryMachine = document.querySelector('[data-summary-machine]');
        const toast = document.querySelector('[data-inspection-toast]');
        const toastMessage = document.querySelector('[data-toast-message]');
        const successDialog = document.querySelector('#inspection-success-dialog');
        const successDescription = document.querySelector('[data-success-description]');
        const itemIds = checklistRows.map((row) => row.dataset.checklistItem);
        let activeMachine = 'baggage';
        let toastTimer;

        const createEmptyState = () => ({
            generalNote: '',
            items: Object.fromEntries(itemIds.map((itemId) => [itemId, { result: null, note: '' }])),
        });

        const initialStates = {
            baggage: createEmptyState(),
            cabin: createEmptyState(),
            cargo: createEmptyState(),
        };

        initialStates.cabin.items['lead-curtain'].result = 'conform';
        initialStates.cabin.items['outer-unit'].result = 'conform';
        initialStates.cabin.items['emergency-stop'].result = 'nonconform';
        initialStates.cabin.items['emergency-stop'].note = 'Respons tombol sedikit terlambat dan perlu ditinjau teknisi.';

        const cloneState = (state) => JSON.parse(JSON.stringify(state));
        const machineStates = Object.fromEntries(
            Object.entries(initialStates).map(([machineKey, state]) => [machineKey, cloneState(state)]),
        );

        const getCurrentState = () => machineStates[activeMachine];

        const getCurrentResults = () => {
            const state = getCurrentState();
            return itemIds.map((itemId) => state.items[itemId].result);
        };

        const showToast = (message, type = 'success') => {
            if (!toast || !toastMessage) {
                return;
            }

            window.clearTimeout(toastTimer);
            toastMessage.textContent = message;
            toast.classList.toggle('is-error', type === 'error');
            toast.hidden = false;
            toastTimer = window.setTimeout(() => {
                toast.hidden = true;
            }, 4200);
        };

        const updateSummary = () => {
            const summary = calculateInspectionSummary(getCurrentResults());
            const status = resolveFinalStatus(summary);

            if (progressPercentage) {
                progressPercentage.textContent = `${summary.percentage}%`;
            }

            if (progressCaption) {
                progressCaption.textContent = `${summary.answered} dari ${summary.total} item telah diperiksa`;
            }

            if (progressTrack) {
                progressTrack.setAttribute('aria-valuenow', String(summary.answered));
            }

            if (progressBar) {
                progressBar.style.width = `${summary.percentage}%`;
            }

            if (conformCount) {
                conformCount.textContent = String(summary.conform);
            }

            if (nonconformCount) {
                nonconformCount.textContent = String(summary.nonconform);
            }

            if (remainingCount) {
                remainingCount.textContent = String(summary.remaining);
            }

            if (finalStatus) {
                finalStatus.textContent = status.label;
                finalStatus.className = `inspection-final-status__badge inspection-final-status__badge--${status.key}`;
            }

            if (finalStatusNote) {
                finalStatusNote.textContent = status.note;
            }

            return summary;
        };

        const renderChecklist = () => {
            const state = getCurrentState();

            checklistRows.forEach((row) => {
                const itemId = row.dataset.checklistItem;
                const itemState = state.items[itemId];
                const note = row.querySelector('[data-item-note]');
                const noteHint = row.querySelector('[data-note-hint]');

                row.classList.toggle('is-conform', itemState.result === 'conform');
                row.classList.toggle('is-nonconform', itemState.result === 'nonconform');

                row.querySelectorAll('[data-result-option]').forEach((button) => {
                    const isActive = button.dataset.resultOption === itemState.result;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', String(isActive));
                });

                if (note) {
                    note.value = itemState.note;
                    note.setAttribute('aria-required', String(itemState.result === 'nonconform'));
                }

                if (noteHint) {
                    noteHint.textContent = itemState.result === 'nonconform' ? 'Wajib diisi' : 'Opsional';
                }
            });

            if (generalNote) {
                generalNote.value = state.generalNote;
            }

            updateSummary();
        };

        const selectMachine = (button) => {
            activeMachine = button.dataset.machineSelect;

            machineCards.forEach((card) => {
                card.classList.toggle('is-selected', card.dataset.machineCard === activeMachine);
            });

            machineButtons.forEach((candidate) => {
                const isSelected = candidate === button;
                candidate.classList.toggle('is-primary', isSelected);
                candidate.setAttribute('aria-pressed', String(isSelected));
            });

            detailFields.forEach((field) => {
                const fieldName = field.dataset.machineDetail;

                if (fieldName) {
                    field.textContent = button.dataset[fieldName] ?? '-';
                }
            });

            if (summaryMachine) {
                summaryMachine.textContent = button.dataset.name ?? '-';
            }

            renderChecklist();
            showToast(`Checklist ${button.dataset.name} siap diisi.`);
        };

        checklistRows.forEach((row) => {
            const itemId = row.dataset.checklistItem;

            row.querySelectorAll('[data-result-option]').forEach((button) => {
                button.addEventListener('click', () => {
                    getCurrentState().items[itemId].result = button.dataset.resultOption;
                    renderChecklist();
                });
            });

            row.querySelector('[data-item-note]')?.addEventListener('input', (event) => {
                getCurrentState().items[itemId].note = event.target.value;
            });
        });

        machineButtons.forEach((button) => {
            button.addEventListener('click', () => selectMachine(button));
        });

        generalNote?.addEventListener('input', (event) => {
            getCurrentState().generalNote = event.target.value;
        });

        markAllButton?.addEventListener('click', () => {
            itemIds.forEach((itemId) => {
                getCurrentState().items[itemId].result = 'conform';
            });

            renderChecklist();
            showToast('Semua item berhasil ditandai sesuai.');
        });

        saveDraftButton?.addEventListener('click', () => {
            showToast('Draft pemeriksaan tersimpan sementara pada simulasi halaman.');
        });

        cancelButton?.addEventListener('click', () => {
            machineStates[activeMachine] = cloneState(initialStates[activeMachine]);
            renderChecklist();
            showToast('Perubahan pada mesin aktif telah dibatalkan.');
        });

        inspectionForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            const state = getCurrentState();
            const results = getCurrentResults();
            const notes = itemIds.map((itemId) => state.items[itemId].note);
            const validation = validateInspection(results, notes);
            const summary = validation.summary;

            updateSummary();

            if (validation.code === 'incomplete') {
                showToast(`Masih ada ${summary.remaining} checklist yang belum dipilih.`, 'error');
                checklistRows[validation.itemIndex]?.querySelector('[data-result-option]')
                    ?.focus();
                return;
            }

            if (validation.code === 'missing-note') {
                showToast('Tambahkan catatan pada setiap item yang Tidak Sesuai.', 'error');
                checklistRows[validation.itemIndex]?.querySelector('[data-item-note]')?.focus();
                return;
            }

            if (successDescription) {
                successDescription.textContent = summary.nonconform > 0
                    ? `Pemeriksaan lengkap dengan ${summary.nonconform} temuan untuk ditindaklanjuti.`
                    : 'Seluruh checklist telah tercatat sebagai pemeriksaan normal.';
            }

            if (successDialog && typeof successDialog.showModal === 'function') {
                successDialog.showModal();
            }
        });

        document.querySelector('[data-toast-close]')?.addEventListener('click', () => {
            if (toast) {
                toast.hidden = true;
            }
        });

        document.querySelector('[data-success-close]')?.addEventListener('click', () => {
            if (successDialog?.open) {
                successDialog.close();
            }
        });

        successDialog?.addEventListener('click', (event) => {
            if (event.target === successDialog) {
                successDialog.close();
            }
        });

        renderChecklist();
    });
}
