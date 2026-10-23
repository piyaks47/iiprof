/** "1500" → "₹1,500"; words such as "Free" or "On request" are kept as they are. */
export function formatFee(value: string | null | undefined): string | null {
  if (value === null || value === undefined || value.trim() === '') return null;
  const n = Number(value.replace(/,/g, ''));
  return Number.isFinite(n) && /^[\d,.\s]+$/.test(value) ? `₹${n.toLocaleString('en-IN')}` : value;
}

/** Day and short month of a YYYY-MM-DD date, for calendar tiles. */
export function dayMonth(date: string): { day: string; month: string; year: string } {
  const d = new Date(`${date}T00:00:00`);
  return {
    day: String(d.getDate()).padStart(2, '0'),
    month: d.toLocaleDateString('en-GB', { month: 'short' }),
    year: String(d.getFullYear()),
  };
}
