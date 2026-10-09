const ACTION_MESSAGES = {
    inspection: 'Membuka pemeriksaan hari ini.',
    schedules: 'Membuka jadwal maintenance.',
    findings: 'Membuka daftar temuan terbaru.',
    report: 'Membuka laporan bulanan.',
    'schedule-detail': 'Membuka detail jadwal pemeriksaan.',
};

export const getMobileDashboardActionMessage = (action) => ACTION_MESSAGES[action] ?? 'Aksi berhasil disimulasikan.';

export const createMobileDashboardNotifier = ({ show, beginHide, hide, schedule = setTimeout, cancel = clearTimeout }) => {
    let displayTimer = null;
    let hideTimer = null;

    return (message) => {
        if (displayTimer !== null) {
            cancel(displayTimer);
        }

        if (hideTimer !== null) {
            cancel(hideTimer);
        }

        show(message);
        displayTimer = schedule(() => {
            displayTimer = null;
            beginHide();
            hideTimer = schedule(() => {
                hideTimer = null;
                hide();
            }, 180);
        }, 2800);
    };
};

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        const dashboard = document.querySelector('[data-mobile-dashboard]');

        if (!dashboard) {
            return;
        }

        const toast = dashboard.querySelector('[data-mobile-dashboard-toast]');
        const notify = createMobileDashboardNotifier({
            show: (message) => {
                toast.querySelector('span').textContent = message;
                toast.hidden = false;
                toast.classList.add('is-visible');
            },
            beginHide: () => toast.classList.remove('is-visible'),
            hide: () => {
                toast.hidden = true;
            },
            schedule: window.setTimeout.bind(window),
            cancel: window.clearTimeout.bind(window),
        });

        dashboard.querySelectorAll('[data-mobile-dashboard-action]').forEach((button) => {
            button.addEventListener('click', () => {
                const message = button.dataset.feedback
                    || getMobileDashboardActionMessage(button.dataset.mobileDashboardAction);

                notify(message);
            });
        });
    });
}
