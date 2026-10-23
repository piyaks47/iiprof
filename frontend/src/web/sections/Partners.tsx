import type { HomeItem, HomeSectionOf } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { SmartLink } from '../components/SmartLink';
import { cx } from '../components/ui';

/** Registrations, affiliations and partners: logos, or name badges without a logo (Homepage → Registrations & partners). */
export function Partners({ section }: { section: HomeSectionOf<'partners'> }) {
  const items = section.items;
  const marquee = items.length > 5;

  return (
    <section id="partners" className="relative border-y border-ivory-200 bg-white py-12 sm:py-14">
      <div className="container-page">
        <Reveal className="flex flex-col items-center gap-2 text-center">
          {section.eyebrow && <p className="eyebrow justify-center">{section.eyebrow}</p>}
          {section.title && <h2 className="font-display text-2xl font-semibold text-navy-950 sm:text-3xl">{section.title}</h2>}
          {section.subtitle && <p className="max-w-2xl text-sm text-navy-500">{section.subtitle}</p>}
        </Reveal>
      </div>

      {marquee ? (
        <div className="group relative mt-9 overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_8%,black_92%,transparent)]">
          <ul className="flex w-max animate-marquee [--marquee-duration:45s] group-hover:[animation-play-state:paused] motion-reduce:animate-none" aria-label={section.title ?? 'Registrations'}>
            {[...items, ...items].map((item, i) => (
              <li key={`${item.id}-${i}`} className="pr-4" aria-hidden={i >= items.length || undefined}>
                <Badge item={item} tabbable={i < items.length} />
              </li>
            ))}
          </ul>
        </div>
      ) : (
        <ul className="container-page mt-9 flex flex-wrap justify-center gap-4">
          {items.map((item) => (
            <li key={item.id}><Badge item={item} tabbable /></li>
          ))}
        </ul>
      )}
    </section>
  );
}

function Badge({ item, tabbable }: { item: HomeItem; tabbable: boolean }) {
  const body = (
    <>
      <SafeImg src={item.image_url ?? undefined} alt={item.title} loading="lazy" className="h-10 w-auto max-w-32 object-contain"
        fallback={
          <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-navy-800 to-navy-950 text-gold-300 ring-1 ring-gold-400/40">
            <Icon name="shield" className="size-5" strokeWidth={1.6} />
          </span>
        } />
      <span className="min-w-0 text-left">
        <span className="block whitespace-nowrap text-[15px] font-semibold text-navy-950">{item.title}</span>
        {item.subtitle && <span className="block whitespace-nowrap text-xs text-navy-500">{item.subtitle}</span>}
      </span>
    </>
  );
  const className = cx(
    'flex items-center gap-3.5 rounded-2xl bg-ivory-50 px-5 py-3.5 ring-1 ring-navy-900/5 transition duration-300',
    item.button_url && 'hover:-translate-y-0.5 hover:bg-white hover:shadow-[0_18px_40px_-28px_rgb(7_15_32/0.5)] hover:ring-gold-400/50',
  );

  if (!item.button_url) return <div className={className}>{body}</div>;
  return tabbable ? (
    <SmartLink href={item.button_url} className={className}>{body}</SmartLink>
  ) : (
    <div className={className}>{body}</div>
  );
}
