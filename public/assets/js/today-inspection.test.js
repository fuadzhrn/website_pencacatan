import assert from 'node:assert/strict';
import test from 'node:test';

import { inspectionProgress, shouldShowFindingFields } from './today-inspection.js';

test('inspectionProgress menghitung seluruh status pemeriksaan', () => {
    assert.deepEqual(
        inspectionProgress(['sesuai', 'tidak_sesuai', 'tidak_dijadwalkan', 'belum_diperiksa']),
        {
            total: 4,
            completed: 3,
            conform: 1,
            nonconform: 1,
            remaining: 1,
            percentage: 75,
        },
    );
});

test('inspectionProgress aman untuk checklist kosong', () => {
    assert.deepEqual(inspectionProgress([]), {
        total: 0,
        completed: 0,
        conform: 0,
        nonconform: 0,
        remaining: 0,
        percentage: 0,
    });
});

test('form temuan hanya tampil untuk hasil tidak sesuai', () => {
    assert.equal(shouldShowFindingFields('tidak_sesuai'), true);
    assert.equal(shouldShowFindingFields('sesuai'), false);
    assert.equal(shouldShowFindingFields('belum_diperiksa'), false);
});
