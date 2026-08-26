'use strict';

const { matchKeyword, reactionsForChannel } = require('../src/lib/autoresponses');

describe('matchKeyword', () => {
  test('matches a verification keyword, case-insensitively', () => {
    expect(matchKeyword('How do I verify?')).toMatch(/verify/i);
    expect(matchKeyword('VERIFY ME')).toBeTruthy();
    expect(matchKeyword('can someone get verified here')).toBeTruthy();
  });

  test('returns null when no trigger appears', () => {
    expect(matchKeyword('hello world')).toBeNull();
    expect(matchKeyword('')).toBeNull();
  });
});

describe('reactionsForChannel', () => {
  test('returns an empty array for an unconfigured channel', () => {
    expect(reactionsForChannel('000000000000000000')).toEqual([]);
  });
});
