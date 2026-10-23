import { Link } from 'react-router';
import type { EventSummary } from '@shared/types';
import { Icon } from '../components/Icon';
import { cx } from '../components/ui';
import { Backdrop } from './Backdrop';
import { SafeImg } from '../components/SafeImg';

export function formatDate(iso: string | null, options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'long', year: 'numeric' }) {
  if (!iso) return null;
  const date = new Date(`${iso}T00:00:00`);
  return Number.isNaN(date.getTime()) ? null : date.toLocaleDateString('en-GB', options);
}

export function EventCard({ event, index = 0, className, featured = false }: { event: EventSummary; index?: number; className?: string; featured?: boolean }) {
  const day = formatDate(event.starts_on, { day: '2-digit' });
  const month = formatDate(event.starts_on, { month: 'short' });

  return (
    <Link
      to={event.path}
      className={cx(
        'group flex h-full overflow-hidden rounded-2xl bg-white',
        featured ? 'flex-col md:flex-row' : 'flex-col',
        'shadow-[0_20px_50px_-30px_rgb(7_15_32/0.35)] ring-1 ring-navy-900/5 transition duration-500 ease-out-expo hover:-translate-y-1.5 hover:shadow-[0_40px_80px_-30px_rgb(7_15_32/0.45)]',
        className,
      )}
    >
      <div className={cx('relative overflow-hidden', featured ? 'aspect-[16/10] md:aspect-auto md:min-h-[20rem] md:w-1/2' : 'aspect-[16/10]')}>
        <SafeImg
          src={event.image_url ?? undefined}
          alt=""
          loading="lazy"
          className="absolute inset-0 size-full object-cover transition duration-[1200ms] ease-out-expo group-hover:scale-110"
          fallback={
            <div className="absolute inset-0 transition duration-[1200ms] ease-out-expo group-hover:scale-110">
              <Backdrop variant={index + 1} rings={false} />
              <Icon name="calendar" className="absolute left-1/2 top-1/2 size-16 -translate-x-1/2 -translate-y-1/2 text-gold-300/40" strokeWidth={1} />
            </div>
          }
        />
        <div className="absolute inset-0 bg-gradient-to-t from-navy-950/60 via-transparent to-transparent opacity-0 transition duration-500 group-hover:opacity-100" />
        {day && (
          <div className="absolute left-4 top-4 flex flex-col items-center rounded-xl bg-white/95 px-3 py-2 text-center shadow-lg backdrop-blur">
            <span className="font-display text-2xl font-semibold leading-none text-navy-950">{day}</span>
            <span className="mt-1 text-[10px] font-bold uppercase tracking-[0.2em] text-gold-600">{month}</span>
          </div>
        )}
      </div>
      <div className={cx('flex flex-1 flex-col', featured ? 'p-8 sm:p-10 md:justify-center' : 'p-5 sm:p-6')}>
        {event.location && (
          <p className="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-navy-400">
            <Icon name="map-pin" className="size-3.5" /> {event.location}
          </p>
        )}
        {featured && <p className="eyebrow">Featured event</p>}
        <h3 className={cx('mt-2 font-semibold leading-snug text-navy-950 transition group-hover:text-gold-700', featured ? 'mt-4 text-3xl sm:text-4xl' : 'text-xl')}>{event.title}</h3>
        {event.summary && <p className={cx('mt-3 text-navy-600', featured ? 'text-lg leading-8' : 'line-clamp-2 text-[15px] leading-6')}>{event.summary}</p>}
        <span className={cx('inline-flex items-center gap-2 pt-5 text-sm font-semibold text-navy-900', featured ? 'mt-4' : 'mt-auto')}>
          Read more
          <span className="flex size-7 items-center justify-center rounded-full bg-navy-950 text-gold-300 transition duration-500 ease-out-expo group-hover:translate-x-1.5 group-hover:bg-gold-400 group-hover:text-navy-950">
            <Icon name="arrow-right" className="size-3.5" strokeWidth={2} />
          </span>
        </span>
      </div>
    </Link>
  );
}
