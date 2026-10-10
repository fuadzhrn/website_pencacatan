import assert from 'node:assert/strict';
import test from 'node:test';

import {
    createFindingSaveMessage,
    filterFindings,
    getTrappedFocusIndex,
    isSupportedFindingPhoto,
    shouldCloseMobileSheet,
    summarizeFindings,
    updateFindingStatus,
    upsertFinding,
} from './mobile-findings.js';

const findings = [
    {
        id: 'finding-1',
        machineKey: 'cabin',
        machine: 'X-Ray Cabin',
        date: '2026-10-09',
        item: 'Indicator lamp tidak menyala',
        description: 'Lampu ready mati',
        followUp: 'Periksa kabel lampu',
        status: 'review',
    },
    {
        id: 'finding-2',
        machineKey: 'cargo',
        machine: 'X-Ray Cargo',
        date: '2026-10-10',
        item: 'UPS tidak normal',
        description: 'Daya cadangan menurun',
        followUp: 'Ganti battery UPS',
        status: 'open',
    },
    {
        id: 'finding-3',
        machineKey: 'baggage',
        machine: 'X-Ray Baggage HI-Scan 100100T',
        date: '2026-10-08',
        item: 'Conveyor belt tidak stabil',
        description: 'Belt bergerak miring',
        followUp: 'Periksa motor conveyor',
        status: 'resolved',
    },
];

test('filterFindings combines machine status date and case-insensitive search', () => {
    const result = filterFindings(findings, {
        machine: 'cargo',
        status: 'open',
        date: '2026-10-10',
        query: 'BATTERY',
    });

    assert.deepEqual(result.map((finding) => finding.id), ['finding-2']);
});

test('filterFindings searches finding details and treats empty filters as all options', () => {
    const result = filterFindings(findings, {
        machine: '',
        status: '',
        date: '',
        query: 'bergerak miring',
    });

    assert.deepEqual(result.map((finding) => finding.id), ['finding-3']);
});

test('filterFindings applies machine status and date filters independently', () => {
    assert.deepEqual(
        filterFindings(findings, { machine: 'baggage', status: '', date: '', query: '' }).map((finding) => finding.id),
        ['finding-3'],
    );
    assert.deepEqual(
        filterFindings(findings, { machine: '', status: 'review', date: '', query: '' }).map((finding) => finding.id),
        ['finding-1'],
    );
    assert.deepEqual(
        filterFindings(findings, { machine: '', status: '', date: '2026-10-10', query: '' }).map((finding) => finding.id),
        ['finding-2'],
    );
});

test('summarizeFindings counts each operational status', () => {
    assert.deepEqual(summarizeFindings(findings), {
        total: 3,
        open: 1,
        review: 1,
        resolved: 1,
    });
});

test('updateFindingStatus updates only the selected finding', () => {
    const result = updateFindingStatus(findings, 'finding-1', 'resolved');

    assert.equal(result[0].status, 'resolved');
    assert.equal(result[1], findings[1]);
    assert.equal(findings[0].status, 'review');
});

test('upsertFinding adds a new finding without changing existing entries', () => {
    const newFinding = { ...findings[0], id: 'finding-4', item: 'Lead curtain robek' };

    const result = upsertFinding(findings, newFinding);

    assert.equal(result.length, 4);
    assert.equal(result[3], newFinding);
    assert.equal(result[0], findings[0]);
});

test('upsertFinding replaces an edited finding without creating a duplicate', () => {
    const editedFinding = { ...findings[1], item: 'UPS gagal menyimpan daya' };

    const result = upsertFinding(findings, editedFinding);

    assert.equal(result.length, 3);
    assert.equal(result[1], editedFinding);
});

test('createFindingSaveMessage identifies add and edit actions', () => {
    assert.equal(
        createFindingSaveMessage(findings[0], false),
        'Indicator lamp tidak menyala berhasil ditambahkan.',
    );
    assert.equal(
        createFindingSaveMessage(findings[0], true),
        'Indicator lamp tidak menyala berhasil diperbarui.',
    );
});

test('getTrappedFocusIndex wraps focus inside a bottom sheet', () => {
    assert.equal(getTrappedFocusIndex(0, 3, true), 2);
    assert.equal(getTrappedFocusIndex(2, 3, false), 0);
    assert.equal(getTrappedFocusIndex(-1, 3, false), 0);
    assert.equal(getTrappedFocusIndex(0, 0, false), -1);
});

test('shouldCloseMobileSheet detects leaving the mobile breakpoint', () => {
    assert.equal(shouldCloseMobileSheet(768), false);
    assert.equal(shouldCloseMobileSheet(769), true);
});

test('isSupportedFindingPhoto accepts images and rejects other file types', () => {
    assert.equal(isSupportedFindingPhoto({ type: 'image/jpeg' }), true);
    assert.equal(isSupportedFindingPhoto({ type: 'image/webp' }), true);
    assert.equal(isSupportedFindingPhoto({ type: 'application/pdf' }), false);
    assert.equal(isSupportedFindingPhoto(null), false);
});
