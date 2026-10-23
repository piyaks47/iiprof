import type { CSSProperties } from 'react';
import type { Site } from '@shared/types';
import { Icon } from './Icon';
import { SmartLink } from './SmartLink';

/** Thin bar above the header: scrolling announcements, contact and social links. */
export function TopBar({ site }: { site?: Site }) {
  const items = site?.announcements ?? [];
  const email = site?.settings.contact_email;
  const phone = site?.settings.contact_phone;

  return (
    <div className="relative z-50 bg-navy-950 text-[13px] text-navy-200">
      <div className="container-page flex h-10 items-center gap-6">
        <div className="flex min-w-0 flex-1 items-center gap-3">
          <span className="flex shrink-0 items-center gap-2 text-gold-300">
            <Icon name="megaphone" className="size-4" />
            <span className="hidden text-[10px] font-bold uppercase tracking-[0.25em] md:inline">News</span>
          </span>
          {items.length > 0 && (
            <div className="group relative min-w-0 flex-1 overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_4%,black_96%,transparent)]">
              <div
                className="flex w-max animate-marquee gap-12 whitespace-nowrap group-hover:[animation-play-state:paused]"
                style={{ '--marquee-duration': `${Math.max(24, items.reduce((n, a) => n + a.text.length, 0) / 4)}s` } as CSSProperties}
              >
                {[0, 1].map((copy) => (
                  <div key={copy} className="flex gap-12" aria-hidden={copy === 1}>
                    {items.map((a) => (
                      <SmartLink key={a.id} href={a.url} className="inline-flex items-center gap-3 transition hover:text-white">
                        <span className="size-1 rounded-full bg-gold-400" />
                        {a.text}
                      </SmartLink>
                    ))}
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>

        <div className="hidden shrink-0 items-center gap-5 lg:flex">
          {email && (
            <a href={`mailto:${email}`} className="flex items-center gap-1.5 transition hover:text-gold-300">
              <Icon name="envelope" className="size-4" /> {email}
            </a>
          )}
          {phone && (
            <a href={`tel:${phone.replace(/[^\d+]/g, '')}`} className="flex items-center gap-1.5 transition hover:text-gold-300">
              <Icon name="phone" className="size-4" /> {phone}
            </a>
          )}
          {site?.socials.length ? (
            <span className="flex items-center gap-3 border-l border-white/10 pl-5">
              {site.socials.map((s) => (
                <a key={s.key} href={s.url} target="_blank" rel="noopener noreferrer" className="text-[11px] font-semibold uppercase tracking-wider transition hover:text-gold-300">
                  {s.label}
                </a>
              ))}
            </span>
          ) : null}
        </div>
      </div>
    </div>
  );
}
