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

export const syncBackgroundInert = (elements, isInert) => {
    elements.forEach((element) => element.toggleAttribute('inert', isInert));
};

const normalizeNavigationPath = (url) => {
    const pathname = new URL(url, 'http://localhost').pathname;
    const normalizedPath = pathname.replace(/\/+$/, '');

    return normalizedPath === '' ? '/' : normalizedPath;
};

export const syncActiveNavigation = (links, currentUrl) => {
    const currentPath = normalizeNavigationPath(currentUrl);

    links.forEach((link) => {
        const isActive = normalizeNavigationPath(link.href) === currentPath;

        link.classList.toggle('is-active', isActive);

        if (isActive) {
            link.setAttribute('aria-current', 'page');
        } else {
            link.removeAttribute('aria-current');
        }
    });
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
        const backgroundElements = [...mobileShell.children].filter((element) => element !== drawer && element !== overlay);
        const navigationLinks = [...mobileShell.querySelectorAll('[data-mobile-nav-link]')];
        const drawerLinks = [...drawer.querySelectorAll('[data-mobile-nav-link]')];
        const notificationButton = mobileShell.querySelector('[data-mobile-notification]');
        const logoutButton = mobileShell.querySelector('[data-mobile-logout]');
        const navigationToast = mobileShell.querySelector('[data-mobile-navigation-toast]');
        const navigationToastMessage = navigationToast?.querySelector('[data-mobile-navigation-toast-message]');
        const navigationToastClose = navigationToast?.querySelector('[data-mobile-navigation-toast-close]');
        const drawerState = createDrawerState();
        const desktopBreakpoint = window.matchMedia('(min-width: 769px)');
        let drawerTrigger = null;
        let toastTimeout = null;

        const drawerFocusableElements = () => [...drawer.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')];

        const closeDrawer = ({ restoreFocus = true } = {}) => {
            const shouldRestoreFocus = restoreFocus && drawerState.isOpen();
            drawerState.close();

            if (shouldRestoreFocus && drawerTrigger) {
                drawerTrigger.focus();
            }
        };

        const hideNavigationToast = () => {
            if (!navigationToast) {
                return;
            }

            navigationToast.hidden = true;

            if (toastTimeout) {
                window.clearTimeout(toastTimeout);
                toastTimeout = null;
            }
        };

        const showNavigationToast = (message) => {
            if (!navigationToast || !navigationToastMessage) {
                return;
            }

            navigationToastMessage.textContent = message;
            navigationToast.hidden = false;

            if (toastTimeout) {
                window.clearTimeout(toastTimeout);
            }

            toastTimeout = window.setTimeout(hideNavigationToast, 3500);
        };

        drawerState.subscribe((isOpen) => {
            mobileShell.classList.toggle('is-drawer-open', isOpen);
            syncDrawerAccessibility({ drawer, openButton, overlay }, isOpen);
            syncDocumentScrollLock({ root: document.documentElement, body: document.body }, isOpen);
            syncBackgroundInert(backgroundElements, isOpen);

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

        drawerLinks.forEach((link) => {
            link.addEventListener('click', () => closeDrawer({ restoreFocus: false }));
        });

        if (window.location.pathname.startsWith('/mobile/')) {
            syncActiveNavigation(navigationLinks, window.location.href);
        }

        notificationButton?.addEventListener('click', () => {
            showNavigationToast('Anda memiliki 2 notifikasi dummy.');
        });

        logoutButton?.addEventListener('click', () => {
            closeDrawer({ restoreFocus: false });
            showNavigationToast('Logout masih berupa simulasi pada tahap ini.');
        });

        navigationToastClose?.addEventListener('click', hideNavigationToast);

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
