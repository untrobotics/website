'use strict';

const { parseDuration, humanDuration } = require('../src/lib/reminders');

describe('parseDuration', () => {
  test.each([
    ['90s', 90],
    ['30m', 1800],
    ['2h', 7200],
    ['1d', 86400],
    ['1h30m', 5400],
    ['1 week', 604800],
    ['1w2d', 604800 + 2 * 86400],
  ])('parses %s -> %i seconds', (input, expected) => {
    expect(parseDuration(input)).toBe(expected);
  });

  test.each(['', 'junk', '5', null, undefined])('returns null for unparseable %p', (input) => {
    expect(parseDuration(input)).toBeNull();
  });

  test('is additive across repeated units', () => {
    expect(parseDuration('1h 1h')).toBe(7200);
  });
});

describe('humanDuration', () => {
  test.each([
    [45, '45 seconds'],
    [60, '1 minute'],
    [90, '1 minute'], // seconds are only shown when there are no larger units
    [3600, '1 hour'],
    [5400, '1 hour 30 minutes'],
    [86400, '1 day'],
    [90000, '1 day 1 hour'],
  ])('formats %i seconds -> %s', (input, expected) => {
    expect(humanDuration(input)).toBe(expected);
  });

  test('caps at two units (drops the minutes)', () => {
    // 1 day 1 hour 1 minute -> only the two largest units
    expect(humanDuration(86400 + 3600 + 60)).toBe('1 day 1 hour');
  });

  test('round-trips with parseDuration for clean values', () => {
    expect(humanDuration(parseDuration('2h'))).toBe('2 hours');
  });
});
