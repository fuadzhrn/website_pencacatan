const completedStatuses = new Set(['sesuai', 'tidak_sesuai', 'tidak_dijadwalkan']);

export function inspectionProgress(statuses) {
    const total = statuses.length;
    const completed = statuses.filter((status) => completedStatuses.has(status)).length;
    const conform = statuses.filter((status) => status === 'sesuai').length;
    const nonconform = statuses.filter((status) => status === 'tidak_sesuai').length;

    return {
        total,
        completed,
        conform,
        nonconform,
        remaining: Math.max(0, total - completed),
        percentage: total === 0 ? 0 : Math.round((completed / total) * 100),
    };
}

export function shouldShowFindingFields(status) {
    return status === 'tidak_sesuai';
}

function updateItemState(item, status) {
    const input = item.querySelector('[data-result-input]');
    if (!input || input.disabled) {
        return;
    }

    input.value = status;
    item.classList.toggle('is-conform', status === 'sesuai');
    item.classList.toggle('is-nonconform', status === 'tidak_sesuai');

    const findingFields = item.querySelector('[data-finding-fields]');
    const findingDescription = item.querySelector('[data-finding-description]');
    if (findingFields) {
        findingFields.hidden = !shouldShowFindingFields(status);
    }
    if (findingDescription && !findingDescription.disabled) {
        findingDescription.required = shouldShowFindingFields(status);
    }

    item.querySelectorAll('[data-inspection-result]').forEach((button) => {
        const isActive = button.dataset.inspectionResult === status;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', String(isActive));
    });
}

function updateProgress(form) {
    const statuses = Array.from(form.querySelectorAll('[data-result-input]')).map((input) => input.value);
    const progress = inspectionProgress(statuses);

    form.querySelectorAll('[data-progress-percentage]').forEach((element) => {
        element.textContent = String(progress.percentage) + '%';
    });
    form.querySelectorAll('[data-progress-bar]').forEach((element) => {
        element.style.width = String(progress.percentage) + '%';
    });
    form.querySelectorAll('[data-progress-caption]').forEach((element) => {
        element.textContent = String(progress.completed) + ' dari ' + String(progress.total) + ' item telah diperiksa';
    });
    form.querySelectorAll('[data-conform-count]').forEach((element) => {
        element.textContent = String(progress.conform);
    });
    form.querySelectorAll('[data-nonconform-count]').forEach((element) => {
        element.textContent = String(progress.nonconform);
    });
    form.querySelectorAll('[data-remaining-count]').forEach((element) => {
        element.textContent = String(progress.remaining);
    });

    const track = form.querySelector('[data-progress-track]');
    if (track) {
        track.setAttribute('aria-valuenow', String(progress.completed));
    }
}

export function initializeInspectionForms(root = document) {
    root.querySelectorAll('[data-inspection-form]').forEach((form) => {
        if (form.dataset.inspectionInitialized === 'true') {
            return;
        }

        form.dataset.inspectionInitialized = 'true';

        form.querySelectorAll('[data-inspection-result]').forEach((button) => {
            button.addEventListener('click', () => {
                const item = button.closest('[data-inspection-item]');
                if (!item) {
                    return;
                }

                updateItemState(item, button.dataset.inspectionResult);
                updateProgress(form);
            });
        });

        form.querySelectorAll('[data-inspection-item]').forEach((item) => {
            const input = item.querySelector('[data-result-input]');
            const findingFields = item.querySelector('[data-finding-fields]');
            const findingDescription = item.querySelector('[data-finding-description]');
            if (findingFields && input) {
                findingFields.hidden = !shouldShowFindingFields(input.value);
            }
            if (findingDescription && input && !findingDescription.disabled) {
                findingDescription.required = shouldShowFindingFields(input.value);
            }
        });

        form.querySelector('[data-mark-all-conform]')?.addEventListener('click', () => {
            form.querySelectorAll('[data-inspection-item]').forEach((item) => {
                updateItemState(item, 'sesuai');
            });
            updateProgress(form);
        });

        updateProgress(form);
    });
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initializeInspectionForms());
    } else {
        initializeInspectionForms();
    }
}
