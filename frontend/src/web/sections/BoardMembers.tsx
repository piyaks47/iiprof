import { useState } from 'react';
import { Link } from 'react-router';
import type { HomeSectionOf, TeamMember } from '@shared/types';
import { initialsOf } from '../components/Brand';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { button, cx } from '../components/ui';
import { ProfileDialog } from './TeamGrid';

const PER_GROUP = 8;

/** Board & leadership (Website → Management & team): one tab per body, profiles open in a dialog. */
export function BoardMembers({ section }: { section: HomeSectionOf<'board'> }) {
  const groups = section.extra?.groups ?? [];
  const [group, setGroup] = useState(groups[0]?.key ?? '');
  const [open, setOpen] = useState<TeamMember | null>(null);

  const current = groups.find((g) => g.key === group) ?? groups[0];
  const all = current ? section.items.filter((m) => m.group === current.key) : section.items;
  const members = all.slice(0, PER_GROUP);

  return (
    <section id="board" className="relative overflow-hidden bg-navy-950 py-16 text-white sm:py-20">
      <div className="pointer-events-none absolute -right-40 -top-40 size-[34rem] rounded-full bg-gold-400/10 blur-3xl" />
      <div className="pointer-events-none absolute inset-0 bg-lattice opacity-[0.06]" />
      <div className="container-page relative">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeading dark eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} />
          {section.button_text && (
            <Reveal delay={150} className="shrink-0">
              <SmartLink href={section.button_url} className={button.ghostLight}>
                {section.button_text}
                <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
              </SmartLink>
            </Reveal>
          )}
        </div>

        {groups.length > 1 && (
          <Reveal delay={100} className="scrollbar-none -mx-4 mt-8 overflow-x-auto px-4">
            <div role="tablist" aria-label="Bodies" className="inline-flex gap-1 rounded-full bg-white/5 p-1 ring-1 ring-white/10">
              {groups.map((g) => (
                <button key={g.key} type="button" role="tab" aria-selected={g.key === current?.key} onClick={() => setGroup(g.key)}
                  className={cx('whitespace-nowrap rounded-full px-4 py-2 text-[13px] font-semibold transition sm:px-5', g.key === current?.key ? 'bg-gold-400 text-navy-950 shadow' : 'text-navy-100 hover:bg-white/10')}>
                  {g.title}
                </button>
              ))}
            </div>
          </Reveal>
        )}

        <ul key={current?.key} role="tabpanel" className={cx('mt-10 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3', members.length === 5 ? 'lg:grid-cols-5 lg:gap-5' : 'lg:grid-cols-4')}>
          {members.map((m, i) => (
            <Reveal as="li" key={m.id} delay={(i % 4) * 90}>
              <BoardCard member={m} onOpen={() => setOpen(m)} />
            </Reveal>
          ))}
        </ul>

        {current && all.length > PER_GROUP && (
          <div className="mt-8 text-center">
            <Link to={current.path} className="link-underline inline-flex items-center gap-1.5 text-sm font-semibold text-gold-300">
              All {all.length} members of the {current.title} <Icon name="arrow-right" className="size-4" strokeWidth={2} />
            </Link>
          </div>
        )}
      </div>
      {open && <ProfileDialog member={open} onClose={() => setOpen(null)} />}
    </section>
  );
}

function BoardCard({ member, onOpen }: { member: TeamMember; onOpen: () => void }) {
  const hasBio = Boolean(member.bio_html);
  const monogram = (
    <span className="flex size-full items-center justify-center bg-gradient-to-br from-navy-800 to-navy-900 font-display text-5xl text-gold-300">
      {initialsOf(member.name.replace(/^(mr|mrs|ms|dr|prof)\.?\s+/i, '')).slice(0, 2)}
    </span>
  );
  const body = (
    <>
      <span className="relative block aspect-[4/5] overflow-hidden rounded-2xl bg-navy-900 ring-1 ring-white/10">
        <SafeImg src={member.photo_url ?? undefined} alt="" loading="lazy" decoding="async" fallback={monogram}
          className="size-full object-cover object-top transition duration-[1200ms] ease-out-expo group-hover:scale-105" />
        <span className="absolute inset-0 bg-gradient-to-t from-navy-950/80 via-transparent to-transparent opacity-70 transition duration-500 group-hover:opacity-100" />
        {hasBio && (
          <span className="absolute bottom-3 right-3 flex size-9 translate-y-2 items-center justify-center rounded-full bg-gold-400 text-navy-950 opacity-0 transition duration-500 group-hover:translate-y-0 group-hover:opacity-100 group-focus-visible:translate-y-0 group-focus-visible:opacity-100">
            <Icon name="plus" className="size-4" strokeWidth={2.25} />
          </span>
        )}
      </span>
      <span className="mt-4 block font-display text-lg font-semibold leading-snug text-white sm:text-xl">{member.name}</span>
      {member.role && <span className="mt-1 block text-[11.5px] font-semibold uppercase leading-5 tracking-[0.14em] text-gold-300/90">{member.role}</span>}
    </>
  );

  return hasBio ? (
    <button type="button" onClick={onOpen} className="group block w-full text-left" aria-label={`Read the profile of ${member.name}`}>{body}</button>
  ) : (
    <div className="group">{body}</div>
  );
}
