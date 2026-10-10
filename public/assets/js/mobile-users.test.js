import assert from 'node:assert/strict';
import test from 'node:test';

import {
    filterUsers,
    findUserById,
    getTrappedFocusIndex,
    removeUser,
    summarizeUsers,
    toggleUserStatus,
    upsertUser,
    validatePasswordConfirmation,
} from './mobile-users.js';

const users = [
    {
        id: 'user-1',
        name: 'Ahmad Admin',
        email: 'admin@example.com',
        phone: '0821xxxx',
        role: 'admin',
        status: 'active',
    },
    {
        id: 'user-2',
        name: 'Budi Santoso',
        email: 'petugas@example.com',
        phone: '0822xxxx',
        role: 'petugas',
        status: 'active',
    },
    {
        id: 'user-3',
        name: 'Siti Rahma',
        email: 'supervisor@example.com',
        phone: '0823xxxx',
        role: 'supervisor',
        status: 'active',
    },
];

test('filterUsers matches name or email and every selected filter', () => {
    const result = filterUsers(users, {
        query: 'SUPERVISOR@EXAMPLE.COM',
        role: 'supervisor',
        status: 'active',
    });

    assert.deepEqual(result.map((user) => user.id), ['user-3']);
});

test('filterUsers treats empty filters as all users', () => {
    const result = filterUsers(users, { query: '', role: '', status: '' });

    assert.deepEqual(result.map((user) => user.id), ['user-1', 'user-2', 'user-3']);
});

test('summarizeUsers counts visible users by role', () => {
    assert.deepEqual(summarizeUsers(users), {
        total: 3,
        admin: 1,
        petugas: 1,
        supervisor: 1,
    });
});

test('findUserById returns the selected user and null for an unknown id', () => {
    assert.equal(findUserById(users, 'user-2')?.name, 'Budi Santoso');
    assert.equal(findUserById(users, 'missing-user'), null);
});

test('upsertUser adds a new user and replaces an existing user without mutating input', () => {
    const newUser = {
        id: 'user-4',
        name: 'Rudi Hartono',
        email: 'rudi@example.com',
        role: 'petugas',
        status: 'inactive',
    };
    const addedUsers = upsertUser(users, newUser);
    const editedUsers = upsertUser(users, { ...users[1], name: 'Budi Pratama' });

    assert.equal(addedUsers.length, 4);
    assert.equal(addedUsers[3].name, 'Rudi Hartono');
    assert.equal(editedUsers[1].name, 'Budi Pratama');
    assert.equal(users[1].name, 'Budi Santoso');
});

test('toggleUserStatus changes only the selected user without mutating input', () => {
    const result = toggleUserStatus(users, 'user-2');

    assert.equal(result[1].status, 'inactive');
    assert.equal(result[0].status, 'active');
    assert.equal(users[1].status, 'active');
});

test('removeUser removes only the selected user without mutating input', () => {
    const result = removeUser(users, 'user-2');

    assert.deepEqual(result.map((user) => user.id), ['user-1', 'user-3']);
    assert.equal(users.length, 3);
});

test('validatePasswordConfirmation enforces password on create and matching confirmation', () => {
    assert.deepEqual(validatePasswordConfirmation('', '', false), {
        valid: false,
        message: 'Password wajib diisi untuk user baru.',
    });
    assert.deepEqual(validatePasswordConfirmation('rahasia123', 'berbeda', false), {
        valid: false,
        message: 'Konfirmasi password belum sama.',
    });
    assert.deepEqual(validatePasswordConfirmation('', '', true), { valid: true, message: '' });
    assert.deepEqual(validatePasswordConfirmation('rahasia123', 'rahasia123', false), { valid: true, message: '' });
});

test('getTrappedFocusIndex wraps keyboard focus inside a sheet', () => {
    assert.equal(getTrappedFocusIndex(2, 3, false), 0);
    assert.equal(getTrappedFocusIndex(0, 3, true), 2);
    assert.equal(getTrappedFocusIndex(1, 3, false), 2);
    assert.equal(getTrappedFocusIndex(-1, 0, false), -1);
});
