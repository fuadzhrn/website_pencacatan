import assert from 'node:assert/strict';
import test from 'node:test';

import {
    createSaveMessage,
    filterChecklistItems,
    getTrappedFocusIndex,
    shouldCloseMobileSheet,
    summarizeChecklistItems,
    upsertChecklistItem,
} from './mobile-checklists.js';

const checklistItems = [
    {
        id: 'checklist-1',
        category: 'daily',
        name: 'Lead curtain dalam kondisi baik',
        criteria: 'Tidak robek dan menutup dengan baik',
        inputType: 'Sesuai/Tidak Sesuai',
        status: 'active',
        note: 'Periksa sisi masuk dan keluar.',
    },
    {
        id: 'checklist-2',
        category: 'weekly',
        name: 'Monitor menampilkan gambar jelas',
        criteria: 'Tidak buram dan tidak berkedip',
        inputType: 'Catatan',
        status: 'active',
        note: 'Amati gambar pada monitor operator.',
    },
    {
        id: 'checklist-3',
        category: 'monthly',
        name: 'UPS dalam kondisi normal',
        criteria: 'Backup daya bekerja baik',
        inputType: 'Sesuai/Tidak Sesuai',
        status: 'inactive',
        note: 'Uji perpindahan sumber daya.',
    },
];

test('filterChecklistItems combines category status and case-insensitive search', () => {
    const result = filterChecklistItems(checklistItems, {
        category: 'weekly',
        status: 'active',
        query: 'GAMBAR',
    });

    assert.deepEqual(result.map((item) => item.id), ['checklist-2']);
});

test('filterChecklistItems searches criteria and treats empty filters as all options', () => {
    const result = filterChecklistItems(checklistItems, {
        category: '',
        status: '',
        query: 'backup daya',
    });

    assert.deepEqual(result.map((item) => item.id), ['checklist-3']);
});

test('summarizeChecklistItems counts distinct categories and item statuses', () => {
    assert.deepEqual(summarizeChecklistItems(checklistItems), {
        categories: 3,
        total: 3,
        active: 2,
        inactive: 1,
    });
});

test('upsertChecklistItem adds a new item without changing existing items', () => {
    const result = upsertChecklistItem(checklistItems, {
        id: 'checklist-4',
        category: 'quarterly',
        name: 'Image orientation sesuai',
        criteria: 'Sesuai kebutuhan operasional',
        inputType: 'Catatan',
        status: 'active',
        note: '',
    });

    assert.equal(result.length, 4);
    assert.equal(result.at(-1)?.id, 'checklist-4');
    assert.equal(checklistItems.length, 3);
});

test('upsertChecklistItem replaces an edited item without adding a duplicate', () => {
    const result = upsertChecklistItem(checklistItems, {
        ...checklistItems[0],
        status: 'inactive',
    });

    assert.equal(result.length, 3);
    assert.equal(result[0].status, 'inactive');
});

test('createSaveMessage identifies whether an item was added or edited', () => {
    assert.equal(
        createSaveMessage({ name: 'Pemeriksaan UPS' }, false),
        'Pemeriksaan UPS berhasil ditambahkan.',
    );
    assert.equal(
        createSaveMessage({ name: 'Pemeriksaan UPS' }, true),
        'Pemeriksaan UPS berhasil diperbarui.',
    );
});

test('getTrappedFocusIndex wraps focus inside a bottom sheet', () => {
    assert.equal(getTrappedFocusIndex(2, 3, false), 0);
    assert.equal(getTrappedFocusIndex(0, 3, true), 2);
    assert.equal(getTrappedFocusIndex(-1, 3, false), 0);
});

test('shouldCloseMobileSheet detects when the viewport leaves the mobile breakpoint', () => {
    assert.equal(shouldCloseMobileSheet(768), false);
    assert.equal(shouldCloseMobileSheet(769), true);
    assert.equal(shouldCloseMobileSheet(1440), true);
});
