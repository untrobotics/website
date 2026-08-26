'use strict';

// Deterministic config for the pure helpers, set BEFORE requiring the module
// (config.js freezes from process.env at load).
process.env.ALLOWED_EMAIL_DOMAINS = 'unt.edu,my.unt.edu';
process.env.CODE_LENGTH = '6';
process.env.HASH_SALT = 'unit-test-secret';

// Stub the side-effectful deps so requiring verification stays a pure-logic test
// (no DB pool, no outbound email, no audit writes).
jest.mock('../src/db', () => ({ pool: { query: jest.fn() } }));
jest.mock('../src/lib/email', () => ({ sendVerificationCode: jest.fn() }));
jest.mock('../src/lib/auditlog', () => ({ logAttempt: jest.fn() }));

const {
  normalizeEmail,
  looksLikeEmail,
  isAllowedDomain,
  generateCode,
  hashCode,
  hashesEqual,
} = require('../src/lib/verification');

describe('normalizeEmail', () => {
  test('lowercases and trims', () => {
    expect(normalizeEmail('  Foo.Bar@My.UNT.edu ')).toBe('foo.bar@my.unt.edu');
  });
  test('straightens a curly apostrophe (phone autocorrect)', () => {
    expect(normalizeEmail('ta’kiyah@my.unt.edu')).toBe("ta'kiyah@my.unt.edu");
  });
  test('strips zero-width characters', () => {
    expect(normalizeEmail('a​b@unt.edu')).toBe('ab@unt.edu');
  });
  test('handles null/undefined without throwing', () => {
    expect(normalizeEmail(null)).toBe('');
    expect(normalizeEmail(undefined)).toBe('');
  });
});

describe('looksLikeEmail', () => {
  test.each(['a@b.co', "o'brien@my.unt.edu", 'a.b+c@unt.edu'])('accepts %s', (e) => {
    expect(looksLikeEmail(e)).toBe(true);
  });
  test.each(['a@b', 'no-at-sign', 'a b@c.co', 'a’b@c.co', ''])('rejects %p', (e) => {
    expect(looksLikeEmail(e)).toBe(false);
  });
});

describe('isAllowedDomain', () => {
  test.each(['x@unt.edu', 'x@my.unt.edu', 'x@UNT.EDU'])('allows %s', (e) => {
    expect(isAllowedDomain(e)).toBe(true);
  });
  test('rejects a look-alike domain (not a substring match)', () => {
    expect(isAllowedDomain('x@notunt.edu')).toBe(false);
    expect(isAllowedDomain('x@unt.edu.evil.com')).toBe(false);
  });
  test('rejects input with no @', () => {
    expect(isAllowedDomain('nope')).toBe(false);
  });
});

describe('generateCode', () => {
  test('is a zero-padded numeric string of the configured length', () => {
    for (let i = 0; i < 50; i++) {
      const code = generateCode();
      expect(code).toMatch(/^\d{6}$/);
      expect(code).toHaveLength(6);
    }
  });
});

describe('hashCode / hashesEqual', () => {
  test('hashCode is a deterministic 64-char hex digest', () => {
    const a = hashCode('1234', 'user-1');
    expect(a).toMatch(/^[0-9a-f]{64}$/);
    expect(hashCode('1234', 'user-1')).toBe(a);
  });
  test('binds to the discord id (same code, different user -> different hash)', () => {
    expect(hashCode('1234', 'user-1')).not.toBe(hashCode('1234', 'user-2'));
  });
  test('hashesEqual: true for identical digests', () => {
    const a = hashCode('1234', 'user-1');
    expect(hashesEqual(a, hashCode('1234', 'user-1'))).toBe(true);
  });
  test('hashesEqual: false for different / mismatched / non-string input', () => {
    expect(hashesEqual(hashCode('1', 'u'), hashCode('2', 'u'))).toBe(false);
    expect(hashesEqual('abc', 'abcd')).toBe(false);
    expect(hashesEqual(null, 'abc')).toBe(false);
  });
});
