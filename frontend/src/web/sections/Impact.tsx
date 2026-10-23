import type { HomeItem, HomeSectionOf } from '@shared/types';
import { parseStat } from '@shared/lib/menu';
import { SafeImg } from '../components/SafeImg';
import { SectionHeading } from '../components/SectionHeading';
import { cx } from '../components/ui';
import { useCountUp, useInView } from '../lib/motion';

/** Big numbers with a short explanation on a dark band (Homepage → Impact numbers). */
export function Impact({ section }: { section: HomeSectionOf<'impact'> }) {
  const [ref, shown] = useInView<HTMLDListElement>();
  const items = section.items;

  return (
    <section id="impact" className="relative isolate overflow-hidden bg-navy-950 py-16 text-white sm:py-24">
      <SafeImg src={section.image_url ?? undefined} alt="" loading="lazy" className="absolute inset-0 -z-20 size-full object-cover opacity-20" />
      <div className="absolute inset-0 -z-10 bg-gradient-to-r from-navy-950 via-navy-950/90 to-navy-950/60" />
      <div className="bg-lattice absolute inset-0 -z-10 opacity-40 [mask-image:linear-gradient(to_right,black,transparent_70%)]" />
      <div className="absolute -right-40 bottom-0 -z-10 size-[34rem] rounded-full bg-gold-500/15 blur-3xl" />

      <div className="container-page grid items-center gap-12 lg:grid-cols-12">
        <div className="lg:col-span-5">
          <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} dark />
        </div>
        <dl
          ref={ref}
          className={cx('grid gap-px overflow-hidden rounded-2xl bg-white/10 ring-1 ring-white/10 lg:col-span-7', items.length > 1 && 'sm:grid-cols-2')}
        >
          {items.map((item, i) => (
            <Figure key={item.id} item={item} start={shown} delay={i * 140} />
          ))}
        </dl>
      </div>
    </section>
  );
}

function Figure({ item, start, delay }: { item: HomeItem; start: boolean; delay: number }) {
  const parts = parseStat(item.value ?? '');
  const value = useCountUp(parts.number, start, 1800 + delay);

  return (
    <div className="flex flex-col bg-navy-950/70 p-7 backdrop-blur-sm transition duration-500 hover:bg-navy-900/80 sm:p-8">
      <dd className="order-1 font-display text-5xl font-semibold leading-none [font-variant-numeric:lining-nums_tabular-nums] sm:text-6xl">
        <span className="text-gilded">
          {parts.prefix}
          {parts.number === null ? parts.suffix : (value ?? 0).toFixed(parts.decimals)}
          {parts.number !== null && parts.suffix}
        </span>
      </dd>
      <dt className="order-2 mt-4 text-[12px] font-bold uppercase tracking-[0.22em] text-gold-200">{item.title}</dt>
      {item.description && <dd className="order-3 mt-2 text-sm leading-6 text-navy-200">{item.description}</dd>}
    </div>
  );
}
