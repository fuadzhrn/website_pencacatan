import assert from 'node:assert/strict';
import test from 'node:test';

import {
    createDrawerState,
    syncActiveNavigation,
    syncBackgroundInert,
    syncDocumentScrollLock,
    syncDrawerAccessibility,
} from './mobile-layout.js';

const createClassList = () => {
    const classes = new Set();

    return {
        contains: (className) => classes.has(className),
        toggle: (className, force) => {
            if (force) {
                classes.add(className);
            } else {
                classes.delete(className);
            }
        },
    };
};

const createElement = (href = '') => {
    const attributes = new Map();

    return {
        href,
        classList: createClassList(),
        setAttribute: (name, value) => attributes.set(name, value),
        removeAttribute: (name) => attributes.delete(name),
        getAttribute: (name) => attributes.get(name) ?? null,
        toggleAttribute: (name, force) => {
            if (force) {
                attributes.set(name, '');
            } else {
                attributes.delete(name);
            }
        },
    };
};

test('createDrawerState notifies subscribers when the drawer opens and closes', () => {
    const states = [];
    const drawerState = createDrawerState();
    drawerState.subscribe((isOpen) => states.push(isOpen));

    drawerState.open();
    drawerState.close();

    assert.deepEqual(states, [false, true, false]);
    assert.equal(drawerState.isOpen(), false);
});

test('syncDrawerAccessibility exposes only an open drawer to assistive technology', () => {
    const drawer = createElement();
    const openButton = createElement();
    const overlay = createElement();

    syncDrawerAccessibility({ drawer, openButton, overlay }, true);

    assert.equal(drawer.getAttribute('aria-hidden'), 'false');
    assert.equal(drawer.getAttribute('inert'), null);
    assert.equal(openButton.getAttribute('aria-expanded'), 'true');
    assert.equal(overlay.getAttribute('aria-hidden'), 'false');
});

test('syncDocumentScrollLock applies and removes the drawer scroll lock', () => {
    const root = createElement();
    const body = createElement();

    syncDocumentScrollLock({ root, body }, true);
    assert.equal(root.classList.contains('mobile-drawer-open'), true);
    assert.equal(body.classList.contains('mobile-drawer-open'), true);

    syncDocumentScrollLock({ root, body }, false);
    assert.equal(root.classList.contains('mobile-drawer-open'), false);
    assert.equal(body.classList.contains('mobile-drawer-open'), false);
});

test('syncBackgroundInert isolates the drawer from surrounding mobile content', () => {
    const topbar = createElement();
    const content = createElement();

    syncBackgroundInert([topbar, content], true);
    assert.equal(topbar.getAttribute('inert'), '');
    assert.equal(content.getAttribute('inert'), '');

    syncBackgroundInert([topbar, content], false);
    assert.equal(topbar.getAttribute('inert'), null);
    assert.equal(content.getAttribute('inert'), null);
});

test('syncActiveNavigation marks every link matching the current mobile path', () => {
    const dashboardLink = createElement('http://localhost/mobile/dashboard');
    const drawerFindingsLink = createElement('http://localhost/mobile/findings');
    const bottomFindingsLink = createElement('http://localhost/mobile/findings');

    syncActiveNavigation(
        [dashboardLink, drawerFindingsLink, bottomFindingsLink],
        'http://localhost/mobile/findings',
    );

    assert.equal(dashboardLink.classList.contains('is-active'), false);
    assert.equal(dashboardLink.getAttribute('aria-current'), null);
    assert.equal(drawerFindingsLink.classList.contains('is-active'), true);
    assert.equal(drawerFindingsLink.getAttribute('aria-current'), 'page');
    assert.equal(bottomFindingsLink.classList.contains('is-active'), true);
    assert.equal(bottomFindingsLink.getAttribute('aria-current'), 'page');
});

test('syncActiveNavigation ignores query strings and normalizes a trailing slash', () => {
    const reportLink = createElement('http://localhost/mobile/report');

    syncActiveNavigation([reportLink], 'http://localhost/mobile/report/?month=10');

    assert.equal(reportLink.classList.contains('is-active'), true);
    assert.equal(reportLink.getAttribute('aria-current'), 'page');
});
