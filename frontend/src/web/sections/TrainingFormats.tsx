import type { HomeSectionOf } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { Spotlight } from '../components/Spotlight';
import { cx } from '../components/ui';

/** Types of training: how it is delivered (Training → Types of training). */
export function TrainingFormats({ section }: { section: HomeSectionOf<'training_formats'> }) {
  const items = section.items;

  return (
    <section id="training_formats" className="relative bg-white py-16 sm:py-20">
      <div className="container-page">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} align="center" />
        <ul className={cx('mt-12 grid gap-5', items.length >= 3 ? 'md:grid-cols-3' : items.length === 2 ? 'md:grid-cols-2' : '')}>
          {items.map((item, i) => (
            <Reveal as="li" key={item.id} delay={i * 110} className="h-full">
              <Spotlight className="group flex h-full flex-col rounded-2xl bg-ivory-50 p-7 ring-1 ring-navy-900/5 transition duration-500 ease-out-expo hover:-translate-y-1.5 hover:bg-white hover:shadow-[0_30px_60px_-38px_rgb(7_15_32/0.55)] sm:p-8">
                <div className="flex items-center justify-between">
                  <span className="flex size-14 items-center justify-center rounded-2xl bg-navy-950 text-gold-300 shadow-[0_14px_30px_-14px_rgb(7_15_32/0.8)] transition duration-500 group-hover:-rotate-6 group-hover:scale-105">
                    <Icon name={item.icon || 'academic-cap'} className="size-6" strokeWidth={1.6} />
                  </span>
                  <span className="font-display text-sm text-navy-300">{String(i + 1).padStart(2, '0')}</span>
                </div>
                {item.subtitle && <p className="mt-6 text-[11px] font-bold uppercase tracking-[0.2em] text-gold-700">{item.subtitle}</p>}
                <h3 className="mt-2 font-display text-2xl font-semibold text-navy-950">{item.title}</h3>
                {item.description && <p className="mt-3 flex-1 text-[15px] leading-7 text-navy-600">{item.description}</p>}
                {item.button_url && (
                  <SmartLink href={item.button_url} className="mt-6 inline-flex items-center gap-2 self-start text-sm font-semibold text-gold-700">
                    {item.button_text || 'Learn more'} <Icon name="arrow-right" className="size-4 transition duration-300 group-hover:translate-x-1" strokeWidth={2} />
                  </SmartLink>
                )}
              </Spotlight>
            </Reveal>
          ))}
        </ul>
      </div>
    </section>
  );
}
