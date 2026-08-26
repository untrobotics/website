'use strict';

const { secondsRemaining, arm, clear } = require('../src/lib/ratelimit');

describe('ratelimit', () => {
  test('an unarmed key has 0 seconds remaining', () => {
    expect(secondsRemaining('never-armed')).toBe(0);
  });

  test('arm() sets a positive cooldown within the requested window', () => {
    arm('k1', 5);
    const r = secondsRemaining('k1');
    expect(r).toBeGreaterThan(0);
    expect(r).toBeLessThanOrEqual(5);
  });

  test('clear() cancels the cooldown', () => {
    arm('k2', 30);
    expect(secondsRemaining('k2')).toBeGreaterThan(0);
    clear('k2');
    expect(secondsRemaining('k2')).toBe(0);
  });
});
