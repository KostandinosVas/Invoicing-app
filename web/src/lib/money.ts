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

  // Δεκαδικός διαχωριστής: ό,τι εμφανίζεται τελευταίο (τελεία ή κόμμα).
  const lastComma = trimmed.lastIndexOf(',');
  const lastDot = trimmed.lastIndexOf('.');
  const decimalSeparator = lastComma > lastDot ? ',' : '.';

  const parts = trimmed.split(decimalSeparator);

  if (parts.length > 2) return null;

  const integerPart = parts[0].replace(/[.,\s]/g, '');
  const decimalPart = parts[1] ?? '';

  if (!/^\d+$/.test(integerPart)) return null;
  if (decimalPart !== '' && !/^\d{1,2}$/.test(decimalPart)) return null;

  const cents = Number(integerPart) * 100 + Number(decimalPart.padEnd(2, '0'));

  return Number.isFinite(cents) ? cents : null;
}