import { useState } from 'react';
import type { HomeSectionOf } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { button, cx } from '../components/ui';
import { BatchList, ProgrammeCard } from './TrainingParts';

/** Training & development: programme cards in tabs next to the scheduled dates (Training → Training programmes, batches). */
export function TrainingBlock({ section }: { section: HomeSectionOf<'training'> }) {
  const { batches, types } = section.extra;
  const present = types.filter((t) => section.items.some((p) => p.type === t.key));
  const [tab, setTab] = useState('all');
  const programmes = section.items.filter((p) => tab === 'all' || p.type === tab).slice(0, 4);

  return (
    <section id="training" className="relative bg-ivory-100 py-16 sm:py-20">
      <div className="container-page">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} />
          {section.button_text && (
            <Reveal delay={150} className="shrink-0">
              <SmartLink href={section.button_url} className={button.outline}>
                {section.button_text} <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
              </SmartLink>
            </Reveal>
          )}
        </div>

        <div className={cx('mt-10 grid gap-8', batches.length > 0 && 'lg:grid-cols-12')}>
          <div className={batches.length > 0 ? 'lg:col-span-8' : ''}>
            {present.length > 1 && (
              <div role="tablist" aria-label="Types of programme" className="scrollbar-none -mx-1 mb-6 flex gap-2 overflow-x-auto px-1 pb-1">
                {[{ key: 'all', label: 'All programmes' }, ...present].map((t) => (
                  <button key={t.key} type="button" role="tab" aria-selected={tab === t.key} onClick={() => setTab(t.key)}
                    className={cx('shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition duration-300', tab === t.key ? 'bg-navy-950 text-gold-300 shadow-[0_10px_24px_-12px_rgb(7_15_32/0.7)]' : 'bg-white text-navy-800 ring-1 ring-navy-900/10 hover:ring-gold-400')}>
                    {t.label}
                  </button>
                ))}
              </div>
            )}
            <ul key={tab} role="tabpanel" className={cx('grid gap-5', programmes.length > 1 && 'sm:grid-cols-2')}>
              {programmes.map((p, i) => (
                <Reveal as="li" key={p.id} delay={(i % 2) * 100} className="h-full">
                  <ProgrammeCard programme={p} index={i} />
                </Reveal>
              ))}
            </ul>
          </div>

          {batches.length > 0 && (
            <Reveal from="right" delay={120} className="lg:col-span-4">
              <div className="relative isolate overflow-hidden rounded-2xl bg-navy-950 p-6 text-white shadow-[0_30px_60px_-36px_rgb(7_15_32/0.7)] sm:p-7 lg:sticky lg:top-24">
                <div className="bg-lattice absolute inset-0 -z-10 opacity-50" />
                <div className="absolute -right-16 -top-16 -z-10 size-56 rounded-full bg-gold-500/20 blur-3xl" />
                <div className="flex items-center justify-between gap-3">
                  <p className="eyebrow text-gold-300">Scheduled dates</p>
                  <Icon name="calendar" className="size-5 text-gold-300" />
                </div>
                <BatchList batches={batches.slice(0, 5)} dark />
                <SmartLink href="/training#calendar" className="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-gold-200 hover:text-white">
                  Full training calendar <Icon name="arrow-right" className="size-4" strokeWidth={2} />
                </SmartLink>
              </div>
            </Reveal>
          )}
        </div>
      </div>
    </section>
  );
}
