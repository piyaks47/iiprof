import { useId, useState } from 'react';
import type { HomeSectionOf } from '@shared/types';
import { Icon } from '../components/Icon';
import { Prose } from '../components/Prose';
import { Reveal } from '../components/Reveal';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { button, cx } from '../components/ui';

/** Questions and answers in an accordion (Homepage → FAQs). */
export function Faq({ section }: { section: HomeSectionOf<'faq'> }) {
  const [open, setOpen] = useState<number | null>(section.items[0]?.id ?? null);
  const uid = useId();

  return (
    <section id="faq" className="relative bg-ivory-100 py-16 sm:py-20">
      <div className="container-page grid gap-12 lg:grid-cols-12">
        <div className="lg:col-span-4">
          <div className="lg:sticky lg:top-28">
            <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} />
            {section.button_text && (
              <Reveal delay={200} className="mt-8">
                <SmartLink href={section.button_url} className={button.gold}>
                  {section.button_text}
                  <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
                </SmartLink>
              </Reveal>
            )}
          </div>
        </div>

        <Reveal as="ul" delay={100} className="divide-y divide-navy-900/8 overflow-hidden rounded-2xl bg-white shadow-[0_24px_50px_-38px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/5 lg:col-span-8">
          {section.items.map((item) => {
            const expanded = open === item.id;
            const panel = `${uid}-faq-${item.id}`;
            return (
              <li key={item.id}>
                <h3>
                  <button
                    type="button"
                    aria-expanded={expanded}
                    aria-controls={panel}
                    onClick={() => setOpen(expanded ? null : item.id)}
                    className="group flex w-full items-center justify-between gap-6 px-6 py-5 text-left transition hover:bg-ivory-50 sm:px-8"
                  >
                    <span className={cx('text-[17px] font-semibold leading-snug transition', expanded ? 'text-gold-700' : 'text-navy-950')}>{item.title}</span>
                    <span className={cx('flex size-9 shrink-0 items-center justify-center rounded-full ring-1 transition duration-500', expanded ? 'rotate-45 bg-navy-950 text-gold-300 ring-navy-950' : 'text-navy-700 ring-navy-900/15 group-hover:ring-gold-400')}>
                      <Icon name="plus" className="size-4" strokeWidth={2} />
                    </span>
                  </button>
                </h3>
                <div id={panel} role="region" className={cx('grid transition-[grid-template-rows] duration-500 ease-out-expo', expanded ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]')}>
                  <div className="overflow-hidden" inert={!expanded}>
                    <Prose html={item.description_html} className="prose-base px-6 pb-6 text-navy-700 sm:px-8 [&>p:first-child]:mt-0 [&>p:last-child]:mb-0" />
                  </div>
                </div>
              </li>
            );
          })}
        </Reveal>
      </div>
    </section>
  );
}
