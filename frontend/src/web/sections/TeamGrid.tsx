import { useEffect, useState } from 'react';
import { createPortal } from 'react-dom';
import { useTeam } from '@shared/hooks/queries';
import type { TeamMember } from '@shared/types';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { Spotlight } from '../components/Spotlight';
import { initialsOf } from '../components/Brand';
import { Icon } from '../components/Icon';
import { Prose } from '../components/Prose';
import { useScrollLock } from '../lib/motion';

/** People of one management group (/management/director-council …). */
export function TeamGrid({ group }: { group: string }) {
  const { data, isPending } = useTeam(group);
  const members = data?.members ?? [];

  if (isPending) {
    return (
      <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3" aria-busy="true">
        {[0, 1, 2].map((i) => <li key={i} className="aspect-[4/5] animate-pulse rounded-2xl bg-ivory-200" />)}
      </ul>
    );
  }

  if (members.length === 0) {
    return (
      <div className="rounded-2xl border border-dashed border-gold-400/50 bg-white/60 px-8 py-14 text-center">
        <p className="eyebrow justify-center">Members</p>
        <p className="mt-4 font-display text-2xl text-navy-950">To be announced</p>
        <p className="mt-2 text-navy-500">The members of this body will be published here soon.</p>
      </div>
    );
  }

  return (
    <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      {members.map((m, i) => (
        <Reveal as="li" key={m.id} delay={(i % 3) * 100}>
          <PersonCard member={m} />
        </Reveal>
      ))}
    </ul>
  );
}

function PersonCard({ member }: { member: TeamMember }) {
  const [open, setOpen] = useState(false);
  const long = (member.bio ?? '').length > 220 || (member.bio ?? '').includes('\n');
  const monogram = (
    <div className="flex size-full items-center justify-center bg-gradient-to-br from-navy-800 to-navy-950 font-display text-6xl text-gold-300">
      {initialsOf(member.name.replace(/^(mr|mrs|ms|dr|prof)\.?\s+/i, '')).slice(0, 2)}
    </div>
  );

  return (
    <Spotlight className="group h-full overflow-hidden rounded-2xl bg-white shadow-[0_24px_50px_-34px_rgb(7_15_32/0.45)] ring-1 ring-navy-900/5 transition duration-500 ease-out-expo hover:-translate-y-1.5 hover:shadow-[0_40px_80px_-36px_rgb(7_15_32/0.5)]">
      <div className="relative aspect-[4/5] overflow-hidden bg-navy-950">
        <SafeImg src={member.photo_url ?? undefined} alt={member.name} loading="lazy" fallback={monogram}
          className="size-full object-cover object-top transition duration-[1200ms] ease-out-expo group-hover:scale-105" />
        <div className="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-navy-950/70 to-transparent opacity-0 transition duration-500 group-hover:opacity-100" />
        <span className="absolute inset-x-6 bottom-0 h-0.5 origin-left scale-x-0 bg-gradient-to-r from-gold-300 to-gold-500 transition-transform duration-700 ease-out-expo group-hover:scale-x-100" />
      </div>
      <div className="p-6">
        <h3 className="text-xl font-semibold text-navy-950">{member.name}</h3>
        {member.role && <p className="mt-2 text-[13px] font-semibold uppercase leading-5 tracking-[0.12em] text-gold-700">{member.role}</p>}
        {member.bio && member.bio_html ? (
          <>
            <div className={long ? 'relative mt-3 max-h-28 overflow-hidden' : 'mt-3'}>
              <Prose html={member.bio_html} className="prose-sm text-[15px] leading-7 [&_li]:my-0 [&_p]:my-0 [&_ul]:my-1" />
              {long && <span className="pointer-events-none absolute inset-x-0 bottom-0 h-12 bg-gradient-to-t from-white to-transparent" />}
            </div>
            {long && (
              <button type="button" onClick={() => setOpen(true)} className="link-underline mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-gold-700">
                Read profile <Icon name="arrow-right" className="size-3.5" strokeWidth={2} />
              </button>
            )}
          </>
        ) : (
          member.bio && <p className="mt-3 text-[15px] leading-7 text-navy-600">{member.bio}</p>
        )}
      </div>
      {open && <ProfileDialog member={member} onClose={() => setOpen(false)} />}
    </Spotlight>
  );
}

export function ProfileDialog({ member, onClose }: { member: TeamMember; onClose: () => void }) {
  useScrollLock(true);
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && onClose();
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [onClose]);

  return createPortal(
    <div className="fixed inset-0 z-[80] flex animate-fade-in items-end justify-center bg-navy-950/80 p-0 backdrop-blur-sm sm:items-center sm:p-6" role="dialog" aria-modal="true" aria-label={member.name} onClick={onClose}>
      <div className="relative max-h-[88vh] w-full max-w-2xl animate-pop overflow-y-auto rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl" onClick={(e) => e.stopPropagation()}>
        <div className="flex items-center gap-5 border-b border-ivory-200 bg-ivory-50 p-6 sm:p-8">
          <span className="size-20 shrink-0 overflow-hidden rounded-full ring-2 ring-gold-400/60 ring-offset-2">
            <SafeImg src={member.photo_url ?? undefined} alt="" className="size-full object-cover object-top"
              fallback={<span className="flex size-full items-center justify-center bg-navy-900 font-display text-2xl text-gold-300">{initialsOf(member.name).slice(0, 2)}</span>} />
          </span>
          <div className="min-w-0">
            <p className="font-display text-2xl font-semibold text-navy-950">{member.name}</p>
            {member.role && <p className="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-gold-700">{member.role}</p>}
          </div>
        </div>
        <div className="p-6 sm:p-8">
          <Prose html={member.bio_html ?? ''} className="prose-base" />
        </div>
        <button type="button" onClick={onClose} autoFocus className="absolute right-4 top-4 rounded-full bg-white p-2.5 text-navy-900 shadow ring-1 ring-navy-900/10 transition hover:rotate-90" aria-label="Close">
          <Icon name="close" className="size-5" />
        </button>
      </div>
    </div>,
    document.body,
  );
}
