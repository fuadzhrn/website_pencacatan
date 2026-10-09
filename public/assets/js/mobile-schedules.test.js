import assert from 'node:assert/strict';
import test from 'node:test';

import {
    createStartMessage,
    filterSchedules,
    findScheduleById,
    getTrappedFocusIndex,
    summarizeSchedules,
} from './mobile-schedules.js';

const schedules = [
    {
        id: 'schedule-1',
        month: '10',
        year: '2026',
        machine: 'baggage',
        status: 'scheduled',
        machineName: 'X-Ray Baggage HI-Scan 100100T',
    },
    {
        id: 'schedule-2',
        month: '10',
        year: '2026',
        machine: 'cabin',
        status: 'completed',
        machineName: 'X-Ray Cabin',
    },
    {
        id: 'schedule-3',
        month: '10',
        year: '2026',
        machine: 'cargo',
        status: 'overdue',
        machineName: 'X-Ray Cargo',
    },
];

test('filterSchedules returns schedules matching every selected filter', () => {
    const result = filterSchedules(schedules, {
        month: '10',
        year: '2026',
        machine: 'cargo',
        status: 'overdue',
    });

    assert.deepEqual(result.map((schedule) => schedule.id), ['schedule-3']);
});

test('filterSchedules treats an empty value as all options', () => {
    const result = filterSchedules(schedules, {
        month: '',
        year: '',
        machine: '',
        status: '',
    });

    assert.deepEqual(result.map((schedule) => schedule.id), [
        'schedule-1',
        'schedule-2',
        'schedule-3',
    ]);
});

test('summarizeSchedules counts visible schedules by operational status', () => {
    const result = summarizeSchedules(schedules);

    assert.deepEqual(result, {
        total: 3,
        scheduled: 1,
        completed: 1,
        overdue: 1,
    });
});

test('findScheduleById returns the selected schedule and null for an unknown id', () => {
    assert.equal(findScheduleById(schedules, 'schedule-2')?.machineName, 'X-Ray Cabin');
    assert.equal(findScheduleById(schedules, 'missing-schedule'), null);
});

test('createStartMessage identifies the machine being started', () => {
    assert.equal(
        createStartMessage(schedules[0]),
        'Pemeriksaan X-Ray Baggage HI-Scan 100100T siap dimulai.',
    );
});

test('getTrappedFocusIndex wraps keyboard focus inside the detail sheet', () => {
    assert.equal(getTrappedFocusIndex(2, 3, false), 0);
    assert.equal(getTrappedFocusIndex(0, 3, true), 2);
    assert.equal(getTrappedFocusIndex(1, 3, false), 2);
});
