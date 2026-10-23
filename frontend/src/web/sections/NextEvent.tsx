import { useEffect, useState } from 'react';
import { Link } from 'react-router';
import type { HomeSectionOf } from '@shared/types';
import { useEnquiry } from '../components/Enquiry';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { SmartLink } from '../components/SmartLink';
import { button, cx } from '../components/ui';
import { icsFor } from '../lib/calendar';
import { Backdrop } from './Backdrop';
import { formatDate } from './EventCard';

/** The next upcoming event: date tag, live countdown, register and add-to-calendar (Homepage → Events). */
export function NextEvent({ section }: { section: HomeSectionOf<'next_event'> }) {
  const event = section.items[0];
  const enquiry = useEnquiry();
  const left = useCountdown(event?.starts_on ?? null);
  if (!event) return null;

  const date = formatDate(event.starts_on, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  const day = formatDate(event.starts_on, { day: '2-digit' });
  const month = formatDate(event.starts_on, { month: 'short' });
  const ics = icsFor({ title: event.title, starts_on: event.starts_on, summary: event.summary, location: event.location, url: `${window.location.origin}${event.path}` });
  const cta = event.button_text || 'Register now';

  return (
    <section id="next_event" className="relative py-12 sm:py-16">
      <div className="container-page">
        <Reveal className="relative isolate grid overflow-hidden rounded-[1.75rem] bg-navy-950 text-white shadow-[0_50px_100px_-50px_rgb(7_15_32/0.75)] ring-1 ring-navy-900/10 lg:grid-cols-12">
          <div className="relative min-h-64 overflow-hidden lg:col-span-5">
            <SafeImg src={event.image_url ?? undefined} alt="" className="absolute inset-0 size-full animate-ken-burns object-cover" fallback={<Backdrop variant={1} />} />
            <div className="absolute inset-0 bg-gradient-to-t from-navy-950/70 via-transparent to-transparent lg:bg-gradient-to-r lg:from-transparent lg:via-transparent lg:to-navy-950" />
            {day && (
              <div className="absolute left-6 top-6 flex flex-col items-center rounded-2xl bg-white px-4 py-3 text-center shadow-xl">
                <span className="font-display text-4xl font-semibold leading-none text-navy-950 [font-variant-numeric:lining-nums]">{day}</span>
                <span className="mt-1 text-[11px] font-bold uppercase tracking-[0.22em] text-gold-600">{month}</span>
              </div>
            )}
          </div>

          <div className="relative p-7 sm:p-10 lg:col-span-7">
            <div className="bg-lattice absolute inset-0 -z-10 opacity-40 [mask-image:linear-gradient(to_left,black,transparent_80%)]" />
            <div className="absolute -right-20 -top-24 -z-10 size-72 rounded-full bg-gold-500/20 blur-3xl" />
            <div className="flex flex-wrap items-center justify-between gap-3">
              <p className="eyebrow text-gold-300">{section.eyebrow || 'Next event'}</p>
              {section.button_text && (
                <SmartLink href={section.button_url} className="inline-flex items-center gap-1.5 text-sm font-semibold text-gold-200 hover:text-white">
                  {section.button_text} <Icon name="arrow-right" className="size-4" strokeWidth={2} />
                </SmartLink>
              )}
            </div>
            <h2 className="mt-4 text-3xl font-semibold leading-tight sm:text-4xl">
              <Link to={event.path} className="transition hover:text-gold-200">{event.title}</Link>
            </h2>
            {event.summary && <p className="mt-3 max-w-2xl text-[15px] leading-7 text-navy-200 sm:text-base">{event.summary}</p>}

            <div className="mt-5 flex flex-wrap gap-2 text-sm">
              {date && <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 ring-1 ring-white/15"><Icon name="calendar" className="size-4 text-gold-300" />{date}</span>}
              {event.location && <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-3.5 py-1.5 ring-1 ring-white/15"><Icon name="map-pin" className="size-4 text-gold-300" />{event.location}</span>}
            </div>

            {left && (
              <dl className="mt-7 grid max-w-md grid-cols-4 gap-2 sm:gap-3" aria-label="Time left">
                {([['days', left.days], ['hours', left.hours], ['min', left.minutes], ['sec', left.seconds]] as const).map(([label, value]) => (
                  <div key={label} className="flex flex-col items-center rounded-xl bg-white/[0.06] py-3 ring-1 ring-white/10">
                    <dd className="font-display text-3xl font-semibold tabular-nums leading-none text-gilded [font-variant-numeric:lining-nums_tabular-nums] sm:text-4xl">{String(value).padStart(2, '0')}</dd>
                    <dt className="mt-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-navy-300">{label}</dt>
                  </div>
                ))}
              </dl>
            )}

            <div className="mt-8 flex flex-wrap gap-3">
              {!event.button_url || event.button_url === '#enquiry' ? (
                <button type="button" className={button.gold} onClick={() => enquiry.open(`Event: ${event.title}`)}>
                  {cta} <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
                </button>
              ) : (
                <SmartLink href={event.button_url} className={button.gold}>{cta} <Icon name="arrow-right" className="size-4" strokeWidth={2} /></SmartLink>
              )}
              {ics && (
                <a href={ics} download={`${event.slug}.ics`} className={cx(button.ghostLight)}>
                  <Icon name="calendar" className="size-4" /> Add to calendar
                </a>
              )}
              <Link to={event.path} className={button.ghostLight}>Details</Link>
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  );
}

/** Days / hours / minutes / seconds until the start of a YYYY-MM-DD date (local time), ticking every second. */
function useCountdown(date: string | null) {
  const [now, setNow] = useState(() => Date.now());
  useEffect(() => {
    const id = window.setInterval(() => setNow(Date.now()), 1000);
    return () => window.clearInterval(id);
  }, []);
  if (!date) return null;
  const ms = new Date(`${date}T00:00:00`).getTime() - now;
  if (ms <= 0) return null;
  const s = Math.floor(ms / 1000);
  return { days: Math.floor(s / 86400), hours: Math.floor((s % 86400) / 3600), minutes: Math.floor((s % 3600) / 60), seconds: s % 60 };
}
