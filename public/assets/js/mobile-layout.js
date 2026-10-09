export const createDrawerState = (initialState = false) => {
    let openState = Boolean(initialState);
    const subscribers = new Set();

    const update = (nextState) => {
        openState = Boolean(nextState);
        subscribers.forEach((subscriber) => subscriber(openState));
    };

    return {
        isOpen: () => openState,
        open: () => update(true),
        close: () => update(false),
        toggle: () => update(!openState),
        subscribe: (subscriber) => {
            subscribers.add(subscriber);
            subscriber(openState);

            return () => subscribers.delete(subscriber);
        },
    };
};

export const syncDrawerAccessibility = ({ drawer, openButton, overlay }, isOpen) => {
    drawer.setAttribute('aria-hidden', String(!isOpen));
    drawer.toggleAttribute('inert', !isOpen);
    openButton.setAttribute('aria-expanded', String(isOpen));
    overlay.setAttribute('aria-hidden', String(!isOpen));
};

export const syncDocumentScrollLock = ({ root, body }, isLocked) => {
    root.classList.toggle('mobile-drawer-open', isLocked);
    body.classList.toggle('mobile-drawer-open', isLocked);
};

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        const mobileShell = document.querySelector('[data-mobile-shell]');

        if (!mobileShell) {
            return;
        }

        const drawer = mobileShell.querySelector('[data-mobile-drawer]');
        const openButton = mobileShell.querySelector('[data-mobile-drawer-open]');
        const closeButtons = [...mobileShell.querySelectorAll('[data-mobile-drawer-close]')];
        const overlay = mobileShell.querySelector('.mobile-drawer-overlay');
        const dummyLinks = [...mobileShell.querySelectorAll('[data-mobile-dummy-link]')];
        const drawerState = createDrawerState();
        const desktopBreakpoint = window.matchMedia('(min-width: 769px)');
        let drawerTrigger = null;

        const drawerFocusableElements = () => [...drawer.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')];

        const closeDrawer = ({ restoreFocus = true } = {}) => {
            const shouldRestoreFocus = restoreFocus && drawerState.isOpen();
            drawerState.close();

            if (shouldRestoreFocus && drawerTrigger) {
                drawerTrigger.focus();
            }
        };

        drawerState.subscribe((isOpen) => {
            mobileShell.classList.toggle('is-drawer-open', isOpen);
            syncDrawerAccessibility({ drawer, openButton, overlay }, isOpen);
            syncDocumentScrollLock({ root: document.documentElement, body: document.body }, isOpen);

            if (isOpen) {
                window.requestAnimationFrame(() => {
                    drawerFocusableElements()[0]?.focus();
                });
            }
        });

        openButton.addEventListener('click', () => {
            drawerTrigger = openButton;
            drawerState.open();
        });

        closeButtons.forEach((button) => {
            button.addEventListener('click', () => closeDrawer());
        });

        dummyLinks.forEach((link) => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                mobileShell.querySelectorAll('.mobile-drawer__link').forEach((menuItem) => {
                    menuItem.classList.toggle('is-active', menuItem === link);
                });
                closeDrawer();
            });
        });

        document.addEventListener('keydown', (event) => {
            if (!drawerState.isOpen()) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeDrawer();
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const focusableElements = drawerFocusableElements();
            const firstElement = focusableElements[0];
            const lastElement = focusableElements.at(-1);

            if (event.shiftKey && document.activeElement === firstElement) {
                event.preventDefault();
                lastElement?.focus();
            } else if (!event.shiftKey && document.activeElement === lastElement) {
                event.preventDefault();
                firstElement?.focus();
            }
        });

        desktopBreakpoint.addEventListener('change', (event) => {
            if (event.matches) {
                closeDrawer({ restoreFocus: false });
            }
        });
    });
}
