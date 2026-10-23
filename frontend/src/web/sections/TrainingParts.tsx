import { dayMonth } from '@shared/lib/format';
import type { TrainingBatch, TrainingProgrammeSummary } from '@shared/types';
import { Link } from 'react-router';
import { useEnquiry } from '../components/Enquiry';
import { Icon } from '../components/Icon';
import { SafeImg } from '../components/SafeImg';
import { SmartLink } from '../components/SmartLink';
import { cx } from '../components/ui';
import { Backdrop } from './Backdrop';

/** Card of a training programme, linking to its page (homepage block, /training). */
export function ProgrammeCard({ programme, index = 0 }: { programme: TrainingProgrammeSummary; index?: number }) {
  const meta = [programme.duration, programme.format].filter(Boolean).join(' · ');

  return (
    <Link
      to={programme.path}
      className="group flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-[0_24px_50px_-36px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/5 transition duration-500 ease-out-expo hover:-translate-y-1.5 hover:shadow-[0_40px_70px_-40px_rgb(7_15_32/0.55)]"
    >
      <div className="relative aspect-[16/9] overflow-hidden bg-navy-950">
        <SafeImg src={programme.image_url ?? undefined} alt="" loading="lazy" fallback={<Backdrop variant={index} rings={false} />}
          className="size-full object-cover transition duration-[1400ms] ease-out-expo group-hover:scale-105" />
        <span className="absolute inset-0 bg-gradient-to-t from-navy-950/60 to-transparent" />
        <span className="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-navy-900 shadow backdrop-blur">{programme.type_label}</span>
        {programme.is_featured && (
          <span className="absolute right-4 top-4 inline-flex items-center gap-1 rounded-full bg-gold-400 px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-navy-950 shadow">
            <Icon name="star" className="size-3" strokeWidth={2.25} /> Featured
          </span>
        )}
        {programme.next_batch && (
          <span className="absolute bottom-3 left-4 inline-flex items-center gap-1.5 text-xs font-semibold text-white">
            <Icon name="calendar" className="size-3.5 text-gold-300" /> Next batch {programme.next_batch.dates}
          </span>
        )}
      </div>
      <div className="flex flex-1 flex-col p-6">
        {programme.tagline && <p className="text-[11px] font-bold uppercase tracking-[0.18em] text-gold-700">{programme.tagline}</p>}
        <h3 className="mt-2 font-display text-xl font-semibold leading-snug text-navy-950">{programme.title}</h3>
        {programme.summary && <p className="mt-2 line-clamp-3 flex-1 text-[14.5px] leading-6 text-navy-600">{programme.summary}</p>}
        <div className="mt-5 flex items-center justify-between gap-3 border-t border-ivory-200 pt-4 text-[13px]">
          <span className="text-navy-500">{meta || 'Details on the programme page'}</span>
          <span className="inline-flex shrink-0 items-center gap-1.5 font-semibold text-gold-700">
            View details <Icon name="arrow-right" className="size-4 transition duration-300 group-hover:translate-x-1" strokeWidth={2} />
          </span>
        </div>
      </div>
    </Link>
  );
}

/** "Register" for a batch: its own link, or the enquiry form pre-filled with the programme and dates. */
export function RegisterBatch({ batch, title, className, children = 'Register' }: { batch: TrainingBatch; title: string; className?: string; children?: React.ReactNode }) {
  const enquiry = useEnquiry();
  if (batch.register_url) {
    return <SmartLink href={batch.register_url} className={className}>{children}</SmartLink>;
  }
  return (
    <button type="button" className={className} onClick={() => enquiry.open(`Registration: ${title} · ${batch.dates}${batch.batch_code ? ` (${batch.batch_code})` : ''}`)}>
      {children}
    </button>
  );
}

/** Compact list of batches with a date tile (homepage "Scheduled dates", programme page). */
export function BatchList({ batches, dark = false, programmeTitle }: { batches: TrainingBatch[]; dark?: boolean; programmeTitle?: string }) {
  return (
    <ul className={cx('divide-y', dark ? 'divide-white/10' : 'divide-ivory-200')}>
      {batches.map((batch) => {
        const { day, month } = dayMonth(batch.starts_on);
        const title = batch.programme?.title ?? programmeTitle ?? 'Training';
        return (
          <li key={batch.id} className="flex items-center gap-4 py-4">
            <span className={cx('flex size-14 shrink-0 flex-col items-center justify-center rounded-xl text-center ring-1', dark ? 'bg-white/5 ring-white/10' : 'bg-ivory-100 ring-gold-400/30')}>
              <span className={cx('font-display text-xl font-semibold leading-none [font-variant-numeric:lining-nums]', dark ? 'text-gold-300' : 'text-navy-950')}>{day}</span>
              <span className={cx('mt-1 text-[10px] font-bold uppercase tracking-[0.18em]', dark ? 'text-navy-200' : 'text-gold-700')}>{month}</span>
            </span>
            <div className="min-w-0 flex-1">
              {batch.programme ? (
                <Link to={batch.programme.path} className={cx('line-clamp-2 text-[15px] font-semibold leading-snug transition', dark ? 'text-white hover:text-gold-200' : 'text-navy-950 hover:text-gold-700')}>{title}</Link>
              ) : (
                <p className={cx('text-[15px] font-semibold', dark ? 'text-white' : 'text-navy-950')}>{batch.dates}</p>
              )}
              <p className={cx('mt-1 text-xs', dark ? 'text-navy-200' : 'text-navy-500')}>
                {[batch.programme ? batch.dates : null, batch.format_label, batch.location].filter(Boolean).join(' · ')}
              </p>
              {batch.schedule && <p className={cx('mt-0.5 text-xs', dark ? 'text-navy-300' : 'text-navy-400')}>{batch.schedule}</p>}
            </div>
            <RegisterBatch batch={batch} title={title}
              className={cx('shrink-0 rounded-full px-4 py-2 text-xs font-semibold transition', dark ? 'bg-gold-400 text-navy-950 hover:bg-gold-300' : 'bg-navy-950 text-white hover:bg-navy-800')} />
          </li>
        );
      })}
    </ul>
  );
}
