import { describe, expect, it } from 'vitest';
import { formatCents, parseEuros } from './money';

describe('parseEuros', () => {
  it('δέχεται ελληνική γραφή', () => {
    expect(parseEuros('12,50')).toBe(1250);
    expect(parseEuros('1.234,56')).toBe(123456);
  });

  it('δέχεται αγγλική γραφή', () => {
    expect(parseEuros('12.50')).toBe(1250);
    expect(parseEuros('1,234.56')).toBe(123456);
  });

  it('δέχεται ακέραιους χωρίς διαχωριστή', () => {
    expect(parseEuros('50')).toBe(5000);
    expect(parseEuros('0')).toBe(0);
  });

  it('ερμηνεύει τρία ψηφία μετά από μοναδικό διαχωριστή ως χιλιάδες', () => {
    expect(parseEuros('1.000')).toBe(100000);
    expect(parseEuros('12,345')).toBe(1234500);
  });

  it('συμπληρώνει ένα δεκαδικό ψηφίο', () => {
    expect(parseEuros('12,5')).toBe(1250);
  });

  it('δεν χάνει ακρίβεια σε τιμές που σπάνε σε float', () => {
    expect(parseEuros('12,34')).toBe(1234);
    expect(parseEuros('0,29')).toBe(29);
    expect(parseEuros('1,10')).toBe(110);
  });

  it('απορρίπτει μη έγκυρη είσοδο', () => {
    expect(parseEuros('')).toBeNull();
    expect(parseEuros('abc')).toBeNull();
    expect(parseEuros('-5')).toBeNull();
    expect(parseEuros('12,3456')).toBeNull();
    expect(parseEuros('1.234,567')).toBeNull();
    expect(parseEuros('12,34,56')).toBeNull();
  });
});

describe('formatCents', () => {
  it('μορφοποιεί κατά ελληνική σύμβαση', () => {
    expect(formatCents(1250)).toContain('12,50');
    expect(formatCents(123456)).toContain('1.234,56');
  });

  it('εμφανίζει μηδενικό ποσό', () => {
    expect(formatCents(0)).toContain('0,00');
  });
});