import type { HomeSectionOf } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { button, cx } from '../components/ui';

/** Membership grades as cards, each with its own button (Homepage → Membership categories). */
export function Membership({ section }: { section: HomeSectionOf<'membership'> }) {
  const items = section.items;

  return (
    <section id="membership" className="relative bg-ivory-100 py-16 sm:py-20">
      <div className="container-page">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} align="center" />

        <ul className={cx('mt-12 grid gap-5 sm:grid-cols-2', items.length >= 3 && 'lg:grid-cols-3')}>
          {items.map((item, i) => (
            <Reveal as="li" key={item.id} delay={(i % 3) * 100} className="h-full">
              <div className="group relative flex h-full flex-col overflow-hidden rounded-2xl bg-white p-7 shadow-[0_24px_50px_-36px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/5 transition duration-500 ease-out-expo hover:-translate-y-1.5 hover:shadow-[0_40px_70px_-40px_rgb(7_15_32/0.55)]">
                <span className="absolute inset-x-0 top-0 h-1 origin-left scale-x-0 bg-gradient-to-r from-gold-300 via-gold-500 to-gold-300 transition-transform duration-700 ease-out-expo group-hover:scale-x-100" />
                <div className="flex items-start justify-between gap-4">
                  <h3 className="font-display text-2xl font-semibold leading-tight text-navy-950">{item.title}</h3>
                  {item.value && (
                    <span className="shrink-0 rounded-full bg-navy-950 px-3 py-1 text-[11px] font-bold tracking-[0.14em] text-gold-300">{item.value}</span>
                  )}
                </div>
                {item.subtitle && (
                  <p className="mt-4 flex items-start gap-2 text-sm font-semibold text-navy-800">
                    <Icon name="check" className="mt-0.5 size-4 shrink-0 text-gold-500" strokeWidth={2.25} />
                    {item.subtitle}
                  </p>
                )}
                {item.description && <p className="mt-3 flex-1 text-[14.5px] leading-6 text-navy-600">{item.description}</p>}
                {item.button_text && item.button_url && (
                  <SmartLink href={item.button_url} className={cx(button.outline, 'mt-7 w-full group-hover:bg-navy-950 group-hover:text-white group-hover:ring-navy-950')}>
                    {item.button_text}
                    <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
                  </SmartLink>
                )}
              </div>
            </Reveal>
          ))}
        </ul>

        {section.button_text && (
          <Reveal className="mt-10 text-center">
            <SmartLink href={section.button_url} className={button.gold}>
              {section.button_text}
              <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
            </SmartLink>
          </Reveal>
        )}
      </div>
    </section>
  );
}
