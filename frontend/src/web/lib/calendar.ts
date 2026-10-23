/** A one-event iCalendar file (all-day), for "Add to calendar". */
export function icsFor(event: { title: string; starts_on: string | null; summary?: string | null; location?: string | null; url: string }): string | null {
  if (!event.starts_on) return null;
  const day = event.starts_on.replace(/-/g, '');
  const next = new Date(`${event.starts_on}T00:00:00Z`);
  next.setUTCDate(next.getUTCDate() + 1);
  const end = next.toISOString().slice(0, 10).replace(/-/g, '');
  const esc = (s: string) => s.replace(/\\/g, '\\\\').replace(/[,;]/g, (m) => `\\${m}`).replace(/\n/g, '\\n');
  const stamp = new Date().toISOString().replace(/[-:]/g, '').replace(/\.\d+Z$/, 'Z');
  const lines = [
    'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//IIP//Events//EN', 'BEGIN:VEVENT',
    `UID:${day}-${encodeURIComponent(event.title).slice(0, 40)}@iiprof`, `DTSTAMP:${stamp}`,
    `DTSTART;VALUE=DATE:${day}`, `DTEND;VALUE=DATE:${end}`, `SUMMARY:${esc(event.title)}`,
    event.summary ? `DESCRIPTION:${esc(`${event.summary}\n${event.url}`)}` : `DESCRIPTION:${esc(event.url)}`,
    event.location ? `LOCATION:${esc(event.location)}` : '', `URL:${event.url}`, 'END:VEVENT', 'END:VCALENDAR',
  ].filter(Boolean);
  return `data:text/calendar;charset=utf-8,${encodeURIComponent(lines.join('\r\n'))}`;
}
