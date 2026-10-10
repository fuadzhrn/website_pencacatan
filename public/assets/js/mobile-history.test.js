import assert from 'node:assert/strict';
import test from 'node:test';

import {
    createPrintMessage,
    filterHistory,
    findHistoryById,
    getTrappedFocusIndex,
    summarizeHistory,
} from './mobile-history.js';

const historyRecords = [
    {
        id: 'inspection-1',
        date: '2026-10-09',
        machine: 'baggage',
        officer: 'budi',
        resultStatus: 'normal',
        verificationStatus: 'verified',
        machineName: 'X-Ray Baggage HI-Scan 100100T',
        searchableText: 'x-ray baggage hi-scan 100100t terminal 1 budi santoso normal terverifikasi',
    },
    {
        id: 'inspection-2',
        date: '2026-10-08',
        machine: 'cabin',
        officer: 'andi',
        resultStatus: 'finding',
        verificationStatus: 'pending',
        machineName: 'X-Ray Cabin',
        searchableText: 'x-ray cabin terminal 2 andi pratama terdapat temuan menunggu verifikasi',
    },
    {
        id: 'inspection-3',
        date: '2026-10-07',
        machine: 'cargo',
        officer: 'siti',
        resultStatus: 'normal',
        verificationStatus: 'verified',
        machineName: 'X-Ray Cargo',
        searchableText: 'x-ray cargo area cargo siti rahma normal terverifikasi',
    },
];

test('filterHistory returns records matching the inclusive date range and every selected filter', () => {
    const result = filterHistory(historyRecords, {
        startDate: '2026-10-08',
        endDate: '2026-10-09',
        machine: 'cabin',
        status: 'pending',
        officer: 'andi',
        query: 'TERMINAL 2',
    });

    assert.deepEqual(result.map((record) => record.id), ['inspection-2']);
});

test('filterHistory treats empty filters as all records', () => {
    const result = filterHistory(historyRecords, {
        startDate: '',
        endDate: '',
        machine: '',
        status: '',
        officer: '',
        query: '',
    });

    assert.deepEqual(result.map((record) => record.id), [
        'inspection-1',
        'inspection-2',
        'inspection-3',
    ]);
});

test('filterHistory returns no records when the date range is reversed', () => {
    const result = filterHistory(historyRecords, {
        startDate: '2026-10-10',
        endDate: '2026-10-01',
        machine: '',
        status: '',
        officer: '',
        query: '',
    });

    assert.deepEqual(result, []);
});

test('summarizeHistory counts visible records by result and verification status', () => {
    assert.deepEqual(summarizeHistory(historyRecords), {
        total: 3,
        normal: 2,
        finding: 1,
        pending: 1,
    });
});

test('findHistoryById returns the selected record and null for an unknown id', () => {
    assert.equal(findHistoryById(historyRecords, 'inspection-2')?.machineName, 'X-Ray Cabin');
    assert.equal(findHistoryById(historyRecords, 'missing-record'), null);
});

test('createPrintMessage identifies the inspection being printed', () => {
    assert.equal(
        createPrintMessage(historyRecords[0]),
        'Dokumen pemeriksaan X-Ray Baggage HI-Scan 100100T sedang disiapkan.',
    );
});

test('getTrappedFocusIndex wraps keyboard focus inside the detail sheet', () => {
    assert.equal(getTrappedFocusIndex(2, 3, false), 0);
    assert.equal(getTrappedFocusIndex(0, 3, true), 2);
    assert.equal(getTrappedFocusIndex(1, 3, false), 2);
    assert.equal(getTrappedFocusIndex(-1, 0, false), -1);
});
