export const toLocalIsoDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const formatCurrentDate = () => {
    const dateElement = document.querySelector('#current-date');

    if (!dateElement) {
        return;
    }

    const currentDate = new Date();
    const formattedDate = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(currentDate);

    dateElement.dateTime = toLocalIsoDate(currentDate);
    dateElement.textContent = formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1);
};

const initializeIcons = () => {
    if (window.lucide) {
        window.lucide.createIcons({
            attrs: {
                'stroke-width': 1.8,
            },
        });
    }
};

const submitButtonSelector = 'button[type="submit"], button:not([type]), input[type="submit"]';

/**
 * Block repeated submissions of data-changing forms. Buttons are disabled after
 * the submit event finishes so the clicked button's name/value is still sent.
 */
export const preventDoubleSubmit = (root) => {
    root.addEventListener('submit', (event) => {
        const form = event.target;

        if (event.defaultPrevented || form?.tagName !== 'FORM' || form.method.toLowerCase() !== 'post') {
            return;
        }

        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }

        form.dataset.submitting = 'true';
        form.setAttribute('aria-busy', 'true');
        setTimeout(() => {
            form.querySelectorAll(submitButtonSelector).forEach((button) => {
                if (!button.disabled) {
                    button.disabled = true;
                    button.dataset.submitLocked = 'true';
                }
            });
        }, 0);
    });
};

const resetSubmittingForms = () => {
    document.querySelectorAll('form[data-submitting="true"]').forEach((form) => {
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        form.querySelectorAll('[data-submit-locked]').forEach((button) => {
            button.disabled = false;
            delete button.dataset.submitLocked;
        });
    });
};

if (typeof document !== 'undefined') {
    preventDoubleSubmit(document);

    document.addEventListener('DOMContentLoaded', () => {
        formatCurrentDate();
        initializeIcons();
    });
}

if (typeof window !== 'undefined') {
    window.addEventListener('load', initializeIcons);
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            resetSubmittingForms();
        }
    });
}
