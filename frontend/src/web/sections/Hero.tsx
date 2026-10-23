import { useCallback, useEffect, useRef, useState, type CSSProperties } from 'react';
import type { HeroSlide, HomeSection } from '@shared/types';
import { Icon } from '../components/Icon';
import { SmartLink } from '../components/SmartLink';
import { button, cx } from '../components/ui';
import { usePrefersReducedMotion } from '../lib/motion';
import { Backdrop } from './Backdrop';

const SLIDE_MS = 7000;

/** Words rise out of a mask one after another. */
function RisingWords({ text, delay = 0 }: { text: string; delay?: number }) {
  return (
    <>
      {text.split(' ').map((word, i) => (
        <span key={i} className="inline-block overflow-hidden pb-[0.12em] align-bottom">
          <span className="inline-block animate-rise" style={{ animationDelay: `${delay + i * 90}ms` }}>
            {word}&nbsp;
          </span>
        </span>
      ))}
    </>
  );
}

export function Hero({ section, overlapped = false }: { section: Extract<HomeSection, { key: 'hero' }>; overlapped?: boolean }) {
  const slides = section.items;
  const [index, setIndex] = useState(0);
  const [paused, setPaused] = useState(false);
  const reduced = usePrefersReducedMotion();
  const touchX = useRef<number | null>(null);
  const count = slides.length;

  const go = useCallback((next: number) => setIndex(((next % count) + count) % count), [count]);

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (!(e.target instanceof HTMLElement) || !e.target.closest('[data-hero]')) return;
      if (e.key === 'ArrowRight') go(index + 1);
      if (e.key === 'ArrowLeft') go(index - 1);
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [go, index]);

  if (count === 0) return null;
  const autoplay = count > 1 && !reduced;

  return (
    <section
      id="hero"
      data-hero
      aria-roledescription="carousel"
      aria-label="Highlights"
      className="relative isolate h-[68svh] max-h-[640px] min-h-[460px] overflow-hidden bg-navy-950 text-white"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
      onFocus={() => setPaused(true)}
      onBlur={() => setPaused(false)}
      onTouchStart={(e) => (touchX.current = e.touches[0].clientX)}
      onTouchEnd={(e) => {
        if (touchX.current === null) return;
        const dx = e.changedTouches[0].clientX - touchX.current;
        if (Math.abs(dx) > 50) go(index + (dx < 0 ? 1 : -1));
        touchX.current = null;
      }}
    >
      {slides.map((slide, i) => (
        <SlideBackground key={slide.id} slide={slide} variant={i} active={i === index} />
      ))}

      <div className={cx('container-page relative z-10 flex h-full flex-col justify-center pt-6', overlapped ? 'pb-32' : 'pb-20')}>
        {slides.map((slide, i) =>
          i === index ? (
            <div key={`${slide.id}-${i}`} className="max-w-3xl" aria-live={autoplay ? 'off' : 'polite'} role="group" aria-roledescription="slide" aria-label={`${i + 1} of ${count}`}>
              {slide.eyebrow && (
                <p className="eyebrow animate-fade-up text-gold-300" style={{ animationDelay: '150ms' }}>{slide.eyebrow}</p>
              )}
              <h1 className="mt-4 text-[2.2rem] font-semibold leading-[1.06] sm:text-5xl lg:text-[3.75rem]">
                <RisingWords text={slide.title} delay={250} />
              </h1>
              {slide.subtitle && (
                <p className="mt-5 max-w-xl animate-fade-up text-base leading-7 text-navy-100/90 sm:text-lg" style={{ animationDelay: '650ms' }}>
                  {slide.subtitle}
                </p>
              )}
              <div className="mt-8 flex animate-fade-up flex-wrap gap-3" style={{ animationDelay: '850ms' }}>
                {slide.button_text && (
                  <SmartLink href={slide.button_url} className={button.gold}>
                    {slide.button_text}
                    <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
                  </SmartLink>
                )}
                {section.button_text && (
                  <SmartLink href={section.button_url} className={button.ghostLight}>{section.button_text}</SmartLink>
                )}
              </div>
            </div>
          ) : null,
        )}
      </div>

      {/* Controls */}
      {count > 1 && (
        <div className="absolute inset-x-0 bottom-0 z-20">
          <div className={cx('container-page flex items-end justify-between gap-6', overlapped ? 'pb-20 sm:pb-24' : 'pb-6')}>
            <div className="flex items-center gap-5">
              <span className="font-display text-2xl font-semibold text-gold-300 tabular-nums">{String(index + 1).padStart(2, '0')}</span>
              <div className="flex gap-2">
                {slides.map((slide, i) => (
                  <button
                    key={slide.id}
                    type="button"
                    onClick={() => go(i)}
                    className="group relative h-8 w-10 sm:w-16"
                    aria-label={`Show slide ${i + 1}: ${slide.title}`}
                    aria-current={i === index}
                  >
                    <span className="absolute inset-x-0 top-1/2 h-0.5 -translate-y-1/2 overflow-hidden rounded-full bg-white/20 transition group-hover:bg-white/40">
                      {i === index && autoplay ? (
                        <span
                          key={`p-${index}`}
                          className="absolute inset-0 origin-left animate-progress rounded-full bg-gold-400"
                          style={{ '--progress-duration': `${SLIDE_MS}ms`, animationPlayState: paused ? 'paused' : 'running' } as CSSProperties}
                          onAnimationEnd={() => go(index + 1)}
                        />
                      ) : (
                        <span className={cx('absolute inset-0 rounded-full bg-gold-400 transition-transform duration-500', i < index || (i === index && !autoplay) ? 'scale-x-100' : 'scale-x-0', 'origin-left')} />
                      )}
                    </span>
                  </button>
                ))}
              </div>
              <span className="text-sm text-white/50 tabular-nums">/ {String(count).padStart(2, '0')}</span>
            </div>

            <div className="hidden gap-3 sm:flex">
              {(['prev', 'next'] as const).map((dir) => (
                <button
                  key={dir}
                  type="button"
                  onClick={() => go(index + (dir === 'next' ? 1 : -1))}
                  className="flex size-11 items-center justify-center rounded-full text-white ring-1 ring-white/25 backdrop-blur transition duration-300 hover:bg-gold-400 hover:text-navy-950 hover:ring-gold-400"
                  aria-label={dir === 'next' ? 'Next slide' : 'Previous slide'}
                >
                  <Icon name={dir === 'next' ? 'arrow-right' : 'arrow-left'} className="size-5" strokeWidth={1.75} />
                </button>
              ))}
            </div>
          </div>
        </div>
      )}

    </section>
  );
}

function SlideBackground({ slide, variant, active }: { slide: HeroSlide; variant: number; active: boolean }) {
  const [failed, setFailed] = useState(false);
  return (
    <div className={cx('absolute inset-0 transition-opacity duration-[1400ms] ease-out', active ? 'z-[1] opacity-100' : 'z-0 opacity-0')} aria-hidden="true">
      {slide.image_url && !failed ? (
        <>
          <img src={slide.image_url} alt="" onError={() => setFailed(true)} className={cx('absolute inset-0 size-full object-cover', active && 'animate-ken-burns')} key={active ? 'on' : 'off'} />
          <div className="absolute inset-0 bg-gradient-to-r from-navy-950/95 via-navy-950/70 to-navy-950/10" />
          <div className="absolute inset-0 bg-gradient-to-t from-navy-950/80 via-transparent to-transparent" />
        </>
      ) : (
        <Backdrop variant={variant} />
      )}
    </div>
  );
}
