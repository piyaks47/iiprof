import { useMemo, useState } from 'react';
import type { HomeSectionOf, MemberOrganisation } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { button, cx } from '../components/ui';

const WALL = 15;

/** Categories present in a list, with how many organisations each has (in order of first appearance). */
function useCategories(items: MemberOrganisation[]) {
  return useMemo(() => {
    const counts = new Map<string, number>();
    items.forEach((o) => o.category && counts.set(o.category, (counts.get(o.category) ?? 0) + 1));
    return [...counts].map(([label, count]) => ({ label, count }));
  }, [items]);
}

function monogram(name: string) {
  return name.replace(/\b(the|of|and|&|pvt|ltd|llp|co)\b\.?/gi, '').trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase();
}

function Logo({ org, className, dark = false }: { org: MemberOrganisation; className?: string; dark?: boolean }) {
  return (
    <SafeImg src={org.logo_url ?? undefined} alt="" loading="lazy" decoding="async"
      className={cx('max-h-full max-w-full object-contain', className)}
      fallback={
        <span className={cx('flex size-14 items-center justify-center rounded-xl font-display text-xl font-semibold', dark ? 'bg-white/10 text-gold-300' : 'bg-navy-950 text-gold-300')}>
          {monogram(org.name) || org.name[0]}
        </span>
      } />
  );
}

/** Corporate members (Membership → Corporate members): a logo wall with industry filters. */
export function CorporateMembers({ section }: { section: HomeSectionOf<'corporate_members'> }) {
  const [category, setCategory] = useState('');
  const [all, setAll] = useState(false);
  const categories = useCategories(section.items);
  const list = category ? section.items.filter((o) => o.category === category) : section.items;
  const shown = all ? list : list.slice(0, WALL);

  return (
    <section id="corporate_members" className="relative bg-white py-16 sm:py-20">
      <div className="container-page">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} />
          {section.button_text && (
            <Reveal delay={150} className="shrink-0">
              <SmartLink href={section.button_url} className={button.navy}>
                {section.button_text}
                <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
              </SmartLink>
            </Reveal>
          )}
        </div>

        {categories.length > 1 && (
          <Reveal delay={100} className="mt-8 flex flex-wrap gap-2">
            {[{ label: '', count: section.items.length }, ...categories].map((c) => (
              <button key={c.label || 'all'} type="button" onClick={() => { setCategory(c.label); setAll(false); }} aria-pressed={category === c.label}
                className={cx('inline-flex items-center gap-2 rounded-full px-4 py-2 text-[13px] font-semibold transition', category === c.label ? 'bg-navy-950 text-gold-300 shadow' : 'bg-ivory-100 text-navy-700 hover:bg-ivory-200')}>
                {c.label || 'All industries'}
                <span className={cx('rounded-full px-1.5 text-[11px] tabular-nums', category === c.label ? 'bg-white/10' : 'bg-white')}>{c.count}</span>
              </button>
            ))}
          </Reveal>
        )}

        <ul key={category} className={cx('mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4', shown.length === 4 ? 'lg:grid-cols-4' : 'lg:grid-cols-5')}>
          {shown.map((org, i) => (
            <Reveal as="li" key={org.id} from="zoom" delay={(i % 5) * 60}>
              <SmartLink href={org.website} newTab className="group relative flex h-full min-h-44 flex-col items-center justify-center gap-3 overflow-hidden rounded-2xl bg-white p-5 text-center ring-1 ring-navy-900/[0.08] transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_24px_50px_-34px_rgb(7_15_32/0.5)]">
                {org.is_featured && <Icon name="star" className="absolute right-3 top-3 size-4 text-gold-500" title="Featured member" />}
                <span className="flex h-16 w-full items-center justify-center">
                  <Logo org={org} className="h-16 grayscale transition duration-500 group-hover:grayscale-0" />
                </span>
                <span className="text-[14px] font-semibold leading-snug text-navy-950">{org.name}</span>
                <span className="text-[11.5px] font-semibold uppercase tracking-[0.12em] text-navy-400">{[org.category, org.city].filter(Boolean).join(' · ')}</span>
                {org.description && (
                  <span className="pointer-events-none absolute inset-0 flex items-center justify-center bg-navy-950/95 p-5 text-[13px] leading-6 text-navy-50 opacity-0 transition duration-300 group-hover:opacity-100">
                    <span className="line-clamp-5">{org.description}</span>
                  </span>
                )}
              </SmartLink>
            </Reveal>
          ))}
        </ul>

        {list.length > WALL && (
          <div className="mt-6 text-center">
            <button type="button" onClick={() => setAll((v) => !v)} className={button.outline}>
              {all ? 'Show fewer' : `Show all ${list.length}`}
              <Icon name={all ? 'chevron-up' : 'chevron-down'} className="size-4" strokeWidth={2} />
            </button>
          </div>
        )}
      </div>
    </section>
  );
}

const TYPE_ICONS: Record<string, string> = { University: 'building', College: 'academic-cap', School: 'book-open', Institute: 'light-bulb', 'Training centre': 'briefcase', 'Research centre': 'beaker' };

/** Institutional members (Membership → Institutional members): counts per type beside a logo grid. */
export function Institutions({ section }: { section: HomeSectionOf<'institutions'> }) {
  const [type, setType] = useState('');
  const types = useCategories(section.items);
  const list = type ? section.items.filter((o) => o.category === type) : section.items;

  return (
    <section id="institutions" className="relative overflow-hidden bg-ivory-100/70 py-16 sm:py-20">
      <div className="pointer-events-none absolute -right-48 top-10 size-[30rem] rounded-full bg-gold-200/30 blur-3xl" />
      <div className="container-page relative grid gap-10 lg:grid-cols-12 lg:gap-14">
        <div className="lg:col-span-4">
          <div className="lg:sticky lg:top-28">
            <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} />
            {types.length > 0 && (
              <Reveal delay={120} className="mt-8 grid grid-cols-2 gap-2">
                {[{ label: '', count: section.items.length }, ...types].map((t) => (
                  <button key={t.label || 'all'} type="button" onClick={() => setType(t.label)} aria-pressed={type === t.label}
                    className={cx('flex items-center gap-3 rounded-xl p-3 text-left transition', type === t.label ? 'bg-navy-950 text-white shadow-lg' : 'bg-white ring-1 ring-navy-900/5 hover:ring-navy-900/20')}>
                    <span className={cx('font-display text-2xl font-semibold tabular-nums', type === t.label ? 'text-gold-300' : 'text-navy-950')}>{t.count}</span>
                    <span className={cx('text-[12.5px] font-semibold leading-tight', type === t.label ? 'text-navy-100' : 'text-navy-600')}>{t.label || 'All institutions'}</span>
                  </button>
                ))}
              </Reveal>
            )}
            {section.button_text && (
              <Reveal delay={180} className="mt-8">
                <SmartLink href={section.button_url} className={button.gold}>
                  {section.button_text}
                  <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
                </SmartLink>
              </Reveal>
            )}
          </div>
        </div>

        <ul key={type} className="grid content-start gap-4 sm:grid-cols-2 lg:col-span-8 xl:grid-cols-3">
          {list.map((org, i) => (
            <Reveal as="li" key={org.id} delay={(i % 3) * 80}>
              <SmartLink href={org.website} newTab className="group flex h-full flex-col rounded-2xl bg-white p-5 shadow-[0_20px_44px_-34px_rgb(7_15_32/0.45)] ring-1 ring-navy-900/5 transition duration-500 ease-out-expo hover:-translate-y-1 hover:shadow-[0_30px_60px_-34px_rgb(7_15_32/0.5)]">
                <span className="flex items-start justify-between gap-3">
                  <span className="flex h-14 max-w-[9rem] items-center"><Logo org={org} className="h-14" /></span>
                  <span className="inline-flex items-center gap-1.5 rounded-full bg-ivory-100 px-2.5 py-1 text-[10.5px] font-bold uppercase tracking-[0.12em] text-navy-600">
                    <Icon name={TYPE_ICONS[org.category ?? ''] ?? 'academic-cap'} className="size-3.5 text-gold-600" />{org.category ?? 'Institution'}
                  </span>
                </span>
                <span className="mt-4 font-display text-lg font-semibold leading-snug text-navy-950 group-hover:text-gold-700">{org.name}</span>
                {org.description && <span className="mt-1.5 line-clamp-2 text-[13.5px] leading-6 text-navy-500">{org.description}</span>}
                <span className="mt-auto flex items-center justify-between gap-3 pt-4 text-[12px] font-semibold text-navy-400">
                  {org.city ? <span className="inline-flex items-center gap-1"><Icon name="map-pin" className="size-3.5" />{org.city}</span> : <span />}
                  {org.member_since && <span>Member since {org.member_since}</span>}
                </span>
              </SmartLink>
            </Reveal>
          ))}
        </ul>
      </div>
    </section>
  );
}
