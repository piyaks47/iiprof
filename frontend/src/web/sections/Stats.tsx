import type { HomeSection, Stat } from '@shared/types';
import { parseStat } from '@shared/lib/menu';
import { cx } from '../components/ui';
import { useCountUp, useInView } from '../lib/motion';

export function Stats({ section, overlap }: { section: Extract<HomeSection, { key: 'stats' }>; overlap: boolean }) {
  const [ref, shown] = useInView<HTMLDivElement>();
  const cols = section.items.length;

  return (
    <section id="stats" className={cx('relative z-20', overlap ? '-mt-14 sm:-mt-16' : 'py-10')}>
      <div className="container-page">
        <div
          ref={ref}
          data-reveal=""
          data-shown={shown ? '' : undefined}
          className="rounded-2xl bg-white px-5 py-6 shadow-[0_40px_90px_-40px_rgb(7_15_32/0.45)] ring-1 ring-navy-900/5 sm:px-8 sm:py-7"
        >
          {(section.eyebrow || section.title) && (
            <div className="mb-5 flex flex-col items-center gap-2 text-center sm:flex-row sm:justify-between sm:text-left">
              {section.eyebrow && <p className="eyebrow">{section.eyebrow}</p>}
              {section.title && <p className="font-display text-xl italic text-navy-800">{section.title}</p>}
            </div>
          )}
          <dl className={cx('grid grid-cols-2 gap-y-6', cols >= 4 ? 'lg:grid-cols-4' : cols === 3 ? 'lg:grid-cols-3' : '')}>
            {section.items.map((stat, i) => (
              <StatItem key={stat.id} stat={stat} start={shown} delay={i * 120} first={i === 0} />
            ))}
          </dl>
        </div>
      </div>
    </section>
  );
}

function StatItem({ stat, start, delay, first }: { stat: Stat; start: boolean; delay: number; first: boolean }) {
  const parts = parseStat(stat.value);
  const value = useCountUp(parts.number, start, 1800 + delay);

  return (
    <div className={cx('flex flex-col items-center px-4 text-center lg:border-l lg:border-navy-900/8', first && 'lg:border-l-0')}>
      <dt className="order-2 mt-1.5 text-[11px] font-semibold uppercase tracking-[0.2em] text-navy-500">{stat.label}</dt>
      <dd className="order-1 font-display text-4xl font-semibold tabular-nums text-navy-950 sm:text-[2.75rem]">
        {parts.prefix}
        {parts.number === null ? parts.suffix : (value ?? 0).toFixed(parts.decimals)}
        {parts.number !== null && <span className="text-gilded">{parts.suffix}</span>}
      </dd>
    </div>
  );
}
