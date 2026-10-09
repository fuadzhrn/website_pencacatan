export const calculateMobileInspectionProgress = (items) => {
    const total = items.length;
    const conform = items.filter(({ result }) => result === 'conform').length;
    const nonconform = items.filter(({ result }) => result === 'nonconform').length;
    const answered = conform + nonconform;

    return {
        total,
        answered,
        conform,
        nonconform,
        remaining: Math.max(total - answered, 0),
        percentage: total === 0 ? 0 : Math.round((answered / total) * 100),
    };
};

export const resolveMobileInspectionStatus = (summary) => {
    if (summary.nonconform > 0) {
        return { key: 'finding', label: 'Terdapat Temuan' };
    }

    if (summary.remaining > 0) {
        return { key: 'pending', label: 'Belum Lengkap' };
    }

    return { key: 'complete', label: 'Siap Disimpan' };
};

export const resolveMobileCategoryStatus = (items) => {
    if (items.some(({ result }) => result === 'nonconform')) {
        return { key: 'finding', label: 'Ada Temuan' };
    }

    if (items.length > 0 && items.every(({ result }) => result === 'conform')) {
        return { key: 'complete', label: 'Selesai' };
    }

    return { key: 'pending', label: 'Belum Lengkap' };
};

export const validateMobileInspection = (items) => {
    const incompleteItem = items.find(({ result }) => result !== 'conform' && result !== 'nonconform');

    if (incompleteItem) {
        return { valid: false, code: 'incomplete', itemId: incompleteItem.id };
    }

    const itemWithoutFinding = items.find(({ result, findingDescription }) => (
        result === 'nonconform' && !findingDescription?.trim()
    ));

    if (itemWithoutFinding) {
        return { valid: false, code: 'missing-finding', itemId: itemWithoutFinding.id };
    }

    return { valid: true, code: 'valid', itemId: null };
};

export const markAllMobileItemsConform = (items) => items.map((item) => ({
    ...item,
    result: 'conform',
}));

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-mobile-inspection]');

        if (!root) {
            return;
        }

        const machineCards = Array.from(root.querySelectorAll('[data-mobile-machine-card]'));
        const machineButtons = Array.from(root.querySelectorAll('[data-mobile-machine-select]'));
        const detailFields = Array.from(root.querySelectorAll('[data-mobile-machine-detail]'));
        const checklistItems = Array.from(root.querySelectorAll('[data-mobile-checklist-item]'));
        const categories = Array.from(root.querySelectorAll('[data-mobile-category]'));
        const form = root.querySelector('[data-mobile-inspection-form]');
        const generalNote = root.querySelector('[data-mobile-general-note]');
        const progressPercentage = root.querySelector('[data-mobile-progress-percentage]');
        const progressCaption = root.querySelector('[data-mobile-progress-caption]');
        const progressTrack = root.querySelector('[data-mobile-progress-track]');
        const progressBar = root.querySelector('[data-mobile-progress-bar]');
        const progressStatus = root.querySelector('[data-mobile-progress-status]');
        const conformCount = root.querySelector('[data-mobile-conform-count]');
        const nonconformCount = root.querySelector('[data-mobile-nonconform-count]');
        const toast = root.querySelector('[data-mobile-inspection-toast]');
        const toastMessage = root.querySelector('[data-mobile-inspection-toast-message]');
        const itemIds = checklistItems.map((item) => item.dataset.mobileChecklistItem);
        let activeMachine = root.dataset.initialMachine ?? machineButtons[0]?.dataset.mobileMachineSelect;
        let toastTimer;

        const createItemState = (id) => ({
            id,
            result: null,
            note: '',
            findingDescription: '',
            findingFollowUp: '',
            photoName: '',
            photoPreview: '',
        });

        const createMachineState = () => ({
            generalNote: '',
            items: Object.fromEntries(itemIds.map((id) => [id, createItemState(id)])),
        });

        const machineStates = Object.fromEntries(
            machineButtons.map((button) => [button.dataset.mobileMachineSelect, createMachineState()]),
        );

        const currentState = () => machineStates[activeMachine];
        const currentItems = () => itemIds.map((id) => currentState().items[id]);

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
            }, 4000);
        };

        const updateProgress = () => {
            const summary = calculateMobileInspectionProgress(currentItems());
            const status = resolveMobileInspectionStatus(summary);

            if (progressPercentage) {
                progressPercentage.textContent = `${summary.percentage}%`;
            }

            if (progressCaption) {
                progressCaption.textContent = `${summary.answered} dari ${summary.total} item terisi`;
            }

            if (progressTrack) {
                progressTrack.setAttribute('aria-valuenow', String(summary.answered));
            }

            if (progressBar) {
                progressBar.style.width = `${summary.percentage}%`;
            }

            if (progressStatus) {
                progressStatus.textContent = status.label;
                progressStatus.className = `mobile-inspection-badge mobile-inspection-badge--${status.key}`;
            }

            if (conformCount) {
                conformCount.textContent = String(summary.conform);
            }

            if (nonconformCount) {
                nonconformCount.textContent = String(summary.nonconform);
            }
        };

        const updateCategoryStatuses = () => {
            categories.forEach((category) => {
                const categoryItems = Array.from(category.querySelectorAll('[data-mobile-checklist-item]'))
                    .map((item) => currentState().items[item.dataset.mobileChecklistItem]);
                const status = resolveMobileCategoryStatus(categoryItems);
                const badge = category.querySelector('[data-mobile-category-badge]');

                if (badge) {
                    badge.textContent = status.label;
                    badge.className = `mobile-category-badge mobile-category-badge--${status.key}`;
                }
            });
        };

        const renderChecklist = () => {
            const state = currentState();

            checklistItems.forEach((item) => {
                const itemState = state.items[item.dataset.mobileChecklistItem];
                const note = item.querySelector('[data-mobile-item-note]');
                const description = item.querySelector('[data-mobile-finding-description]');
                const followUp = item.querySelector('[data-mobile-finding-follow-up]');
                const findingForm = item.querySelector('[data-mobile-finding-form]');
                const photoInput = item.querySelector('[data-mobile-finding-photo]');
                const photoPreview = item.querySelector('[data-mobile-photo-preview]');
                const photoImage = photoPreview?.querySelector('img');
                const photoName = item.querySelector('[data-mobile-photo-name]');

                item.classList.toggle('is-conform', itemState.result === 'conform');
                item.classList.toggle('is-nonconform', itemState.result === 'nonconform');

                item.querySelectorAll('[data-mobile-result]').forEach((button) => {
                    const isActive = button.dataset.mobileResult === itemState.result;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', String(isActive));
                });

                if (note) {
                    note.value = itemState.note;
                }

                if (description) {
                    description.value = itemState.findingDescription;
                    description.required = itemState.result === 'nonconform';
                    description.setAttribute('aria-required', String(itemState.result === 'nonconform'));
                }

                if (followUp) {
                    followUp.value = itemState.findingFollowUp;
                }

                if (findingForm) {
                    findingForm.hidden = itemState.result !== 'nonconform';
                }

                if (photoInput) {
                    photoInput.value = '';
                }

                if (photoPreview && photoImage && photoName) {
                    photoPreview.hidden = !itemState.photoPreview;
                    photoImage.src = itemState.photoPreview;
                    photoName.textContent = itemState.photoName;
                }
            });

            if (generalNote) {
                generalNote.value = state.generalNote;
            }

            updateProgress();
            updateCategoryStatuses();
        };

        const openItemCategory = (item) => {
            const category = item?.closest('[data-mobile-category]');
            const toggle = category?.querySelector('[data-mobile-category-toggle]');
            const panel = category?.querySelector('[data-mobile-category-panel]');

            if (toggle && panel) {
                toggle.setAttribute('aria-expanded', 'true');
                panel.hidden = false;
            }
        };

        const focusInvalidItem = (itemId, targetSelector) => {
            const item = checklistItems.find((candidate) => candidate.dataset.mobileChecklistItem === itemId);

            openItemCategory(item);
            item?.querySelector(targetSelector)?.focus();
            item?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        };

        machineButtons.forEach((button) => {
            button.addEventListener('click', () => {
                activeMachine = button.dataset.mobileMachineSelect;

                machineCards.forEach((card) => {
                    card.classList.toggle('is-selected', card.dataset.mobileMachineCard === activeMachine);
                });

                machineButtons.forEach((candidate) => {
                    candidate.setAttribute('aria-pressed', String(candidate === button));
                });

                detailFields.forEach((field) => {
                    field.textContent = button.dataset[field.dataset.mobileMachineDetail] ?? '-';
                });

                renderChecklist();
                showToast(`${button.dataset.name} dipilih untuk pemeriksaan.`);
            });
        });

        root.querySelectorAll('[data-mobile-category-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const panel = document.getElementById(button.getAttribute('aria-controls'));
                const willOpen = button.getAttribute('aria-expanded') !== 'true';

                button.setAttribute('aria-expanded', String(willOpen));

                if (panel) {
                    panel.hidden = !willOpen;
                }
            });
        });

        checklistItems.forEach((item) => {
            const itemId = item.dataset.mobileChecklistItem;

            item.querySelectorAll('[data-mobile-result]').forEach((button) => {
                button.addEventListener('click', () => {
                    currentState().items[itemId].result = button.dataset.mobileResult;
                    renderChecklist();

                    if (button.dataset.mobileResult === 'nonconform') {
                        item.querySelector('[data-mobile-finding-description]')?.focus();
                    }
                });
            });

            item.querySelector('[data-mobile-item-note]')?.addEventListener('input', (event) => {
                currentState().items[itemId].note = event.target.value;
            });

            item.querySelector('[data-mobile-finding-description]')?.addEventListener('input', (event) => {
                currentState().items[itemId].findingDescription = event.target.value;
            });

            item.querySelector('[data-mobile-finding-follow-up]')?.addEventListener('input', (event) => {
                currentState().items[itemId].findingFollowUp = event.target.value;
            });

            item.querySelector('[data-mobile-finding-photo]')?.addEventListener('change', (event) => {
                const file = event.target.files?.[0];

                if (!file) {
                    return;
                }

                if (!file.type.startsWith('image/')) {
                    event.target.value = '';
                    showToast('Pilih file gambar untuk preview temuan.', 'error');
                    return;
                }

                const selectedMachine = activeMachine;
                const reader = new FileReader();

                reader.addEventListener('load', () => {
                    const itemState = machineStates[selectedMachine]?.items[itemId];

                    if (!itemState) {
                        return;
                    }

                    itemState.photoName = file.name;
                    itemState.photoPreview = String(reader.result ?? '');

                    if (selectedMachine === activeMachine) {
                        renderChecklist();
                        showToast('Preview foto temuan berhasil ditambahkan.');
                    }
                });

                reader.readAsDataURL(file);
            });

            item.querySelector('[data-mobile-apply-finding]')?.addEventListener('click', () => {
                const description = currentState().items[itemId].findingDescription;

                if (!description.trim()) {
                    showToast('Lengkapi keterangan temuan terlebih dahulu.', 'error');
                    item.querySelector('[data-mobile-finding-description]')?.focus();
                    return;
                }

                showToast('Temuan diterapkan pada item pemeriksaan.');
                updateProgress();
            });
        });

        generalNote?.addEventListener('input', (event) => {
            currentState().generalNote = event.target.value;
        });

        root.querySelector('[data-mobile-mark-all]')?.addEventListener('click', () => {
            markAllMobileItemsConform(currentItems()).forEach((item) => {
                currentState().items[item.id] = item;
            });

            renderChecklist();
            showToast('Semua checklist ditandai sesuai.');
        });

        root.querySelector('[data-mobile-save-draft]')?.addEventListener('click', () => {
            showToast('Draft pemeriksaan tersimpan sementara.');
        });

        form?.addEventListener('submit', (event) => {
            event.preventDefault();
            const validation = validateMobileInspection(currentItems());

            if (validation.code === 'incomplete') {
                showToast('Masih ada checklist yang belum diisi.', 'error');
                focusInvalidItem(validation.itemId, '[data-mobile-result]');
                return;
            }

            if (validation.code === 'missing-finding') {
                showToast('Lengkapi keterangan temuan terlebih dahulu.', 'error');
                focusInvalidItem(validation.itemId, '[data-mobile-finding-description]');
                return;
            }

            showToast('Hasil pemeriksaan berhasil disimpan.');
        });

        root.querySelector('[data-mobile-inspection-toast-close]')?.addEventListener('click', () => {
            window.clearTimeout(toastTimer);

            if (toast) {
                toast.hidden = true;
            }
        });

        renderChecklist();
    });
}
