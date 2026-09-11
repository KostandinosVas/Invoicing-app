const formatter = new Intl.NumberFormat('el-GR', {
  style: 'currency',
  currency: 'EUR',
});

export function formatCents(cents: number): string {
  return formatter.format(cents / 100);
}

export function parseEuros(input: string): number | null {
  const trimmed = input.trim();

  if (trimmed === '') return null;

  // Μόνο ψηφία και διαχωριστές.
  if (!/^[\d.,]+$/.test(trimmed)) return null;

  // Λανθασμένη ομαδοποίηση: διαχωριστής, 1-2 ψηφία, ξανά διαχωριστής.
  if (/[.,]\d{1,2}[.,]/.test(trimmed)) return null;

  const lastComma = trimmed.lastIndexOf(',');
  const lastDot = trimmed.lastIndexOf('.');
  const lastSeparatorIndex = Math.max(lastComma, lastDot);

  // Χωρίς διαχωριστή: σκέτος ακέραιος.
  if (lastSeparatorIndex === -1) {
    return Number(trimmed) * 100;
  }

  const after = trimmed.slice(lastSeparatorIndex + 1);
  const before = trimmed.slice(0, lastSeparatorIndex);

  // Τρία ψηφία μετά από μοναδικό διαχωριστή: χιλιάδες, όχι δεκαδικά.
  const isThousandsGrouping =
    after.length === 3 && !before.includes(',') && !before.includes('.');

  const integerPart = isThousandsGrouping
    ? (before + after).replace(/[.,]/g, '')
    : before.replace(/[.,]/g, '');

  const decimalPart = isThousandsGrouping ? '' : after;

  if (!/^\d+$/.test(integerPart)) return null;
  if (decimalPart !== '' && !/^\d{1,2}$/.test(decimalPart)) return null;

  return Number(integerPart) * 100 + Number(decimalPart.padEnd(2, '0'));
}

/** Λεπτά σε μορφή κατάλληλη για input πεδίο: "1250" → "12,50" */
export function formatEurosInput(cents: number): string {
  const euros = Math.floor(cents / 100);
  const remainder = Math.abs(cents % 100);

  return `${euros},${String(remainder).padStart(2, '0')}`;
}