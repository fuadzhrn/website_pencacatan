import { initializeInspectionForms, inspectionProgress } from './today-inspection.js';

export function checklistStatusLabel(statuses) {
    const progress = inspectionProgress(statuses);

    if (progress.total > 0 && progress.remaining === 0) {
        return progress.nonconform > 0
            ? { label: 'Ada Temuan', modifier: 'finding' }
            : { label: 'Lengkap', modifier: 'complete' };
    }

    return { label: 'Belum Lengkap', modifier: 'pending' };
}

function updateChecklistBadges(form) {
    const statuses = [...form.querySelectorAll('[data-result-input]')].map((input) => input.value);
    const status = checklistStatusLabel(statuses);
    const progressBadge = form.querySelector('[data-mobile-progress-status]');
    const categoryBadge = form.querySelector('[data-mobile-category-badge]');

    if (progressBadge) {
        progressBadge.textContent = status.label;
        progressBadge.className = `mobile-inspection-badge mobile-inspection-badge--${status.modifier}`;
    }

    if (categoryBadge) {
        categoryBadge.textContent = status.label;
        categoryBadge.className = status.modifier === 'pending'
            ? 'mobile-category-badge'
            : `mobile-category-badge mobile-category-badge--${status.modifier}`;
    }
}

function previewPhoto(input) {
    const preview = input.closest('label')?.nextElementSibling;

    if (!preview?.matches('[data-mobile-photo-preview]')) {
        return;
    }

    const image = preview.querySelector('img');
    const [file] = input.files ?? [];

    if (image.src.startsWith('blob:')) {
        URL.revokeObjectURL(image.src);
    }

    preview.hidden = !file;
    image.src = file ? URL.createObjectURL(file) : '';
    preview.querySelector('[data-mobile-photo-name]').textContent = file?.name ?? '';
}

export function initializeMobileInspection(root = document) {
    const page = root.querySelector('[data-mobile-inspection]');

    if (!page) {
        return;
    }

    initializeInspectionForms(page);

    page.querySelectorAll('[data-inspection-form]').forEach((form) => {
        updateChecklistBadges(form);
        form.addEventListener('click', (event) => {
            if (event.target.closest('[data-inspection-result], [data-mark-all-conform]')) {
                updateChecklistBadges(form);
            }
        });
    });

    page.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-mobile-category-toggle]');

        if (!toggle) {
            return;
        }

        const panel = document.getElementById(toggle.getAttribute('aria-controls'));
        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!isExpanded));

        if (panel) {
            panel.hidden = isExpanded;
        }
    });

    page.querySelectorAll('[data-mobile-photo-input]').forEach((input) => {
        input.addEventListener('change', () => previewPhoto(input));
    });
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initializeMobileInspection());
    } else {
        initializeMobileInspection();
    }
}
