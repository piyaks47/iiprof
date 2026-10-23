import { useCallback, useEffect, useMemo, useRef, useState, type CSSProperties, type PointerEvent as ReactPointerEvent } from 'react';
import { createPortal } from 'react-dom';
import { Link } from 'react-router';
import type { GalleryItem, HomeSection } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SectionHeading } from '../components/SectionHeading';
import { cx } from '../components/ui';
import { usePrefersReducedMotion, useScrollLock } from '../lib/motion';

export function Gallery({ section }: { section: Extract<HomeSection, { key: 'gallery' }> }) {
  const [empty, setEmpty] = useState(false);
  if (empty || section.items.length === 0) return null; // no photo could be loaded: leave the section out

  return (
    <section id="gallery" className="relative isolate overflow-hidden bg-navy-950 py-16 text-white sm:py-20">
      <div className="bg-lattice absolute inset-0 -z-10 opacity-40 [mask-image:radial-gradient(ellipse_at_center,black,transparent_70%)]" />
      <div className="container-page">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} dark />
          <Reveal delay={120} className="shrink-0">
            <Link to="/view-gallery" className="group inline-flex items-center gap-3 text-sm font-semibold uppercase tracking-[0.18em] text-gold-200 transition hover:text-white">
              View the full gallery
              <span className="flex size-9 items-center justify-center rounded-full ring-1 ring-gold-400/50 transition duration-500 ease-out-expo group-hover:translate-x-1 group-hover:bg-gold-400 group-hover:text-navy-950">
                <Icon name="arrow-right" className="size-4" strokeWidth={2} />
              </span>
            </Link>
          </Reveal>
        </div>
        <GalleryCarousel items={section.items} className="mt-10 sm:mt-12" onEmpty={() => setEmpty(true)} />
      </div>
    </section>
  );
}

const AUTOPLAY_MS = 4200;

/** Signed distance on a ring of `n` places, in (-n/2, n/2]. */
const wrap = (d: number, n: number) => (d > n / 2 ? d - n : d <= -n / 2 ? d + n : d);
/** Fewer photos than this are repeated so the ring always has neighbours on both sides. */
const MIN_RING = 7;

/**
 * 3D cover-flow carousel: the centred photo faces you, its neighbours turn away and recede.
 * Drag / swipe (with flick), arrow keys, click a neighbour to bring it forward, click the centre one to
 * open the viewer. Autoplays (shown as a filling ring on the next button) while visible and not hovered.
 */
export function GalleryCarousel({ items: all, className, onEmpty }: { items: GalleryItem[]; className?: string; onEmpty?: () => void }) {
  const [broken, setBroken] = useState<number[]>([]);
  const items = useMemo(() => all.filter((item) => !broken.includes(item.id)), [all, broken]);
  const count = items.length;
  const ring = useMemo(() => {
    if (count === 0) return [];
    const copies = count >= MIN_RING || count < 2 ? 1 : Math.ceil(MIN_RING / count);
    return Array.from({ length: count * copies }, (_, i) => ({ item: items[i % count], key: `${items[i % count].id}-${Math.floor(i / count)}` }));
  }, [items, count]);
  const n = ring.length;

  const [active, setActive] = useState(0);
  const [drag, setDrag] = useState(0); // in slides; positive = pulling towards the next photo
  const [dragging, setDragging] = useState(false);
  const [hovered, setHovered] = useState(false);
  const [visible, setVisible] = useState(false);
  const [open, setOpen] = useState<number | null>(null);
  const reduced = usePrefersReducedMotion();
  const stage = useRef<HTMLDivElement>(null);
  const moved = useRef(false);
  const settled = useRef(0); // index of the last rendered position, to spot photos wrapping round the ring

  const go = useCallback((to: number) => n && setActive(((to % n) + n) % n), [n]);

  useEffect(() => {
    settled.current = active;
  }, [active]);

  useEffect(() => {
    if (all.length > 0 && count === 0) onEmpty?.();
  }, [all.length, count, onEmpty]);

  // Keep the index valid when photos drop out (failed to load).
  useEffect(() => {
    if (active >= n && n > 0) setActive(active % n);
  }, [active, n]);

  // Autoplay only while the carousel is on screen.
  useEffect(() => {
    const el = stage.current;
    if (!el || typeof IntersectionObserver === 'undefined') return;
    const io = new IntersectionObserver(([entry]) => setVisible(entry.isIntersecting), { threshold: 0.35 });
    io.observe(el);
    return () => io.disconnect();
  }, []);

  const onPointerDown = (e: ReactPointerEvent<HTMLDivElement>) => {
    if (e.button !== 0 || n < 2) return;
    const startX = e.clientX;
    const startAt = performance.now();
    const width = (stage.current?.querySelector<HTMLElement>('[data-slide]')?.offsetWidth ?? 600) * 0.62;
    let offset = 0;
    moved.current = false;
    setDragging(true);

    const move = (ev: PointerEvent) => {
      const dx = ev.clientX - startX;
      if (Math.abs(dx) > 6) moved.current = true;
      offset = Math.max(-2.5, Math.min(2.5, -dx / width));
      setDrag(offset);
    };
    const up = (ev: PointerEvent) => {
      window.removeEventListener('pointermove', move);
      window.removeEventListener('pointerup', up);
      window.removeEventListener('pointercancel', up);
      const velocity = (ev.clientX - startX) / Math.max(1, performance.now() - startAt); // px per ms
      const flick = Math.abs(velocity) > 0.45 && Math.abs(offset) < 0.5 ? -Math.sign(velocity) : 0;
      setDragging(false);
      setDrag(0);
      setActive((a) => (((a + Math.round(offset) + flick) % n) + n) % n);
    };
    window.addEventListener('pointermove', move);
    window.addEventListener('pointerup', up);
    window.addEventListener('pointercancel', up);
  };

  // Gentle tilt of the centre photo towards the cursor.
  const onPointerMove = (e: ReactPointerEvent<HTMLDivElement>) => {
    if (dragging || reduced || e.pointerType !== 'mouse') return;
    const card = stage.current?.querySelector<HTMLElement>('[data-slide][data-active] [data-tilt]');
    if (!card) return;
    const r = card.getBoundingClientRect();
    const x = (e.clientX - r.left) / r.width - 0.5;
    const y = (e.clientY - r.top) / r.height - 0.5;
    card.style.setProperty('--rx', `${(-y * 6).toFixed(2)}deg`);
    card.style.setProperty('--ry', `${(x * 8).toFixed(2)}deg`);
    card.style.setProperty('--mx', `${((x + 0.5) * 100).toFixed(1)}%`);
    card.style.setProperty('--my', `${((y + 0.5) * 100).toFixed(1)}%`);
  };
  const resetTilt = () => stage.current?.querySelectorAll<HTMLElement>('[data-tilt]').forEach((el) => {
    el.style.removeProperty('--rx');
    el.style.removeProperty('--ry');
  });

  if (count === 0) return null;

  const real = active % count;
  const current = ring[active]?.item ?? items[0];
  const autoplay = n > 1 && !reduced && visible && !hovered && !dragging && open === null;

  return (
    <div className={cx('relative', className)}>
      {/* Ambient glow taken from the current photo. */}
      <div className="pointer-events-none absolute inset-x-0 -top-10 bottom-0 -z-10" aria-hidden="true">
        <img key={current.id} src={current.image_url} alt="" className="absolute left-1/2 top-1/2 h-[80%] w-[70%] -translate-x-1/2 -translate-y-1/2 animate-fade-in object-cover opacity-35 blur-3xl saturate-150" />
      </div>

      <div
        ref={stage}
        role="region"
        aria-roledescription="carousel"
        aria-label="Photo gallery"
        tabIndex={0}
        className="coverflow relative cursor-grab touch-pan-y select-none outline-none active:cursor-grabbing"
        onPointerDown={onPointerDown}
        onPointerMove={onPointerMove}
        onPointerEnter={() => setHovered(true)}
        onPointerLeave={() => {
          setHovered(false);
          resetTilt();
        }}
        onFocus={() => setHovered(true)}
        onBlur={() => setHovered(false)}
        onKeyDown={(e) => {
          if (e.key === 'ArrowRight') go(active + 1);
          else if (e.key === 'ArrowLeft') go(active - 1);
          else if (e.key === 'Enter') setOpen(real);
          else return;
          e.preventDefault();
        }}
      >
        {/* Sizer: gives the stage the height of one photo. */}
        <div className="coverflow-slide invisible" aria-hidden="true" />

        {ring.map(({ item, key }, i) => {
          const base = wrap(i - active, n);
          const jump = Math.abs(base - wrap(i - settled.current, n)) > 1.6; // wrapped round the ring → no animation
          const d = base - drag;
          const abs = Math.abs(d);
          const x = Math.sign(d) * (Math.min(abs, 1) * 62 + Math.max(abs - 1, 0) * 26);
          const style = {
            transform: `translate3d(${x}%, 0, ${-abs * 170}px) rotateY(${-Math.max(-1, Math.min(1, d)) * 34}deg) scale(${Math.max(0.6, 1 - abs * 0.08)})`,
            opacity: abs > 2.6 ? 0 : 1,
            zIndex: 100 - Math.round(abs * 10),
            transition: dragging || jump ? 'none' : undefined,
            pointerEvents: abs > 2.4 ? 'none' : undefined,
          } as CSSProperties;
          const isActive = i === active;

          return (
            <div key={key} data-slide="" data-active={isActive ? '' : undefined} className="coverflow-slide absolute inset-x-0 top-0" style={style}>
              <button
                type="button"
                data-tilt=""
                tabIndex={-1}
                aria-hidden={!isActive}
                aria-label={`Open photo${item.title ? `: ${item.title}` : ''}`}
                onClick={() => {
                  if (moved.current) return;
                  if (isActive) setOpen(real);
                  else go(i);
                }}
                className={cx('coverflow-card group relative block size-full overflow-hidden rounded-2xl bg-navy-900 ring-1 ring-white/10', isActive && 'is-active')}
              >
                <img
                  src={item.image_url}
                  alt={item.title ?? ''}
                  draggable={false}
                  loading={abs < 3 ? 'eager' : 'lazy'}
                  onError={() => setBroken((b) => (b.includes(item.id) ? b : [...b, item.id]))}
                  className="size-full object-cover transition duration-[1400ms] ease-out-expo group-hover:scale-[1.04]"
                />
                <span className="absolute inset-0 bg-navy-950 transition-opacity duration-700" style={{ opacity: Math.min(0.7, abs * 0.38) }} />
                <span className="coverflow-sheen absolute inset-0" />
                {isActive && (
                  <span className="absolute right-4 top-4 flex size-10 items-center justify-center rounded-full bg-navy-950/60 text-gold-200 opacity-0 ring-1 ring-white/20 backdrop-blur transition duration-500 group-hover:opacity-100">
                    <Icon name="magnifying-glass-plus" className="size-4" strokeWidth={2} />
                  </span>
                )}
              </button>
            </div>
          );
        })}
      </div>

      {/* Caption + controls */}
      <div className="relative z-[110] mt-8 grid items-center gap-5 sm:mt-10 sm:grid-cols-[1fr_auto_1fr]">
        <div className="flex items-baseline gap-3 font-display tabular-nums">
          <span className="text-3xl font-semibold text-gold-300">{String(real + 1).padStart(2, '0')}</span>
          <span className="text-sm text-white/40">/ {String(count).padStart(2, '0')}</span>
          <span className="relative ml-3 hidden h-px w-32 overflow-hidden bg-white/15 sm:block">
            <span className="absolute inset-y-0 left-0 bg-gold-400 transition-transform duration-700 ease-out-expo" style={{ width: `${100 / count}%`, transform: `translateX(${real * 100}%)` }} />
          </span>
        </div>

        <p key={`${current.id}-${active}`} className="line-clamp-2 min-h-[1.75rem] animate-fade-up text-center font-display text-lg italic text-white/90 sm:max-w-md sm:text-xl" aria-live="polite">
          {current.title}
        </p>

        {n > 1 && (
          <div className="flex items-center gap-3 sm:justify-end">
            <button type="button" onClick={() => go(active - 1)} aria-label="Previous photo"
              className="flex size-12 items-center justify-center rounded-full text-white ring-1 ring-white/20 transition duration-300 hover:bg-white/10 hover:ring-white/40">
              <Icon name="arrow-left" className="size-5" strokeWidth={1.75} />
            </button>
            <button type="button" onClick={() => go(active + 1)} aria-label="Next photo"
              className="relative flex size-12 items-center justify-center rounded-full bg-gold-400 text-navy-950 shadow-[0_10px_30px_-10px_rgb(209_170_90/0.8)] transition duration-300 hover:bg-gold-300">
              <Icon name="arrow-right" className="size-5" strokeWidth={2} />
              <svg viewBox="0 0 52 52" className="pointer-events-none absolute -inset-1 size-14 -rotate-90" aria-hidden="true">
                <circle cx="26" cy="26" r="25" fill="none" stroke="rgb(255 255 255 / 0.12)" strokeWidth="1.5" />
                {!reduced && (
                  <circle
                    key={active}
                    cx="26" cy="26" r="25" fill="none" stroke="var(--color-gold-200)" strokeWidth="1.5" strokeLinecap="round" pathLength={100}
                    className="ring-fill"
                    style={{ animationDuration: `${AUTOPLAY_MS}ms`, animationPlayState: autoplay ? 'running' : 'paused' }}
                    onAnimationEnd={() => go(active + 1)}
                  />
                )}
              </svg>
            </button>
          </div>
        )}
      </div>

      {open !== null && items[open] && <Lightbox items={items} index={open} onChange={setOpen} onClose={() => setOpen(null)} />}
    </div>
  );
}

/** Masonry grid with a full-screen viewer. Photos that fail to load are left out. */
export function GalleryGrid({ items: all, className }: { items: GalleryItem[]; className?: string }) {
  const [open, setOpen] = useState<number | null>(null);
  const [broken, setBroken] = useState<number[]>([]);
  const items = all.filter((item) => !broken.includes(item.id));

  return (
    <>
        <ul className={cx(className, 'gap-4 [&>li]:mb-4', items.length === 1 ? 'mx-auto max-w-3xl' : 'columns-2', items.length >= 3 && 'md:columns-3', items.length >= 8 && 'lg:columns-4')}>
          {items.map((item, i) => (
            <Reveal as="li" key={item.id} from="zoom" delay={(i % 4) * 90} className="break-inside-avoid">
              <button
                type="button"
                onClick={() => setOpen(i)}
                className="group relative block w-full overflow-hidden rounded-2xl bg-navy-100 shadow-[0_20px_50px_-30px_rgb(7_15_32/0.4)]"
                aria-label={`Open photo${item.title ? `: ${item.title}` : ` ${i + 1}`}`}
              >
                <img src={item.image_url} alt={item.title ?? ''} loading="lazy" onError={() => setBroken((b) => [...b, item.id])} className="w-full object-cover transition duration-[1200ms] ease-out-expo group-hover:scale-[1.07]" />
                <span className="absolute inset-0 flex items-end justify-between gap-3 bg-gradient-to-t from-navy-950/80 via-navy-950/10 to-transparent p-4 text-left text-sm font-medium text-white opacity-0 transition duration-500 group-hover:opacity-100 group-focus-visible:opacity-100">
                  <span className="line-clamp-2">{item.title}</span>
                  <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-gold-400 text-navy-950">
                    <Icon name="magnifying-glass-plus" className="size-4" strokeWidth={2} />
                  </span>
                </span>
              </button>
            </Reveal>
          ))}
        </ul>
      {open !== null && items[open] && <Lightbox items={items} index={open} onChange={setOpen} onClose={() => setOpen(null)} />}
    </>
  );
}

function Lightbox({ items, index, onChange, onClose }: { items: GalleryItem[]; index: number; onChange: (i: number) => void; onClose: () => void }) {
  useScrollLock(true);
  const step = useCallback((d: number) => onChange((index + d + items.length) % items.length), [index, items.length, onChange]);

  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
      if (e.key === 'ArrowRight') step(1);
      if (e.key === 'ArrowLeft') step(-1);
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [onClose, step]);

  const item = items[index];

  return createPortal(
    <div className="fixed inset-0 z-[80] flex animate-fade-in items-center justify-center bg-navy-950/95 p-4 backdrop-blur-md" role="dialog" aria-modal="true" aria-label="Photo viewer" onClick={onClose}>
      <figure key={item.id} className="relative max-h-full animate-pop" onClick={(e) => e.stopPropagation()}>
        <img src={item.image_url} alt={item.title ?? ''} className="max-h-[82vh] max-w-[92vw] rounded-xl object-contain shadow-2xl" />
        <figcaption className="mt-4 flex items-center justify-between gap-6 text-sm text-navy-200">
          <span className="font-display text-lg text-white">{item.title}</span>
          <span className="tabular-nums text-gold-300">{index + 1} / {items.length}</span>
        </figcaption>
      </figure>
      <button type="button" onClick={onClose} className="absolute right-5 top-5 rounded-full p-3 text-white ring-1 ring-white/20 transition hover:rotate-90 hover:bg-white/10" aria-label="Close" autoFocus>
        <Icon name="close" className="size-5" />
      </button>
      {items.length > 1 &&
        ([-1, 1] as const).map((d) => (
          <button
            key={d}
            type="button"
            onClick={(e) => {
              e.stopPropagation();
              step(d);
            }}
            className={`absolute top-1/2 flex size-12 -translate-y-1/2 items-center justify-center rounded-full text-white ring-1 ring-white/20 transition hover:bg-gold-400 hover:text-navy-950 ${d === 1 ? 'right-4 sm:right-8' : 'left-4 sm:left-8'}`}
            aria-label={d === 1 ? 'Next photo' : 'Previous photo'}
          >
            <Icon name={d === 1 ? 'arrow-right' : 'arrow-left'} className="size-5" />
          </button>
        ))}
    </div>,
    document.body,
  );
}
