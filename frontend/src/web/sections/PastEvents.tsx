import { useRef } from 'react';
import { Link } from 'react-router';
import type { HomeSectionOf } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { button } from '../components/ui';
import { Backdrop } from './Backdrop';
import { formatDate } from './EventCard';

/** Archive of events that took place: carousel with summaries and photo / video links (Homepage → Events). */
export function PastEvents({ section }: { section: HomeSectionOf<'past_events'> }) {
  const track = useRef<HTMLUListElement>(null);
  const scroll = (dir: 1 | -1) => {
    const el = track.current;
    const card = el?.querySelector('li');
    if (el && card) el.scrollBy({ left: dir * (card.getBoundingClientRect().width + 20), behavior: 'smooth' });
  };

  return (
    <section id="past_events" className="relative bg-white py-16 sm:py-20">
      <div className="container-page">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} />
          <Reveal delay={150} className="flex shrink-0 items-center gap-3">
            {section.items.length > 2 && ([-1, 1] as const).map((dir) => (
              <button key={dir} type="button" onClick={() => scroll(dir)} aria-label={dir === 1 ? 'Next past events' : 'Previous past events'}
                className="flex size-11 items-center justify-center rounded-full text-navy-900 ring-1 ring-navy-900/15 transition hover:bg-navy-950 hover:text-gold-300">
                <Icon name={dir === 1 ? 'arrow-right' : 'arrow-left'} className="size-5" />
              </button>
            ))}
            {section.button_text && <SmartLink href={section.button_url} className={button.outline}>{section.button_text}</SmartLink>}
          </Reveal>
        </div>

        <ul ref={track} className="scrollbar-none -mx-4 mt-10 flex snap-x snap-mandatory scroll-px-4 gap-5 overflow-x-auto px-4 pb-4 sm:-mx-6 sm:px-6 lg:-mx-10 lg:px-10">
          {section.items.map((event, i) => (
            <Reveal as="li" key={event.id} delay={Math.min(i, 3) * 100} className="w-[80%] shrink-0 snap-start sm:w-[calc(50%-10px)] lg:w-[calc(33.333%-14px)]">
              <article className="group flex h-full flex-col overflow-hidden rounded-2xl bg-ivory-50 ring-1 ring-navy-900/5 transition duration-500 hover:-translate-y-1 hover:bg-white hover:shadow-[0_30px_60px_-38px_rgb(7_15_32/0.55)]">
                <Link to={event.path} className="relative block aspect-[16/10] overflow-hidden bg-navy-950">
                  <SafeImg src={event.image_url ?? undefined} alt="" loading="lazy" fallback={<Backdrop variant={i} rings={false} />}
                    className="size-full object-cover grayscale-[35%] transition duration-[1200ms] ease-out-expo group-hover:scale-105 group-hover:grayscale-0" />
                  <span className="absolute inset-0 bg-gradient-to-t from-navy-950/70 to-transparent" />
                  <span className="absolute bottom-3 left-4 inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-navy-900">
                    <Icon name="clock" className="size-3.5 text-gold-600" /> {formatDate(event.starts_on, { month: 'short', year: 'numeric' }) ?? 'Past event'}
                  </span>
                </Link>
                <div className="flex flex-1 flex-col p-6">
                  <h3 className="font-display text-xl font-semibold leading-snug text-navy-950"><Link to={event.path} className="hover:text-gold-700">{event.title}</Link></h3>
                  {event.summary && <p className="mt-2 line-clamp-3 flex-1 text-[14.5px] leading-6 text-navy-600">{event.summary}</p>}
                  <div className="mt-5 flex items-center justify-between gap-3 border-t border-ivory-200 pt-4 text-sm">
                    {event.media_url ? (
                      <SmartLink href={event.media_url} className="inline-flex items-center gap-1.5 font-semibold text-gold-700 hover:text-gold-600">
                        <Icon name="photo" className="size-4" /> Photos &amp; video
                      </SmartLink>
                    ) : <span />}
                    <Link to={event.path} className="inline-flex items-center gap-1 text-navy-500 hover:text-navy-900">Summary <Icon name="arrow-right" className="size-3.5" strokeWidth={2} /></Link>
                  </div>
                </div>
              </article>
            </Reveal>
          ))}
        </ul>
      </div>
    </section>
  );
}
