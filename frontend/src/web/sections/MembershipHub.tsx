import { useState } from 'react';
import type { FeaturedMember, HomeSectionOf } from '@shared/types';
import { initialsOf } from '../components/Brand';
import { Icon } from '../components/Icon';
import { Prose } from '../components/Prose';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { button, cx } from '../components/ui';
import { Backdrop } from './Backdrop';

/** Join IIP · member community highlights · member directory in tabs (Membership → Membership sections). */
export function MembershipHub({ section }: { section: HomeSectionOf<'membership_hub'> }) {
  const { members, member_categories: categories } = section.extra;
  const panels = [section.body_html || section.image_url ? 'join' : null, section.items.length ? 'community' : null, members.length ? 'members' : null].filter(Boolean);

  return (
    <section id="membership_hub" className="relative bg-ivory-100 py-16 sm:py-20">
      <div className="container-page">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} align="center" />

        <div className={cx('mt-12 grid gap-6', panels.length >= 3 ? 'lg:grid-cols-3' : panels.length === 2 ? 'md:grid-cols-2' : '')}>
          {panels.includes('join') && (
            <Reveal className="h-full">
              <div className="group flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-[0_24px_50px_-36px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/5">
                <div className="relative aspect-[16/10] overflow-hidden bg-navy-950">
                  <SafeImg src={section.image_url ?? undefined} alt="" loading="lazy" fallback={<Backdrop variant={1} rings={false} />}
                    className="size-full object-cover transition duration-[1400ms] ease-out-expo group-hover:scale-105" />
                  <span className="absolute inset-0 bg-gradient-to-t from-navy-950/70 to-transparent" />
                  <p className="absolute bottom-4 left-6 font-display text-2xl font-semibold text-white">Join IIP</p>
                </div>
                <div className="flex flex-1 flex-col p-6 sm:p-7">
                  {section.body_html && <Prose html={section.body_html} className="prose-base flex-1 [&>p:first-child]:mt-0 [&>p:last-child]:mb-0" />}
                  {section.button_text && (
                    <SmartLink href={section.button_url} className={cx(button.gold, 'mt-6 self-start')}>
                      {section.button_text} <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
                    </SmartLink>
                  )}
                </div>
              </div>
            </Reveal>
          )}

          {panels.includes('community') && (
            <Reveal delay={120} className="h-full">
              <div className="relative isolate flex h-full flex-col overflow-hidden rounded-2xl bg-navy-950 p-7 text-white shadow-[0_30px_60px_-36px_rgb(7_15_32/0.7)] sm:p-8">
                <div className="bg-lattice absolute inset-0 -z-10 opacity-50" />
                <div className="absolute -right-20 -top-20 -z-10 size-64 rounded-full bg-gold-500/20 blur-3xl" />
                <p className="eyebrow text-gold-300">Growing member community</p>
                <dl className="mt-6 flex flex-1 flex-col justify-around gap-6">
                  {section.items.map((item) => (
                    <div key={item.id} className="border-l-2 border-gold-400/60 pl-5">
                      <dd className="text-gilded font-display text-4xl font-semibold leading-none [font-variant-numeric:lining-nums]">{item.value}</dd>
                      <dt className="mt-2 text-sm font-semibold text-white">{item.title}</dt>
                      {item.description && <dd className="mt-1 text-[13.5px] leading-6 text-navy-200">{item.description}</dd>}
                    </div>
                  ))}
                </dl>
              </div>
            </Reveal>
          )}

          {panels.includes('members') && (
            <Reveal delay={240} className="h-full">
              <MemberDirectory members={members} categories={categories} />
            </Reveal>
          )}
        </div>
      </div>
    </section>
  );
}

function MemberDirectory({ members, categories }: { members: FeaturedMember[]; categories: { key: string; label: string }[] }) {
  const [tab, setTab] = useState(categories[0]?.key ?? '');
  const shown = members.filter((m) => m.category === tab);

  return (
    <div className="flex h-full flex-col overflow-hidden rounded-2xl bg-white shadow-[0_24px_50px_-36px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/5">
      <div className="border-b border-ivory-200 px-6 pt-6 sm:px-7">
        <p className="eyebrow">Our members</p>
        {categories.length > 1 ? (
          <div role="tablist" aria-label="Member categories" className="scrollbar-none -mx-1 mt-4 flex gap-1 overflow-x-auto pb-3">
            {categories.map((c) => (
              <button key={c.key} type="button" role="tab" aria-selected={tab === c.key} onClick={() => setTab(c.key)}
                className={cx('shrink-0 rounded-full px-3.5 py-1.5 text-xs font-semibold transition', tab === c.key ? 'bg-navy-950 text-gold-300' : 'text-navy-700 ring-1 ring-navy-900/10 hover:ring-gold-400')}>
                {c.label}
              </button>
            ))}
          </div>
        ) : (
          <p className="mt-2 pb-4 font-display text-xl text-navy-950">{categories[0]?.label}</p>
        )}
      </div>
      <ul key={tab} role="tabpanel" className="max-h-[26rem] flex-1 animate-fade-in divide-y divide-ivory-200 overflow-y-auto px-6 sm:px-7">
        {shown.map((m) => {
          const body = (
            <>
              <span className="size-12 shrink-0 overflow-hidden rounded-full ring-2 ring-gold-400/50 ring-offset-2">
                <SafeImg src={m.photo_url ?? undefined} alt="" loading="lazy" className="size-full object-cover object-top"
                  fallback={<span className="flex size-full items-center justify-center bg-navy-900 font-display text-sm text-gold-300">{initialsOf(m.name.replace(/^(mr|mrs|ms|dr|prof)\.?\s+/i, '')).slice(0, 2)}</span>} />
              </span>
              <span className="min-w-0">
                <span className="block font-semibold text-navy-950">{m.name}</span>
                <span className="mt-0.5 block text-[13px] leading-5 text-navy-500">{[m.designation, m.organisation, m.city].filter(Boolean).join(', ')}</span>
              </span>
            </>
          );
          return (
            <li key={m.id}>
              {m.url ? (
                <SmartLink href={m.url} className="flex items-center gap-4 py-4 transition hover:translate-x-1">{body}</SmartLink>
              ) : (
                <div className="flex items-center gap-4 py-4">{body}</div>
              )}
            </li>
          );
        })}
      </ul>
    </div>
  );
}
