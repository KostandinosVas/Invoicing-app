export type LineInput = {
  quantity: string;
  unitPriceCents: number;
  vatRate: number;
};

export type LineTotals = {
  netAmountCents: number;
  vatAmountCents: number;
  totalCents: number;
};

/**
 * Αντιγράφει τη λογική του App\ValueObjects\Money στο backend:
 * ακέραια λεπτά, στρογγυλοποίηση half-up.
 * Το backend παραμένει η αυθεντία — αυτό είναι πρόβλεψη για άμεση ανάδραση.
 */
export function calculateLine({
  quantity,
  unitPriceCents,
  vatRate,
}: LineInput): LineTotals {
  const qty = parseQuantity(quantity);

  if (qty === null) {
    return { netAmountCents: 0, vatAmountCents: 0, totalCents: 0 };
  }

  const netAmountCents = roundHalfUp(unitPriceCents * qty);
  const vatAmountCents = roundHalfUp((netAmountCents * vatRate) / 100);

  return {
    netAmountCents,
    vatAmountCents,
    totalCents: netAmountCents + vatAmountCents,
  };
}

export function sumTotals(lines: LineTotals[]): LineTotals {
  return lines.reduce<LineTotals>(
    (acc, line) => ({
      netAmountCents: acc.netAmountCents + line.netAmountCents,
      vatAmountCents: acc.vatAmountCents + line.vatAmountCents,
      totalCents: acc.totalCents + line.totalCents,
    }),
    { netAmountCents: 0, vatAmountCents: 0, totalCents: 0 },
  );
}

function roundHalfUp(value: number): number {
  return Math.floor(value + 0.5);
}

function parseQuantity(input: string): number | null {
  const normalised = input.trim().replace(',', '.');

  if (!/^\d+(\.\d{1,3})?$/.test(normalised)) return null;

  const value = Number(normalised);

  return value > 0 ? value : null;
}