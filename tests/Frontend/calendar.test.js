import test from 'node:test';
import assert from 'node:assert/strict';
import { calendarCells } from '../../resources/js/Support/calendar.js';
test('calendar has six Monday-first weeks and selected month dates', () => {
    const cells = calendarCells(2026, 10);
    assert.equal(cells.length, 42);
    assert.equal(cells[0].key, '2026-10-26');
    assert.equal(cells[6].key, '2026-11-01');
    assert.equal(cells.filter(day => day.currentMonth).length, 30);
});
test('calendar preserves leap days and December to January boundaries', () => {
    assert.equal(calendarCells(2028, 1).filter(day => day.currentMonth).length, 29);
    assert.ok(calendarCells(2026, 11).some(day => day.key === '2027-01-01'));
});
