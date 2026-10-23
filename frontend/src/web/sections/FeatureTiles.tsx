import type { HomeSectionOf } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { cx } from '../components/ui';
import { Backdrop } from './Backdrop';

/** Large photo tiles, one per area of IIP (Homepage → Feature tiles). */
export function FeatureTiles({ section }: { section: HomeSectionOf<'pillars'> }) {
  const items = section.items;

  return (
    <section id="pillars" className="relative bg-white py-16 sm:py-20">
      <div className="container-page">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} align="center" />

        <ul className={cx('mt-12 grid gap-5 sm:grid-cols-2', items.length % 3 === 0 || items.length > 4 ? 'lg:grid-cols-3' : items.length === 4 ? 'lg:grid-cols-4' : '')}>
          {items.map((tile, i) => (
            <Reveal as="li" key={tile.id} delay={(i % 3) * 110} className="h-full">
              <SmartLink
                href={tile.button_url}
                className="group relative isolate flex aspect-[4/3] h-full min-h-72 w-full flex-col text-left justify-end overflow-hidden rounded-2xl bg-navy-950 p-6 text-white shadow-[0_30px_60px_-36px_rgb(7_15_32/0.6)] ring-1 ring-navy-900/10 transition duration-500 ease-out-expo hover:-translate-y-1.5 hover:shadow-[0_44px_80px_-36px_rgb(7_15_32/0.65)] sm:p-7"
              >
                <SafeImg src={tile.image_url ?? undefined} alt="" loading="lazy" fallback={<Backdrop variant={i} rings={false} className="-z-20" />}
                  className="absolute inset-0 -z-20 size-full object-cover transition duration-[1400ms] ease-out-expo group-hover:scale-[1.07]" />
                <span className="absolute inset-0 -z-10 bg-gradient-to-t from-navy-950 via-navy-950/60 to-navy-950/5 transition duration-500 group-hover:via-navy-950/70" />

                {tile.icon && (
                  <span className="absolute left-6 top-6 flex size-11 items-center justify-center rounded-full bg-white/10 text-gold-300 ring-1 ring-white/25 backdrop-blur-md transition duration-500 group-hover:bg-gold-400 group-hover:text-navy-950 sm:left-7 sm:top-7">
                    <Icon name={tile.icon} className="size-5" strokeWidth={1.75} />
                  </span>
                )}
                <span className="absolute right-6 top-6 font-display text-sm text-white/50 sm:right-7 sm:top-7">{String(i + 1).padStart(2, '0')}</span>

                {tile.subtitle && <span className="text-[11px] font-bold uppercase tracking-[0.22em] text-gold-300">{tile.subtitle}</span>}
                <span className="mt-2 font-display text-3xl font-semibold leading-tight">{tile.title}</span>
                {tile.description && (
                  <span className="mt-2 line-clamp-2 max-w-md text-sm leading-6 text-navy-100/90 sm:line-clamp-3">{tile.description}</span>
                )}
                {tile.button_url && (
                  <span className="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-gold-200">
                    {tile.button_text || 'Explore'}
                    <Icon name="arrow-right" className="size-4 transition duration-300 group-hover:translate-x-1.5" strokeWidth={2} />
                  </span>
                )}
                <span className="absolute inset-x-0 bottom-0 h-0.5 origin-left scale-x-0 bg-gradient-to-r from-gold-300 via-gold-500 to-transparent transition-transform duration-700 ease-out-expo group-hover:scale-x-100" />
              </SmartLink>
            </Reveal>
          ))}
        </ul>
      </div>
    </section>
  );
}
