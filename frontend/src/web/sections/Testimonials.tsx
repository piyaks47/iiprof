import { useCallback, useState, type CSSProperties } from 'react';
import { createPortal } from 'react-dom';
import { useTestimonials } from '@shared/hooks/queries';
import type { HomeSectionOf, Testimonial } from '@shared/types';
import { initialsOf } from '../components/Brand';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { Spotlight } from '../components/Spotlight';
import { button, cx } from '../components/ui';
import { usePrefersReducedMotion, useScrollLock } from '../lib/motion';

const SLIDE_MS = 9000;
const hasVideo = (t: Testimonial) => Boolean(t.video_embed_url || t.video_file_url);

/** /testimonials page: every quote in a masonry wall. */
export function TestimonialsGrid() {
  const { data = [], isPending } = useTestimonials();
  const [video, setVideo] = useState<Testimonial | null>(null);

  if (isPending) return <div className="h-64 animate-pulse rounded-2xl bg-ivory-200" aria-busy="true" />;
  if (data.length === 0) return <p className="text-navy-500">Testimonials will appear here soon.</p>;

  return (
    <>
      <ul className="columns-1 gap-6 sm:columns-2 lg:columns-3">
        {data.map((t, i) => (
          <Reveal as="li" key={t.id} delay={(i % 3) * 100} className="mb-6 break-inside-avoid">
            <TestimonialCard t={t} dark={i % 5 === 1} onPlay={() => setVideo(t)} />
          </Reveal>
        ))}
      </ul>
      {video && hasVideo(video) && <VideoModal title={video.name} src={video.video_embed_url} file={video.video_file_url} onClose={() => setVideo(null)} />}
    </>
  );
}

/**
 * Homepage block (Homepage → Testimonials; the quotes are under Website → Testimonials):
 * one quote in the spotlight, the people beside it to pick from, moving on by itself.
 */
export function HomeTestimonials({ section }: { section: HomeSectionOf<'testimonials'> }) {
  const items = section.items.slice(0, 8);
  const count = items.length;
  const [index, setIndex] = useState(0);
  const [paused, setPaused] = useState(false);
  const [video, setVideo] = useState<Testimonial | null>(null);
  const reduced = usePrefersReducedMotion();
  const go = useCallback((next: number) => setIndex(((next % count) + count) % count), [count]);

  if (count === 0) return null;
  const t = items[index];
  const autoplay = count > 1 && !reduced && !video;
  const long = (t.quote ?? '').length > 300;

  return (
    <section id="testimonials" className="relative overflow-hidden py-16 sm:py-20">
      <div className="pointer-events-none absolute -left-40 bottom-0 -z-10 size-[30rem] rounded-full bg-gold-200/30 blur-3xl" />
      <div className="container-page">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} />
          {section.button_text && (
            <Reveal delay={150} className="shrink-0">
              <SmartLink href={section.button_url} className={button.outline}>
                {section.button_text}
                <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
              </SmartLink>
            </Reveal>
          )}
        </div>

        <Reveal delay={100} className="mt-10">
          <div
            role="region"
            aria-roledescription="carousel"
            aria-label={section.title ?? 'Testimonials'}
            className="grid gap-5 lg:grid-cols-12 lg:gap-6"
            onMouseEnter={() => setPaused(true)}
            onMouseLeave={() => setPaused(false)}
            onFocus={() => setPaused(true)}
            onBlur={() => setPaused(false)}
            onKeyDown={(e) => {
              if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); go(index + 1); }
              if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); go(index - 1); }
            }}
          >
            {/* The quote in the spotlight */}
            <figure className="relative isolate flex min-h-[22rem] flex-col overflow-hidden rounded-3xl bg-white p-8 shadow-[0_34px_70px_-42px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/5 sm:p-10 lg:col-span-7 lg:p-12">
              <svg viewBox="0 0 64 48" className="absolute right-8 top-8 -z-10 h-24 w-32 text-ivory-100" aria-hidden="true">
                <path fill="currentColor" d={QUOTE} />
              </svg>
              <svg viewBox="0 0 64 48" className="h-9 w-12 text-gold-400" aria-hidden="true"><path fill="currentColor" d={QUOTE} /></svg>

              <div key={t.id} className="flex flex-1 animate-fade-up flex-col" aria-live={autoplay && !paused ? 'off' : 'polite'}>
                <blockquote className={cx('mt-6 flex-1 font-display italic text-navy-900', long ? 'text-lg leading-8 sm:text-xl sm:leading-9' : 'text-xl leading-9 sm:text-[1.7rem] sm:leading-[1.45]')}>
                  {t.quote || (hasVideo(t) ? `Watch ${t.name.split(' ')[0]}’s story.` : '')}
                </blockquote>
                <figcaption className="mt-8 flex items-center gap-4">
                  <Avatar t={t} className="size-16" />
                  <div className="min-w-0 flex-1">
                    <p className="font-display text-xl font-semibold text-navy-950">{t.name}</p>
                    {t.role && <p className="text-xs font-semibold uppercase tracking-[0.14em] text-gold-700">{t.role}</p>}
                  </div>
                  {hasVideo(t) && <PlayButton name={t.name} onClick={() => setVideo(t)} />}
                </figcaption>
              </div>

              {count > 1 && (
                <div className="mt-8 flex items-center gap-4 border-t border-ivory-200 pt-5">
                  <span className="font-display text-lg font-semibold tabular-nums text-navy-950">{String(index + 1).padStart(2, '0')}<span className="text-navy-300"> / {String(count).padStart(2, '0')}</span></span>
                  <span className="relative h-0.5 flex-1 overflow-hidden rounded-full bg-ivory-200">
                    {autoplay ? (
                      <span key={`p-${index}`} className="absolute inset-0 origin-left animate-progress rounded-full bg-gold-500"
                        style={{ '--progress-duration': `${SLIDE_MS}ms`, animationPlayState: paused ? 'paused' : 'running' } as CSSProperties}
                        onAnimationEnd={() => go(index + 1)} />
                    ) : (
                      <span className="absolute inset-0 origin-left rounded-full bg-gold-500 transition-transform duration-500" style={{ transform: `scaleX(${(index + 1) / count})` }} />
                    )}
                  </span>
                  <div className="flex gap-2">
                    {([-1, 1] as const).map((dir) => (
                      <button key={dir} type="button" onClick={() => go(index + dir)} aria-label={dir === 1 ? 'Next testimonial' : 'Previous testimonial'}
                        className="flex size-10 items-center justify-center rounded-full text-navy-900 ring-1 ring-navy-900/15 transition hover:bg-navy-950 hover:text-gold-300">
                        <Icon name={dir === 1 ? 'arrow-right' : 'arrow-left'} className="size-4" strokeWidth={2} />
                      </button>
                    ))}
                  </div>
                </div>
              )}
            </figure>

            {/* The people to pick from */}
            {count > 1 && (
              <ul className="scrollbar-none -mx-4 flex gap-3 overflow-x-auto px-4 pb-1 lg:col-span-5 lg:mx-0 lg:flex-col lg:gap-2 lg:overflow-visible lg:px-0" aria-label="Choose a testimonial">
                {items.map((p, i) => {
                  const active = i === index;
                  return (
                    <li key={p.id} className="shrink-0 lg:shrink">
                      <button type="button" onClick={() => go(i)} aria-current={active}
                        className={cx(
                          'group flex w-64 items-center gap-4 rounded-2xl p-3 text-left transition duration-300 lg:w-full lg:p-4',
                          active ? 'bg-navy-950 text-white shadow-[0_24px_50px_-30px_rgb(7_15_32/0.7)]' : 'bg-white/70 ring-1 ring-navy-900/5 hover:bg-white hover:shadow-md',
                        )}>
                        <Avatar t={p} className={cx('size-12 transition', active ? 'ring-gold-400' : 'grayscale-[40%] group-hover:grayscale-0')} />
                        <span className="min-w-0 flex-1">
                          <span className={cx('block truncate font-display text-[17px] font-semibold', active ? 'text-white' : 'text-navy-950')}>{p.name}</span>
                          <span className={cx('mt-0.5 block truncate text-[12.5px]', active ? 'text-gold-300' : 'text-navy-500')}>{p.role || (p.quote ? p.quote.slice(0, 60) : 'Video story')}</span>
                        </span>
                        {hasVideo(p) && <span className={cx('shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.12em]', active ? 'bg-gold-400 text-navy-950' : 'bg-ivory-100 text-navy-500')}>Video</span>}
                        <Icon name="chevron-right" className={cx('hidden size-4 shrink-0 transition lg:block', active ? 'translate-x-0 text-gold-300' : '-translate-x-1 text-navy-300 opacity-0 group-hover:translate-x-0 group-hover:opacity-100')} strokeWidth={2} />
                      </button>
                    </li>
                  );
                })}
              </ul>
            )}
          </div>
        </Reveal>
      </div>
      {video && hasVideo(video) && <VideoModal title={video.name} src={video.video_embed_url} file={video.video_file_url} onClose={() => setVideo(null)} />}
    </section>
  );
}

const QUOTE = 'M0 48V28C0 12.5 8.2 3.2 24.6 0l2.6 5.5C18.4 8.4 14 13.6 13.4 21H26v27H0Zm37.6 0V28c0-15.5 8.2-24.8 24.6-28l2.6 5.5C56 8.4 51.6 13.6 51 21h12.6v27H37.6Z';

function Avatar({ t, className }: { t: Testimonial; className?: string }) {
  return (
    <span className={cx('shrink-0 overflow-hidden rounded-full ring-2 ring-gold-400/60 ring-offset-2 ring-offset-transparent', className)}>
      <SafeImg src={t.photo_url ?? undefined} alt="" loading="lazy" decoding="async" className="size-full object-cover"
        fallback={<span className="flex size-full items-center justify-center bg-navy-900 font-display text-lg text-gold-300">{initialsOf(t.name).slice(0, 2)}</span>} />
    </span>
  );
}

function PlayButton({ name, onClick, light = false }: { name: string; onClick: () => void; light?: boolean }) {
  return (
    <button type="button" onClick={onClick} aria-label={`Play ${name}’s video`}
      className={cx('relative flex size-12 shrink-0 items-center justify-center rounded-full ring-4 ring-gold-400/20 transition hover:scale-105 hover:ring-gold-400/40', light ? 'bg-gold-400 text-navy-950' : 'bg-navy-950 text-gold-300 hover:bg-gold-400 hover:text-navy-950')}>
      <svg viewBox="0 0 24 24" className="relative ml-0.5 size-5" fill="currentColor" aria-hidden="true"><path d="M8 5.5v13l11-6.5-11-6.5Z" /></svg>
    </button>
  );
}

function TestimonialCard({ t, onPlay, dark = false }: { t: Testimonial; onPlay: () => void; dark?: boolean }) {
  return (
    <Spotlight className={cx(
      'relative isolate flex flex-col overflow-hidden rounded-2xl p-8 transition duration-500 hover:-translate-y-1',
      dark ? 'bg-navy-950 text-white shadow-[0_30px_60px_-34px_rgb(7_15_32/0.7)]' : 'bg-white shadow-[0_24px_50px_-34px_rgb(7_15_32/0.45)] ring-1 ring-navy-900/5',
    )}>
      <svg viewBox="0 0 64 48" className={cx('absolute right-6 top-6 -z-10 h-14 w-20', dark ? 'text-white/[0.04]' : 'text-ivory-100')} aria-hidden="true"><path fill="currentColor" d={QUOTE} /></svg>
      <svg viewBox="0 0 64 48" className="h-7 w-10 text-gold-400" aria-hidden="true"><path fill="currentColor" d={QUOTE} /></svg>
      {t.quote ? (
        <blockquote className={cx('mt-5 font-display text-lg italic leading-8', dark ? 'text-navy-50' : 'text-navy-900')}>{t.quote}</blockquote>
      ) : hasVideo(t) ? (
        <p className={cx('mt-5 font-display text-lg italic leading-8', dark ? 'text-navy-200' : 'text-navy-500')}>Watch {t.name.split(' ')[0]}’s story.</p>
      ) : null}
      <div className={cx('mt-7 flex items-center gap-4 border-t pt-5', dark ? 'border-white/10' : 'border-ivory-200')}>
        <Avatar t={t} className="size-14" />
        <div className="min-w-0 flex-1">
          <p className={cx('font-display text-lg font-semibold', dark ? 'text-white' : 'text-navy-950')}>{t.name}</p>
          {t.role && <p className={cx('text-xs font-semibold uppercase tracking-[0.14em]', dark ? 'text-gold-300' : 'text-gold-700')}>{t.role}</p>}
        </div>
        {hasVideo(t) && <PlayButton name={t.name} onClick={onPlay} light={dark} />}
      </div>
    </Spotlight>
  );
}

function VideoModal({ title, src, file, onClose }: { title: string; src: string | null; file: string | null; onClose: () => void }) {
  useScrollLock(true);
  return createPortal(
    <div className="fixed inset-0 z-[80] flex animate-fade-in items-center justify-center bg-navy-950/95 p-4 backdrop-blur-md" role="dialog" aria-modal="true" aria-label={title} onClick={onClose}
      onKeyDown={(e) => e.key === 'Escape' && onClose()}>
      <div className="relative aspect-video w-full max-w-4xl animate-pop overflow-hidden rounded-2xl shadow-2xl ring-1 ring-white/10" onClick={(e) => e.stopPropagation()}>
        {src ? (
          <iframe src={`${src}?autoplay=1&rel=0`} title={title} className="size-full" allow="autoplay; encrypted-media; picture-in-picture" allowFullScreen />
        ) : (
          <video src={file ?? undefined} title={title} className="size-full bg-black" controls autoPlay playsInline />
        )}
      </div>
      <button type="button" onClick={onClose} className="absolute right-5 top-5 rounded-full p-3 text-white ring-1 ring-white/20 transition hover:rotate-90 hover:bg-white/10" aria-label="Close" autoFocus>
        <Icon name="close" className="size-5" />
      </button>
    </div>,
    document.body,
  );
}
