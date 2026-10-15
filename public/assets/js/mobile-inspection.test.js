import assert from 'node:assert/strict';
import test from 'node:test';

import { checklistStatusLabel } from './mobile-inspection.js';
import { normalizeMobileReportMode } from './mobile-report-database.js';

test('checklistStatusLabel menandai checklist yang belum lengkap', () => {
    assert.deepEqual(checklistStatusLabel(['sesuai', 'belum_diperiksa']), { label: 'Belum Lengkap', modifier: 'pending' });
    assert.deepEqual(checklistStatusLabel([]), { label: 'Belum Lengkap', modifier: 'pending' });
});

test('checklistStatusLabel membedakan checklist lengkap normal dan yang memiliki temuan', () => {
    assert.deepEqual(checklistStatusLabel(['sesuai', 'sesuai']), { label: 'Lengkap', modifier: 'complete' });
    assert.deepEqual(checklistStatusLabel(['sesuai', 'tidak_sesuai']), { label: 'Ada Temuan', modifier: 'finding' });
});

test('normalizeMobileReportMode hanya menerima mode laporan yang tersedia', () => {
    assert.equal(normalizeMobileReportMode('table'), 'table');
    assert.equal(normalizeMobileReportMode('detail'), 'detail');
    assert.equal(normalizeMobileReportMode('unknown'), 'summary');
});
