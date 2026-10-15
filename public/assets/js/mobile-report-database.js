export const MOBILE_REPORT_MODES = ['summary', 'detail', 'table'];

export function normalizeMobileReportMode(mode) {
    return MOBILE_REPORT_MODES.includes(mode) ? mode : 'summary';
}

export function initializeMobileReportDatabase(root = document) {
    const view = root.querySelector('[data-mobile-report-database]');

    if (!view) {
        return;
    }

    const modeButtons = [...view.querySelectorAll('[data-mobile-report-mode]')];
    const panels = [...view.querySelectorAll('[data-mobile-report-panel]')];
    const detailItems = [...view.querySelectorAll('[data-mobile-report-detail-item]')];

    const setMode = (requestedMode) => {
        const mode = normalizeMobileReportMode(requestedMode);

        modeButtons.forEach((button) => {
            const isActive = button.dataset.mobileReportMode === mode;
            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', String(isActive));
            button.tabIndex = isActive ? 0 : -1;
        });

        panels.forEach((panel) => {
            panel.hidden = panel.dataset.mobileReportPanel !== mode;
        });
    };

    const showDetail = (rowIndex) => {
        detailItems.forEach((item) => {
            item.hidden = item.dataset.mobileReportDetailItem !== rowIndex;
        });
        setMode('detail');
        view.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    view.addEventListener('click', (event) => {
        const modeButton = event.target.closest('[data-mobile-report-mode]');
        const detailButton = event.target.closest('[data-mobile-report-detail]');

        if (modeButton) {
            setMode(modeButton.dataset.mobileReportMode);
        } else if (detailButton) {
            showDetail(detailButton.dataset.mobileReportDetail);
        } else if (event.target.closest('[data-mobile-report-mode-back]')) {
            setMode('summary');
        }
    });

    view.addEventListener('keydown', (event) => {
        const currentIndex = modeButtons.indexOf(document.activeElement);

        if (currentIndex < 0 || !['ArrowLeft', 'ArrowRight'].includes(event.key)) {
            return;
        }

        event.preventDefault();
        const offset = event.key === 'ArrowRight' ? 1 : -1;
        const nextButton = modeButtons[(currentIndex + offset + modeButtons.length) % modeButtons.length];
        setMode(nextButton.dataset.mobileReportMode);
        nextButton.focus();
    });
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initializeMobileReportDatabase());
    } else {
        initializeMobileReportDatabase();
    }
}
