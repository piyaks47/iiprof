import { useEffect, useRef, useState } from 'react';
import type { HomeSection } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { button, cx } from '../components/ui';
import { EventCard } from './EventCard';

export function Events({ section }: { section: Extract<HomeSection, { key: 'events' }> }) {
  const track = useRef<HTMLUListElement>(null);
  const [edges, setEdges] = useState({ start: true, end: true });

  const measure = () => {
    const el = track.current;
    if (!el) return;
    setEdges({ start: el.scrollLeft < 8, end: el.scrollLeft + el.clientWidth >= el.scrollWidth - 8 });
  };

  useEffect(() => {
    measure();
    window.addEventListener('resize', measure);
    return () => window.removeEventListener('resize', measure);
  }, [section.items.length]);

  const scroll = (dir: 1 | -1) => {
    const el = track.current;
    const card = el?.querySelector('li');
    if (!el || !card) return;
    el.scrollBy({ left: dir * (card.getBoundingClientRect().width + 20), behavior: 'smooth' });
  };

  return (
    <section id="events" className="relative bg-ivory-100 py-16 sm:py-20">
      <div className="container-page">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} />
          <Reveal delay={150} className="flex shrink-0 items-center gap-3">
            {!(edges.start && edges.end) &&
              ([-1, 1] as const).map((dir) => (
                <button
                  key={dir}
                  type="button"
                  onClick={() => scroll(dir)}
                  disabled={dir === -1 ? edges.start : edges.end}
                  className="flex size-11 items-center justify-center rounded-full text-navy-900 ring-1 ring-navy-900/15 transition duration-300 hover:bg-navy-950 hover:text-gold-300 disabled:pointer-events-none disabled:opacity-30"
                  aria-label={dir === 1 ? 'Next events' : 'Previous events'}
                >
                  <Icon name={dir === 1 ? 'arrow-right' : 'arrow-left'} className="size-5" />
                </button>
              ))}
            <SmartLink href={section.button_url || '/events'} className={cx(button.outline, 'ml-2')}>
              {section.button_text || 'All events'}
            </SmartLink>
          </Reveal>
        </div>

        {section.items.length === 1 ? (
          <Reveal className="mt-10">
            <EventCard event={section.items[0]} featured />
          </Reveal>
        ) : (
        <ul
          ref={track}
          onScroll={measure}
          className="scrollbar-none -mx-4 mt-10 flex snap-x snap-mandatory scroll-px-4 gap-5 overflow-x-auto scroll-smooth px-4 pb-6 sm:-mx-6 sm:scroll-px-6 sm:px-6 lg:-mx-10 lg:scroll-px-10 lg:px-10"
        >
          {section.items.map((event, i) => (
            <Reveal
              as="li"
              key={event.id}
              delay={Math.min(i, 3) * 110}
              className="w-[78%] shrink-0 snap-start sm:w-[calc(50%-10px)] lg:w-[calc(33.333%-14px)] xl:w-[calc(25%-15px)]"
            >
              <EventCard event={event} index={i} />
            </Reveal>
          ))}
        </ul>
        )}
      </div>
    </section>
  );
}
