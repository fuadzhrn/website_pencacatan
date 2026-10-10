export function filterUsers(users, filters) {
    const query = (filters.query ?? '').trim().toLocaleLowerCase('id-ID');

    return users.filter((user) => {
        const searchableText = `${user.name} ${user.email}`.toLocaleLowerCase('id-ID');
        const matchesQuery = query === '' || searchableText.includes(query);
        const matchesRole = !filters.role || user.role === filters.role;
        const matchesStatus = !filters.status || user.status === filters.status;

        return matchesQuery && matchesRole && matchesStatus;
    });
}

export function summarizeUsers(users) {
    return users.reduce((summary, user) => {
        summary.total += 1;

        if (Object.hasOwn(summary, user.role)) {
            summary[user.role] += 1;
        }

        return summary;
    }, {
        total: 0,
        admin: 0,
        petugas: 0,
        supervisor: 0,
    });
}

export function findUserById(users, userId) {
    return users.find((user) => user.id === userId) ?? null;
}

export function upsertUser(users, user) {
    const existingIndex = users.findIndex((candidate) => candidate.id === user.id);

    if (existingIndex === -1) {
        return [...users, { ...user }];
    }

    return users.map((candidate, index) => index === existingIndex ? { ...user } : candidate);
}

export function toggleUserStatus(users, userId) {
    return users.map((user) => {
        if (user.id !== userId) {
            return user;
        }

        const status = user.status === 'active' ? 'inactive' : 'active';

        return {
            ...user,
            status,
            statusLabel: status === 'active' ? 'Aktif' : 'Nonaktif',
        };
    });
}

export function removeUser(users, userId) {
    return users.filter((user) => user.id !== userId);
}

export function resolveFocusTarget(previousTarget, replacementTarget, fallbackTarget) {
    return [replacementTarget, previousTarget, fallbackTarget]
        .find((target) => target?.isConnected) ?? null;
}

export function validatePasswordConfirmation(password, confirmation, isEditing) {
    if (!isEditing && password === '') {
        return {
            valid: false,
            message: 'Password wajib diisi untuk user baru.',
        };
    }

    if (password !== confirmation) {
        return {
            valid: false,
            message: 'Konfirmasi password belum sama.',
        };
    }

    return { valid: true, message: '' };
}

export function getTrappedFocusIndex(currentIndex, focusableCount, backwards) {
    if (focusableCount <= 0) {
        return -1;
    }

    if (currentIndex < 0) {
        return backwards ? focusableCount - 1 : 0;
    }

    return backwards
        ? (currentIndex - 1 + focusableCount) % focusableCount
        : (currentIndex + 1) % focusableCount;
}

const roleLabels = {
    admin: 'Admin',
    petugas: 'Petugas',
    supervisor: 'Supervisor',
};

const statusLabels = {
    active: 'Aktif',
    inactive: 'Nonaktif',
};

function getInitials(name) {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toLocaleUpperCase('id-ID'))
        .join('');
}

function initializeMobileUsers() {
    const page = document.querySelector('[data-mobile-users]');

    if (!page) {
        return;
    }

    const filterForm = page.querySelector('[data-mobile-users-filter]');
    const resetButton = page.querySelector('[data-mobile-users-reset]');
    const list = page.querySelector('[data-mobile-users-list]');
    const emptyState = page.querySelector('[data-mobile-users-empty]');
    const resultCount = page.querySelector('[data-mobile-users-result-count]');
    const cardTemplate = page.querySelector('[data-mobile-users-card-template]');
    const addButton = page.querySelector('[data-mobile-users-add]');
    const userForm = page.querySelector('[data-mobile-users-form]');
    const formTitle = page.querySelector('[data-mobile-users-form-title]');
    const formError = page.querySelector('[data-mobile-users-form-error]');
    const passwordHint = page.querySelector('[data-mobile-users-password-hint]');
    const passwordToggle = page.querySelector('[data-mobile-users-password-toggle]');
    const actionsTitle = page.querySelector('[data-mobile-users-actions-title]');
    const toggleStatusButton = page.querySelector('[data-mobile-users-toggle-status]');
    const toggleStatusLabel = page.querySelector('[data-mobile-users-toggle-label]');
    const resetPasswordButton = page.querySelector('[data-mobile-users-reset-password]');
    const deleteButton = page.querySelector('[data-mobile-users-delete]');
    const toast = page.querySelector('[data-mobile-users-toast]');
    const toastMessage = page.querySelector('[data-mobile-users-toast-message]');
    const toastCloseButton = page.querySelector('[data-mobile-users-toast-close]');
    const sheets = new Map(
        [...page.querySelectorAll('[data-mobile-users-sheet]')]
            .map((sheet) => [sheet.dataset.mobileUsersSheet, sheet]),
    );
    const initialCards = [...page.querySelectorAll('[data-mobile-users-card]')];
    const mobileShell = page.closest('[data-mobile-shell]');
    const backgroundInertStates = new Map();
    let users = initialCards.map((card) => ({
        id: card.dataset.id,
        name: card.dataset.name,
        email: card.dataset.email,
        phone: card.dataset.phone,
        role: card.dataset.role,
        roleLabel: card.dataset.roleLabel,
        status: card.dataset.status,
        statusLabel: card.dataset.statusLabel,
        createdAt: card.dataset.createdAt,
        lastActivity: card.dataset.lastActivity,
    }));
    let nextUserNumber = users.length + 1;
    let selectedUserId = null;
    let activeSheet = null;
    let lastFocusedElement = null;
    let toastTimer = null;

    function refreshIcons() {
        window.lucide?.createIcons();
    }

    function readFilters() {
        const formData = new FormData(filterForm);

        return {
            query: formData.get('query')?.toString() ?? '',
            role: formData.get('role')?.toString() ?? '',
            status: formData.get('status')?.toString() ?? '',
        };
    }

    function updateSummary(visibleUsers) {
        const summary = summarizeUsers(visibleUsers);

        Object.entries(summary).forEach(([key, value]) => {
            const output = page.querySelector(`[data-mobile-users-summary="${key}"]`);

            if (output) {
                output.textContent = value.toString();
            }
        });

        if (resultCount) {
            resultCount.textContent = `${visibleUsers.length} user`;
        }
    }

    function setCardField(card, field, value) {
        const output = card.querySelector(`[data-card-field="${field}"]`);

        if (output) {
            output.textContent = value;
        }
    }

    function createUserCard(user) {
        const card = cardTemplate.content.firstElementChild.cloneNode(true);
        const emailLink = card.querySelector('[data-card-field="email-link"]');
        const roleBadge = card.querySelector('[data-card-field="role"]');
        const statusBadge = card.querySelector('[data-card-field="status"]');
        const actionButtons = card.querySelectorAll('[data-user-id], [data-mobile-users-actions-open], [data-mobile-users-detail-open], [data-mobile-users-form-open]');

        card.dataset.id = user.id;
        setCardField(card, 'initials', getInitials(user.name));
        setCardField(card, 'name', user.name);
        setCardField(card, 'email', user.email);
        setCardField(card, 'phone', user.phone);
        setCardField(card, 'role', user.roleLabel);
        setCardField(card, 'status', user.statusLabel);

        if (emailLink) {
            emailLink.href = `mailto:${user.email}`;
        }

        if (roleBadge) {
            roleBadge.className = `mobile-users-badge mobile-users-badge--role-${user.role}`;
        }

        if (statusBadge) {
            statusBadge.className = `mobile-users-badge mobile-users-badge--status-${user.status}`;
        }

        actionButtons.forEach((button) => {
            button.dataset.userId = user.id;

            if (button.matches('[data-mobile-users-actions-open]')) {
                button.setAttribute('aria-label', `Buka aksi untuk ${user.name}`);
            }
        });

        return card;
    }

    function renderUsers(visibleUsers) {
        list.replaceChildren(...visibleUsers.map(createUserCard));
        list.hidden = visibleUsers.length === 0;
        emptyState.hidden = visibleUsers.length > 0;
        updateSummary(visibleUsers);
        refreshIcons();
    }

    function applyFilters() {
        renderUsers(filterUsers(users, readFilters()));
    }

    function closeToast() {
        window.clearTimeout(toastTimer);
        toast.hidden = true;
    }

    function showToast(message) {
        window.clearTimeout(toastTimer);
        toastMessage.textContent = message;
        toast.hidden = false;
        toastTimer = window.setTimeout(closeToast, 4200);
    }

    function getBackgroundElements(sheet) {
        return [
            ...[...page.children].filter((element) => element !== sheet && element !== toast),
            ...[...(mobileShell?.children ?? [])].filter((element) => element !== page),
        ];
    }

    function openSheet(name, trigger) {
        const sheet = sheets.get(name);

        if (!sheet) {
            return;
        }

        activeSheet = sheet;
        lastFocusedElement = trigger ?? document.activeElement;
        getBackgroundElements(sheet).forEach((element) => {
            backgroundInertStates.set(element, element.inert);
            element.inert = true;
        });
        sheet.hidden = false;
        sheet.setAttribute('aria-hidden', 'false');
        document.body.classList.add('mobile-users-sheet-open');
        window.requestAnimationFrame(() => {
            sheet.classList.add('is-open');
            sheet.querySelector('[role="dialog"]')?.focus();
        });
    }

    function closeSheet(restoreSelector = null) {
        if (!activeSheet) {
            return;
        }

        const sheetToClose = activeSheet;
        const previousFocusTarget = lastFocusedElement;
        activeSheet = null;
        lastFocusedElement = null;
        sheetToClose.classList.remove('is-open');
        sheetToClose.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('mobile-users-sheet-open');

        window.setTimeout(() => {
            sheetToClose.hidden = true;
            backgroundInertStates.forEach((wasInert, element) => {
                element.inert = wasInert;
            });
            backgroundInertStates.clear();
            const replacementTarget = restoreSelector ? page.querySelector(restoreSelector) : null;

            resolveFocusTarget(previousFocusTarget, replacementTarget, addButton)?.focus();
        }, 220);
    }

    function setDetailValue(field, value) {
        const output = page.querySelector(`[data-mobile-users-detail="${field}"]`);

        if (output) {
            output.textContent = value;
        }
    }

    function openDetail(userId, trigger) {
        const user = findUserById(users, userId);

        if (!user) {
            return;
        }

        setDetailValue('initials', getInitials(user.name));
        setDetailValue('name', user.name);
        setDetailValue('email', user.email);
        setDetailValue('phone', user.phone);
        setDetailValue('createdAt', user.createdAt);
        setDetailValue('lastActivity', user.lastActivity);
        setDetailValue('role', user.roleLabel);
        setDetailValue('status', user.statusLabel);

        const roleBadge = page.querySelector('[data-mobile-users-detail="role"]');
        const statusBadge = page.querySelector('[data-mobile-users-detail="status"]');

        roleBadge.className = `mobile-users-badge mobile-users-badge--role-${user.role}`;
        statusBadge.className = `mobile-users-badge mobile-users-badge--status-${user.status}`;
        openSheet('detail', trigger);
    }

    function setFormValue(name, value) {
        const input = userForm.elements.namedItem(name);

        if (input) {
            input.value = value;
        }
    }

    function openUserForm(userId, trigger) {
        const user = userId ? findUserById(users, userId) : null;

        userForm.reset();
        formError.hidden = true;
        formError.textContent = '';
        const passwordInput = userForm.elements.namedItem('password');

        passwordInput.type = 'password';
        passwordToggle.setAttribute('aria-label', 'Tampilkan password');
        passwordToggle.querySelector('[data-lucide]')?.setAttribute('data-lucide', 'eye');
        selectedUserId = user?.id ?? null;
        formTitle.textContent = user ? 'Edit User' : 'Tambah User';
        passwordHint.textContent = user ? '(opsional)' : '(wajib)';

        if (user) {
            setFormValue('id', user.id);
            setFormValue('name', user.name);
            setFormValue('email', user.email);
            setFormValue('phone', user.phone);
            setFormValue('role', user.role);
            setFormValue('status', user.status);
        } else {
            setFormValue('id', '');
            setFormValue('role', 'petugas');
            setFormValue('status', 'active');
        }

        openSheet('form', trigger);
    }

    function openActions(userId, trigger) {
        const user = findUserById(users, userId);

        if (!user) {
            return;
        }

        selectedUserId = user.id;
        actionsTitle.textContent = user.name;
        toggleStatusLabel.textContent = user.status === 'active' ? 'Nonaktifkan User' : 'Aktifkan User';
        openSheet('actions', trigger);
    }

    filterForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        applyFilters();
    });

    resetButton?.addEventListener('click', () => {
        filterForm.reset();
        applyFilters();
    });

    addButton?.addEventListener('click', (event) => openUserForm(null, event.currentTarget));

    list?.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        const detailButton = target?.closest('[data-mobile-users-detail-open]');
        const editButton = target?.closest('[data-mobile-users-form-open]');
        const actionsButton = target?.closest('[data-mobile-users-actions-open]');

        if (detailButton) {
            openDetail(detailButton.dataset.userId, detailButton);
        } else if (editButton) {
            openUserForm(editButton.dataset.userId, editButton);
        } else if (actionsButton) {
            openActions(actionsButton.dataset.userId, actionsButton);
        }
    });

    userForm?.addEventListener('submit', (event) => {
        event.preventDefault();

        if (!userForm.reportValidity()) {
            return;
        }

        const formData = new FormData(userForm);
        const isEditing = selectedUserId !== null;
        const passwordCheck = validatePasswordConfirmation(
            formData.get('password')?.toString() ?? '',
            formData.get('passwordConfirmation')?.toString() ?? '',
            isEditing,
        );

        if (!passwordCheck.valid) {
            formError.textContent = passwordCheck.message;
            formError.hidden = false;
            return;
        }

        const existingUser = findUserById(users, selectedUserId);
        const role = formData.get('role')?.toString() ?? 'petugas';
        const status = formData.get('status')?.toString() ?? 'active';
        const user = {
            id: existingUser?.id ?? `user-${nextUserNumber++}`,
            name: formData.get('name')?.toString().trim() ?? '',
            email: formData.get('email')?.toString().trim() ?? '',
            phone: formData.get('phone')?.toString().trim() ?? '',
            role,
            roleLabel: roleLabels[role],
            status,
            statusLabel: statusLabels[status],
            createdAt: existingUser?.createdAt ?? '10 Oktober 2026',
            lastActivity: existingUser?.lastActivity ?? 'Belum pernah login',
        };

        users = upsertUser(users, user);
        const restoreSelector = isEditing
            ? `[data-mobile-users-form-open][data-user-id="${user.id}"]`
            : null;

        closeSheet(restoreSelector);
        applyFilters();
        showToast(`${isEditing ? 'Perubahan' : 'User baru'} ${user.name} berhasil disimpan.`);
    });

    passwordToggle?.addEventListener('click', () => {
        const passwordInput = userForm.elements.namedItem('password');
        const shouldShow = passwordInput.type === 'password';

        passwordInput.type = shouldShow ? 'text' : 'password';
        passwordToggle.setAttribute('aria-label', shouldShow ? 'Sembunyikan password' : 'Tampilkan password');
        passwordToggle.querySelector('[data-lucide]')?.setAttribute('data-lucide', shouldShow ? 'eye-off' : 'eye');
        refreshIcons();
    });

    toggleStatusButton?.addEventListener('click', () => {
        const user = findUserById(users, selectedUserId);

        if (!user) {
            return;
        }

        users = toggleUserStatus(users, user.id);
        const updatedUser = findUserById(users, user.id);
        closeSheet(`[data-mobile-users-actions-open][data-user-id="${user.id}"]`);
        applyFilters();
        showToast(`${updatedUser.name} sekarang berstatus ${updatedUser.statusLabel}.`);
    });

    resetPasswordButton?.addEventListener('click', () => {
        const user = findUserById(users, selectedUserId);

        if (!user) {
            return;
        }

        closeSheet();
        showToast(`Instruksi reset password untuk ${user.name} berhasil disimulasikan.`);
    });

    deleteButton?.addEventListener('click', () => {
        const user = findUserById(users, selectedUserId);

        if (!user || !window.confirm(`Hapus ${user.name} dari daftar user?`)) {
            return;
        }

        users = removeUser(users, user.id);
        closeSheet('[data-mobile-users-actions-open]');
        applyFilters();
        showToast(`${user.name} berhasil dihapus dari daftar simulasi.`);
    });

    sheets.forEach((sheet) => {
        sheet.querySelectorAll('[data-mobile-users-sheet-close]')
            .forEach((button) => button.addEventListener('click', () => closeSheet()));
    });
    toastCloseButton?.addEventListener('click', closeToast);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && activeSheet) {
            closeSheet();
        }

        if (event.key === 'Tab' && activeSheet) {
            const dialog = activeSheet.querySelector('[role="dialog"]');
            const focusableElements = [...dialog.querySelectorAll(
                'button:not([disabled]):not([tabindex="-1"]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
            )];
            const currentIndex = focusableElements.indexOf(document.activeElement);
            const nextIndex = getTrappedFocusIndex(currentIndex, focusableElements.length, event.shiftKey);

            if (nextIndex >= 0) {
                event.preventDefault();
                focusableElements[nextIndex].focus();
            }
        }
    });

    applyFilters();
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeMobileUsers, { once: true });
    } else {
        initializeMobileUsers();
    }
}
