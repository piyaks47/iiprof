import type { HomeSectionOf } from '@shared/types';
import { initialsOf } from '../components/Brand';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { cx } from '../components/ui';

/** Trainers & faculty (Training → Trainers & faculty). Hidden until one is added. */
export function Faculty({ section }: { section: HomeSectionOf<'faculty'> }) {
  return (
    <section id="faculty" className="relative bg-white py-16 sm:py-20">
      <div className="container-page">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} align="center" />
        <ul className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {section.items.map((t, i) => {
            const card = (
              <div className="group h-full overflow-hidden rounded-2xl bg-white shadow-[0_24px_50px_-36px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/5 transition duration-500 ease-out-expo hover:-translate-y-1.5">
                <div className="aspect-[4/5] overflow-hidden bg-navy-950">
                  <SafeImg src={t.image_url ?? undefined} alt={t.title} loading="lazy" className="size-full object-cover object-top transition duration-[1200ms] ease-out-expo group-hover:scale-105"
                    fallback={<div className="flex size-full items-center justify-center bg-gradient-to-br from-navy-800 to-navy-950 font-display text-5xl text-gold-300">{initialsOf(t.title).slice(0, 2)}</div>} />
                </div>
                <div className="p-5">
                  <h3 className="font-display text-lg font-semibold text-navy-950">{t.title}</h3>
                  {t.subtitle && <p className="mt-1 text-[12px] font-semibold uppercase tracking-[0.12em] text-gold-700">{t.subtitle}</p>}
                  {t.description && <p className="mt-2 line-clamp-3 text-[14px] leading-6 text-navy-600">{t.description}</p>}
                </div>
              </div>
            );
            return (
              <Reveal as="li" key={t.id} delay={(i % 4) * 90} className={cx('h-full')}>
                {t.button_url ? <SmartLink href={t.button_url} className="block h-full text-left">{card}</SmartLink> : card}
              </Reveal>
            );
          })}
        </ul>
      </div>
    </section>
  );
}
