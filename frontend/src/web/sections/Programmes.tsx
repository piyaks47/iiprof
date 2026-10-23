import type { HomeSectionOf } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { button, cx } from '../components/ui';
import { Backdrop } from './Backdrop';

/** Photo cards for courses, admissions and initiatives (Homepage → Programmes). */
export function Programmes({ section }: { section: HomeSectionOf<'programmes'> }) {
  return (
    <section id="programmes" className="relative py-16 sm:py-20">
      <div className="container-page">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} />
          {section.button_text && (
            <Reveal delay={150} className="shrink-0">
              <SmartLink href={section.button_url} className={button.outline}>
                {section.button_text}
                <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
              </SmartLink>
            </Reveal>
          )}
        </div>

        <ul className="scrollbar-none -mx-4 mt-10 flex snap-x snap-mandatory scroll-px-4 gap-5 overflow-x-auto px-4 pb-4 sm:mx-0 sm:grid sm:snap-none sm:grid-cols-2 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-3">
          {section.items.map((item, i) => (
            <Reveal as="li" key={item.id} delay={(i % 3) * 110} className="w-[82%] shrink-0 snap-start sm:w-auto">
              <SmartLink href={item.button_url} className="group flex h-full w-full flex-col overflow-hidden rounded-2xl bg-white text-left shadow-[0_24px_50px_-36px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/5 transition duration-500 ease-out-expo hover:-translate-y-1.5 hover:shadow-[0_40px_70px_-40px_rgb(7_15_32/0.55)]">
                <div className="relative aspect-[16/10] overflow-hidden bg-navy-950">
                  <SafeImg src={item.image_url ?? undefined} alt="" loading="lazy" fallback={<Backdrop variant={i} rings={false} />}
                    className="size-full object-cover transition duration-[1400ms] ease-out-expo group-hover:scale-105" />
                  <span className="absolute inset-0 bg-gradient-to-t from-navy-950/50 to-transparent" />
                  {item.subtitle && (
                    <span className="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-navy-900 shadow backdrop-blur">{item.subtitle}</span>
                  )}
                </div>
                <div className="flex flex-1 flex-col p-6">
                  <h3 className="font-display text-xl font-semibold leading-snug text-navy-950">{item.title}</h3>
                  {item.description && <p className="mt-2 flex-1 text-[14.5px] leading-6 text-navy-600">{item.description}</p>}
                  {item.button_url && (
                    <span className={cx('mt-5 inline-flex items-center gap-2 text-sm font-semibold text-gold-700')}>
                      {item.button_text || 'Learn more'}
                      <Icon name="arrow-right" className="size-4 transition duration-300 group-hover:translate-x-1.5" strokeWidth={2} />
                    </span>
                  )}
                </div>
              </SmartLink>
            </Reveal>
          ))}
        </ul>
      </div>
    </section>
  );
}
