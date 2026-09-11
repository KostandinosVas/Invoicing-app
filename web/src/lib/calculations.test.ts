import { describe, expect, it } from 'vitest';
import { calculateLine, sumTotals } from './calculations';

describe('calculateLine', () => {
  it('υπολογίζει γραμμή με ΦΠΑ 24%', () => {
    // Ίδιο παράδειγμα με το backend test: 10 × 50,00 € με 24%
    const result = calculateLine({
      quantity: '10',
      unitPriceCents: 5000,
      vatRate: 24,
    });

    expect(result).toEqual({
      netAmountCents: 50000,
      vatAmountCents: 12000,
      totalCents: 62000,
    });
  });

  it('στρογγυλοποιεί half-up σε κλασματική ποσότητα', () => {
    // 1333 × 2.5 = 3332,5 → 3333
    const result = calculateLine({
      quantity: '2,5',
      unitPriceCents: 1333,
      vatRate: 24,
    });

    expect(result.netAmountCents).toBe(3333);
  });

  it('στρογγυλοποιεί half-up στον ΦΠΑ', () => {
    // 333 × 24% = 79,92 → 80
    const result = calculateLine({
      quantity: '1',
      unitPriceCents: 333,
      vatRate: 24,
    });

    expect(result.vatAmountCents).toBe(80);
  });

  it('χειρίζεται μηδενικό συντελεστή', () => {
    const result = calculateLine({
      quantity: '1',
      unitPriceCents: 10000,
      vatRate: 0,
    });

    expect(result.vatAmountCents).toBe(0);
    expect(result.totalCents).toBe(10000);
  });

  it('δέχεται και τις δύο γραφές ποσότητας', () => {
    const greek = calculateLine({ quantity: '2,5', unitPriceCents: 1000, vatRate: 24 });
    const english = calculateLine({ quantity: '2.5', unitPriceCents: 1000, vatRate: 24 });

    expect(greek).toEqual(english);
  });

  it('επιστρέφει μηδενικά σε μη έγκυρη ποσότητα', () => {
    const result = calculateLine({ quantity: 'abc', unitPriceCents: 1000, vatRate: 24 });

    expect(result).toEqual({
      netAmountCents: 0,
      vatAmountCents: 0,
      totalCents: 0,
    });
  });

  it('απορρίπτει μηδενική ή αρνητική ποσότητα', () => {
    expect(calculateLine({ quantity: '0', unitPriceCents: 1000, vatRate: 24 }).totalCents).toBe(0);
    expect(calculateLine({ quantity: '-2', unitPriceCents: 1000, vatRate: 24 }).totalCents).toBe(0);
  });
});

describe('sumTotals', () => {
  it('αθροίζει γραμμές με διαφορετικούς συντελεστές ΦΠΑ', () => {
    // Ίδιο παράδειγμα με το backend: 500 € @24% + 100 € @6%
    const first = calculateLine({ quantity: '10', unitPriceCents: 5000, vatRate: 24 });
    const second = calculateLine({ quantity: '1', unitPriceCents: 10000, vatRate: 6 });

    expect(sumTotals([first, second])).toEqual({
      netAmountCents: 60000,
      vatAmountCents: 12600,
      totalCents: 72600,
    });
  });

  it('επιστρέφει μηδενικά για κενή λίστα', () => {
    expect(sumTotals([])).toEqual({
      netAmountCents: 0,
      vatAmountCents: 0,
      totalCents: 0,
    });
  });

  it('αθροίζει τα στρογγυλοποιημένα, όχι τα ακατέργαστα', () => {
    // Τρεις γραμμές που στρογγυλοποιούνται ξεχωριστά.
    // Αν αθροίζαμε πριν τη στρογγυλοποίηση, το αποτέλεσμα θα διέφερε.
    const line = calculateLine({ quantity: '1', unitPriceCents: 333, vatRate: 24 });

    expect(sumTotals([line, line, line]).vatAmountCents).toBe(240); // 80 × 3
  });
});