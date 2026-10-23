import { useCallback, useRef, useState, type CSSProperties } from 'react';
import { Link } from 'react-router';
import type { HomeSectionOf } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { button, cx } from '../components/ui';
import { usePrefersReducedMotion } from '../lib/motion';
import { Backdrop } from './Backdrop';
import { formatDate } from './EventCard';

const SLIDE_MS = 8000;

/** Seminar banners (News & seminars → Seminars): featured image, title, short text and a button to the destination link. */
export function SeminarBanners({ section }: { section: HomeSectionOf<'seminars'> }) {
  const slides = section.items;
  const count = slides.length;
  const [index, setIndex] = useState(0);
  const [paused, setPaused] = useState(false);
  const reduced = usePrefersReducedMotion();
  const touchX = useRef<number | null>(null);
  const go = useCallback((next: number) => setIndex(((next % count) + count) % count), [count]);

  if (count === 0) return null;
  const autoplay = count > 1 && !reduced;

  return (
    <section id="seminars" className="relative bg-white py-16 sm:py-20">
      <div className="container-page">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} />

        <Reveal from="zoom" delay={100} className="mt-10">
          <div
            role="region"
            aria-roledescription="carousel"
            aria-label={section.title ?? 'Seminars'}
            tabIndex={-1}
            className="relative isolate grid min-h-[30rem] overflow-hidden rounded-3xl bg-navy-950 text-white shadow-[0_40px_90px_-45px_rgb(7_15_32/0.75)] sm:min-h-[26rem]"
            onMouseEnter={() => setPaused(true)}
            onMouseLeave={() => setPaused(false)}
            onFocus={() => setPaused(true)}
            onBlur={() => setPaused(false)}
            onKeyDown={(e) => {
              if (e.key === 'ArrowRight') go(index + 1);
              if (e.key === 'ArrowLeft') go(index - 1);
            }}
            onTouchStart={(e) => (touchX.current = e.touches[0].clientX)}
            onTouchEnd={(e) => {
              if (touchX.current === null) return;
              const dx = e.changedTouches[0].clientX - touchX.current;
              if (Math.abs(dx) > 50) go(index + (dx < 0 ? 1 : -1));
              touchX.current = null;
            }}
          >
            {slides.map((s, i) => {
              const active = i === index;
              const date = formatDate(s.starts_on, { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' });
              const href = s.link || s.path;
              return (
                <article key={s.id} aria-hidden={!active} inert={!active}
                  className={cx('relative isolate flex [grid-area:1/1] transition-opacity duration-700 ease-out', active ? 'z-10 opacity-100' : 'z-0 opacity-0')}>
                  <SafeImg src={s.image_url ?? undefined} alt="" loading={i === 0 ? 'eager' : 'lazy'} decoding="async"
                    fallback={<Backdrop variant={i + 2} className="-z-20" />}
                    className={cx('absolute inset-0 -z-20 size-full object-cover', active && !reduced && 'animate-ken-burns')} />
                  <span className="absolute inset-0 -z-10 bg-gradient-to-r from-navy-950 via-navy-950/80 to-navy-950/10" />
                  <span className="absolute inset-0 -z-10 bg-gradient-to-t from-navy-950/80 via-transparent to-transparent sm:hidden" />

                  <div className="flex max-w-2xl flex-col justify-end gap-4 p-7 pb-20 sm:justify-center sm:p-12 sm:pb-20 lg:p-16">
                    <p className="eyebrow text-gold-300">{count > 1 ? `Seminar ${i + 1} of ${count}` : 'Featured seminar'}</p>
                    <h3 className={cx('font-display text-3xl font-semibold leading-tight sm:text-5xl', active && 'animate-fade-up')}>{s.title}</h3>
                    {(date || s.location) && (
                      <p className="flex flex-wrap gap-2 text-[13px]">
                        {date && <span className="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/15 backdrop-blur"><Icon name="calendar" className="size-4 text-gold-300" />{date}</span>}
                        {s.location && <span className="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/15 backdrop-blur"><Icon name="map-pin" className="size-4 text-gold-300" />{s.location}</span>}
                      </p>
                    )}
                    {s.summary && <p className="line-clamp-3 max-w-xl text-[15px] leading-7 text-navy-100/90 sm:text-base">{s.summary}</p>}
                    <div className="mt-2 flex flex-wrap gap-3">
                      <SmartLink href={href} subject={`Event: ${s.title}`} className={button.gold}>
                        {s.button_text || 'Learn more'}
                        <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
                      </SmartLink>
                      {s.link && s.link !== s.path && <Link to={s.path} className={button.ghostLight}>Details</Link>}
                    </div>
                  </div>
                </article>
              );
            })}

            {count > 1 && (
              <div className="absolute inset-x-0 bottom-0 z-20 flex items-center justify-between gap-4 px-7 pb-6 sm:px-12 lg:px-16">
                <div className="flex gap-2">
                  {slides.map((s, i) => (
                    <button key={s.id} type="button" onClick={() => go(i)} className="group relative h-8 w-10 sm:w-14" aria-label={`Show seminar ${i + 1}: ${s.title}`} aria-current={i === index}>
                      <span className="absolute inset-x-0 top-1/2 h-0.5 -translate-y-1/2 overflow-hidden rounded-full bg-white/25 transition group-hover:bg-white/45">
                        {i === index && autoplay ? (
                          <span key={`p-${index}`} className="absolute inset-0 origin-left animate-progress rounded-full bg-gold-400"
                            style={{ '--progress-duration': `${SLIDE_MS}ms`, animationPlayState: paused ? 'paused' : 'running' } as CSSProperties}
                            onAnimationEnd={() => go(index + 1)} />
                        ) : (
                          <span className={cx('absolute inset-0 origin-left rounded-full bg-gold-400 transition-transform duration-500', i === index ? 'scale-x-100' : 'scale-x-0')} />
                        )}
                      </span>
                    </button>
                  ))}
                </div>
                <div className="flex gap-2">
                  {([-1, 1] as const).map((dir) => (
                    <button key={dir} type="button" onClick={() => go(index + dir)} aria-label={dir === 1 ? 'Next seminar' : 'Previous seminar'}
                      className="flex size-10 items-center justify-center rounded-full bg-white/10 ring-1 ring-white/20 backdrop-blur transition hover:bg-gold-400 hover:text-navy-950">
                      <Icon name={dir === 1 ? 'arrow-right' : 'arrow-left'} className="size-4" strokeWidth={2} />
                    </button>
                  ))}
                </div>
              </div>
            )}
          </div>
        </Reveal>
      </div>
    </section>
  );
}
