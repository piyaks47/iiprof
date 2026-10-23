import { useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { Link, useLocation } from 'react-router';
import { hasGrandchildren } from '@shared/lib/menu';
import type { MenuItem, Site } from '@shared/types';
import { useScrollLock, useScrollY } from '../lib/motion';
import { Crest } from './Brand';
import { Icon } from './Icon';
import { SmartLink } from './SmartLink';
import { button, cx } from './ui';

const LOGIN_URL = '/admin/login';
const MORE_ID = -1;

export function Header({ site }: { site?: Site }) {
  const settings = site?.settings;
  const menu = site?.menu ?? [];
  const [stuck, setStuck] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);
  const sentinel = useRef<HTMLDivElement>(null);
  const location = useLocation();

  useEffect(() => setMobileOpen(false), [location.pathname]);

  useEffect(() => {
    const el = sentinel.current;
    if (!el || typeof IntersectionObserver === 'undefined') return;
    const io = new IntersectionObserver(([entry]) => setStuck(!entry.isIntersecting));
    io.observe(el);
    return () => io.disconnect();
  }, []);

  return (
    <>
      {/* Brand row — scrolls away */}
      <div className="relative bg-ivory-50">
        <div className="container-page flex items-center gap-5 py-4 sm:py-5">
          <Link to="/" className="group flex min-w-0 items-center gap-4" aria-label={`${settings?.site_name ?? 'Home'} — home`}>
            <Crest settings={settings} className="h-14 w-auto max-w-16 shrink-0 transition duration-500 group-hover:scale-105 sm:h-16" />
            <span className="min-w-0">
              {settings?.tagline && (
                <span className="block truncate text-[10px] font-bold uppercase tracking-[0.3em] text-gold-600 sm:text-[11px]">{settings.tagline}</span>
              )}
              <span className="mt-1 block font-display text-lg font-semibold leading-tight text-navy-950 sm:text-2xl lg:text-[1.7rem]">
                {settings?.site_name ?? ' '}
              </span>
            </span>
          </Link>

          <div className="ml-auto hidden items-center gap-3 md:flex">
            <a href={LOGIN_URL} className={cx(button.outline, button.small, 'px-5 py-2.5')}>
              <Icon name="login" className="size-4" /> Login
            </a>
            <SmartLink href="#register" className={cx(button.gold, button.small, 'px-5 py-2.5')}>
              Register
            </SmartLink>
          </div>

          <button
            type="button"
            className="ml-auto rounded-full p-2.5 text-navy-900 ring-1 ring-navy-900/10 md:hidden"
            onClick={() => setMobileOpen(true)}
            aria-expanded={mobileOpen}
            aria-controls="mobile-menu"
            aria-label="Open menu"
          >
            <Icon name="menu" className="size-6" />
          </button>
        </div>
        <div className="h-px bg-gradient-to-r from-transparent via-gold-400/60 to-transparent" />
      </div>

      <div ref={sentinel} aria-hidden="true" />

      {/* Navigation — sticks to the top */}
      <nav
        aria-label="Main"
        className={cx(
          'sticky top-0 z-40 hidden transition-[background-color,box-shadow] duration-500 md:block',
          stuck ? 'bg-navy-950/92 shadow-[0_12px_40px_-12px_rgb(7_15_32/0.55)] backdrop-blur-xl' : 'bg-navy-900',
        )}
      >
        <div className="container-page relative flex h-14 items-center">
          <Link
            to="/"
            aria-hidden={!stuck}
            tabIndex={stuck ? 0 : -1}
            className={cx('flex shrink-0 items-center overflow-hidden transition-all duration-500 ease-out-expo', stuck ? 'mr-4 w-9 opacity-100' : 'mr-0 w-0 opacity-0')}
          >
            <Crest settings={settings} className="h-9 w-auto max-w-9 shrink-0" />
          </Link>

          <PriorityNav menu={menu} />

          <div className={cx('ml-3 shrink-0 transition-all duration-500', stuck ? 'translate-y-0 opacity-100' : 'pointer-events-none -translate-y-2 opacity-0')}>
            <SmartLink href="#register" className={cx(button.gold, button.small)}>Register</SmartLink>
          </div>
        </div>
        <ScrollProgress />
      </nav>

      {/* Mobile: compact bar that slides in once the brand row has scrolled away */}
      <div
        className={cx(
          'fixed inset-x-0 top-0 z-40 flex h-14 items-center justify-between bg-navy-950/95 px-4 shadow-lg backdrop-blur-xl transition-transform duration-500 ease-out-expo md:hidden',
          stuck ? 'translate-y-0' : '-translate-y-full',
        )}
        inert={!stuck}
      >
        <Link to="/" className="flex items-center gap-2.5 text-white">
          <Crest settings={settings} className="h-8 w-auto max-w-8" />
          <span className="font-display text-sm font-semibold">{settings?.short_name || settings?.site_name}</span>
        </Link>
        <button type="button" className="rounded-full p-2 text-white ring-1 ring-white/15" onClick={() => setMobileOpen(true)} aria-label="Open menu" aria-controls="mobile-menu" aria-expanded={mobileOpen}>
          <Icon name="menu" className="size-5" />
        </button>
        <ScrollProgress />
      </div>

      <MobileMenu site={site} open={mobileOpen} onClose={() => setMobileOpen(false)} />
    </>
  );
}

/** Thin gold line showing how far down the page you are. */
function ScrollProgress() {
  const y = useScrollY();
  const max = typeof document === 'undefined' ? 1 : Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
  return (
    <span
      aria-hidden="true"
      className="pointer-events-none absolute inset-x-0 bottom-0 h-[2px] origin-left bg-gradient-to-r from-gold-500 via-gold-300 to-gold-500 transition-transform duration-150"
      style={{ transform: `scaleX(${Math.min(1, y / max)})` }}
    />
  );
}

function isCurrent(url: string | null, pathname: string) {
  if (!url || !url.startsWith('/')) return false;
  return url === '/' ? pathname === '/' : pathname === url || pathname.startsWith(url + '/');
}

function containsCurrent(item: MenuItem, pathname: string): boolean {
  return isCurrent(item.url, pathname) || item.children.some((c) => containsCurrent(c, pathname));
}

/**
 * Shows as many top-level items as fit; the rest move into "More" (merged with an existing "More" item).
 * Widths are measured from an invisible copy of the row, so nothing jumps.
 */
function PriorityNav({ menu }: { menu: MenuItem[] }) {
  const wrap = useRef<HTMLDivElement>(null);
  const measure = useRef<HTMLUListElement>(null);
  const moreItem = menu.find((m) => m.label.trim().toLowerCase() === 'more' && m.children.length > 0);
  const items = useMemo(() => menu.filter((m) => m !== moreItem), [menu, moreItem]);
  const [count, setCount] = useState(items.length);

  useLayoutEffect(() => {
    const calc = () => {
      if (!wrap.current || !measure.current) return;
      const available = wrap.current.clientWidth;
      const widths = [...measure.current.children].map((li) => li.getBoundingClientRect().width);
      const moreWidth = widths[widths.length - 1] ?? 80;
      const itemWidths = widths.slice(0, -1);
      const total = itemWidths.reduce((a, b) => a + b, 0);
      if (total + (moreItem ? moreWidth : 0) <= available) {
        setCount(items.length);
        return;
      }
      let used = moreWidth;
      let n = 0;
      for (const w of itemWidths) {
        if (used + w > available) break;
        used += w;
        n++;
      }
      setCount(Math.max(1, n));
    };
    calc();
    const ro = new ResizeObserver(calc);
    if (wrap.current) ro.observe(wrap.current);
    document.fonts?.ready.then(calc).catch(() => {});
    return () => ro.disconnect();
  }, [items, moreItem]);

  const visible = items.slice(0, count);
  const overflow = items.slice(count);
  const more: MenuItem | null =
    overflow.length || moreItem
      ? { id: MORE_ID, label: moreItem?.label ?? 'More', url: null, new_tab: false, image_url: moreItem?.image_url ?? null, children: [...overflow, ...(moreItem?.children ?? [])] }
      : null;

  return (
    // Not positioned: mega panels span the whole nav container.
    <div ref={wrap} className="min-w-0 flex-1">
      {/* Invisible copy used to measure every label (inside a zero-size box, so it never widens the page) */}
      <div aria-hidden="true" className="pointer-events-none invisible absolute left-0 top-0 -z-10 size-0 overflow-hidden">
      <ul ref={measure} className="absolute left-0 top-0 flex whitespace-nowrap">
        {[...items, { id: MORE_ID, label: moreItem?.label ?? 'More', url: null, new_tab: false, children: [{} as MenuItem] }].map((item) => (
          <li key={item.id} className={topLinkClass(false)}>
            {item.label}
            {item.children.length > 0 && <Icon name="chevron-down" className="size-3.5" strokeWidth={2} />}
            {item.new_tab && item.children.length === 0 && <Icon name="arrow-up-right" className="size-3" strokeWidth={2} />}
          </li>
        ))}
      </ul>
      </div>

      <ul className="flex items-stretch" data-nav="top">
        {visible.map((item) => (
          <TopItem key={item.id} item={item} />
        ))}
        {more && <TopItem item={more} />}
      </ul>
    </div>
  );
}

const topLinkClass = (active: boolean) =>
  cx(
    'relative flex h-14 cursor-pointer items-center gap-1 whitespace-nowrap px-2.5 text-[13.5px] font-medium tracking-wide transition-colors xl:px-3.5',
    active ? 'text-gold-300' : 'text-white/85 hover:text-white group-focus-within/top:text-white',
  );

function TopItem({ item }: { item: MenuItem }) {
  const { pathname } = useLocation();
  const active = containsCurrent(item, pathname);
  const hasChildren = item.children.length > 0;
  const mega = item.id === MORE_ID || hasGrandchildren(item);
  const ref = useRef<HTMLLIElement>(null);
  const [alignRight, setAlignRight] = useState(false);
  // Closed after a link inside was chosen (or Escape) — until the pointer comes back to the item or it gets focus again.
  // (The hidden panel no longer counts as part of the item, so the pointer has "left" as soon as it closes.)
  const [dismissed, setDismissed] = useState(false);
  const measure = () => {
    const rect = ref.current?.getBoundingClientRect();
    if (rect) setAlignRight(rect.left + 272 > window.innerWidth - 24);
  };
  const dismiss = () => {
    setDismissed(true);
    const focused = document.activeElement;
    if (focused instanceof HTMLElement && ref.current?.contains(focused)) focused.blur();
  };
  const onPick = (e: React.MouseEvent) => {
    if ((e.target as HTMLElement).closest('a, button')) dismiss();
  };

  // Navigating by any other means (back button, a link elsewhere) also closes it.
  useEffect(() => {
    const focused = document.activeElement;
    if (focused instanceof HTMLElement && ref.current?.contains(focused)) focused.blur();
  }, [pathname]);

  return (
    <li
      ref={ref}
      className={cx('group/top flex', !mega && 'relative')}
      onMouseEnter={() => { measure(); setDismissed(false); }}
      onFocus={() => { measure(); setDismissed(false); }}
      onKeyDown={(e) => e.key === 'Escape' && dismiss()}
    >
      <SmartLink href={item.url} fallback="button" newTab={item.new_tab} className={topLinkClass(active)} current={active} onClick={item.url ? dismiss : undefined}>
        {item.label}
        {hasChildren && <Icon name="chevron-down" className={cx('size-3.5 opacity-60 transition duration-300', !dismissed && 'group-hover/top:rotate-180 group-focus-within/top:rotate-180')} strokeWidth={2} />}
        {item.new_tab && !hasChildren && <Icon name="arrow-up-right" className="size-3 opacity-50" strokeWidth={2} />}
        <span
          className={cx(
            'absolute inset-x-2.5 bottom-3 h-px origin-left bg-gold-400 transition-transform duration-500 ease-out-expo xl:inset-x-3.5',
            active ? 'scale-x-100' : 'scale-x-0 group-hover/top:scale-x-100',
          )}
        />
      </SmartLink>

      {hasChildren &&
        (mega ? (
          <MegaPanel item={item} visibility={dismissed ? panelHidden : panelShow} onPick={onPick} pathname={pathname} />
        ) : (
          <div className={cx(dismissed ? panelHidden : panelShow, 'absolute top-full z-50 w-64', alignRight ? 'right-0' : 'left-0')} onClick={onPick}>
            <ul className="overflow-hidden rounded-b-2xl border-t-2 border-gold-400 bg-white pb-2 shadow-[0_24px_60px_-20px_rgb(7_15_32/0.45)] ring-1 ring-navy-900/5">
              {item.image_url ? (
                <li aria-hidden="true" className="relative mb-2 h-24 overflow-hidden">
                  <img src={item.image_url} alt="" loading="lazy" className="size-full object-cover transition duration-[1200ms] ease-out-expo group-hover/top:scale-105" />
                  <span className="absolute inset-0 bg-gradient-to-t from-navy-950/85 via-navy-950/30 to-transparent" />
                  <span className="absolute bottom-2.5 left-5 font-display text-lg font-semibold text-white">{item.label}</span>
                </li>
              ) : (
                <li aria-hidden="true" className="h-2" />
              )}
              {item.children.map((child) => {
                const here = containsCurrent(child, pathname);
                return (
                  <li key={child.id}>
                    <SmartLink href={child.url} newTab={child.new_tab} current={here}
                      className={cx('block px-5 py-2.5 text-[13.5px] transition-all duration-300 hover:bg-ivory-100 hover:pl-6 hover:text-gold-700', here ? 'bg-ivory-100 font-semibold text-gold-700' : 'text-navy-800')}>
                      {child.label}
                    </SmartLink>
                  </li>
                );
              })}
            </ul>
          </div>
        ))}
    </li>
  );
}

/** A panel that was just used: hidden even while the pointer is still over it. */
const panelHidden = 'invisible translate-y-3 opacity-0 transition-all duration-200 ease-out';

const panelShow =
  'invisible translate-y-3 opacity-0 transition-all duration-300 ease-out-expo group-focus-within/top:visible group-focus-within/top:translate-y-0 group-focus-within/top:opacity-100 group-hover/top:visible group-hover/top:translate-y-0 group-hover/top:opacity-100 group-hover/top:delay-75';

/**
 * Full-width panel with one column per sub-section (Khazana → Lender, Borrower, Depositor, Fund Manager …).
 * The side panel shows the featured image of the section; pointing at a column shows that column's image.
 */
function MegaPanel({ item, visibility, onPick, pathname }: { item: MenuItem; visibility: string; onPick: (e: React.MouseEvent) => void; pathname: string }) {
  const groups = item.children.filter((c) => c.children.length > 0);
  const singles = item.children.filter((c) => c.children.length === 0);
  const columns = [...(singles.length ? [{ heading: null as MenuItem | null, items: singles }] : []), ...groups.map((g) => ({ heading: g, items: g.children }))];
  const [focus, setFocus] = useState<MenuItem | null>(null);
  const shown = focus ?? item;
  const image = shown.image_url || item.image_url;

  return (
    <div className={cx(visibility, 'absolute inset-x-0 top-full z-50 px-4 sm:px-6 lg:px-10')} onMouseLeave={() => setFocus(null)} onClick={onPick}>
      <div className="grid overflow-hidden rounded-b-2xl border-t-2 border-gold-400 bg-white shadow-[0_40px_90px_-30px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/5 lg:grid-cols-[17rem_1fr]">
        <div className="relative hidden min-h-64 overflow-hidden bg-navy-950 p-7 text-white lg:flex lg:flex-col lg:justify-end">
          {image ? (
            <img key={image} src={image} alt="" loading="lazy" className="absolute inset-0 size-full animate-fade-in object-cover" />
          ) : (
            <div className="bg-lattice absolute inset-0 opacity-60" />
          )}
          <div className="absolute inset-0 bg-gradient-to-t from-navy-950 via-navy-950/70 to-navy-950/10" />
          <div className="absolute -bottom-16 -right-16 size-48 rounded-full bg-gold-400/20 blur-3xl" />
          <p className="eyebrow relative text-gold-300">{shown === item ? 'Explore' : item.label}</p>
          <p className="relative mt-3 font-display text-3xl font-semibold leading-tight">{shown.label}</p>
          <p className="relative mt-2 text-sm leading-6 text-navy-200">
            {shown === item
              ? `${item.children.length} ${item.children.length === 1 ? 'section' : 'sections'} · ${countLinks(item)} links`
              : `${shown.children.length} ${shown.children.length === 1 ? 'link' : 'links'}`}
          </p>
        </div>
        <div className="grid max-h-[70vh] gap-x-8 gap-y-6 overflow-y-auto p-7 [grid-template-columns:repeat(auto-fill,minmax(11.5rem,1fr))]">
          {columns.map((col, i) => (
            <div key={col.heading?.id ?? `singles-${i}`} className="min-w-0" onMouseEnter={() => setFocus(col.heading)} onFocus={() => setFocus(col.heading)}>
              {col.heading ? (
                <SmartLink href={col.heading.url} newTab={col.heading.new_tab} current={containsCurrent(col.heading, pathname)}
                  className={cx('mb-3 flex items-center gap-2 border-b pb-2 text-[11px] font-bold uppercase tracking-[0.18em] text-gold-700', containsCurrent(col.heading, pathname) ? 'border-gold-400' : 'border-ivory-200')}>
                  {col.heading.label}
                </SmartLink>
              ) : (
                <p className="mb-3 border-b border-ivory-200 pb-2 text-[11px] font-bold uppercase tracking-[0.18em] text-gold-700">{item.id === MORE_ID ? 'More' : 'Overview'}</p>
              )}
              <ul className="space-y-0.5">
                {col.items.map((link) => (
                  <li key={link.id} onMouseEnter={() => !col.heading && link.image_url && setFocus(link)}>
                    <SmartLink href={link.url} newTab={link.new_tab} current={isCurrent(link.url, pathname)}
                      className={cx('group/link flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-[13.5px] transition hover:bg-ivory-100 hover:text-gold-700', containsCurrent(link, pathname) ? 'bg-ivory-100 font-semibold text-gold-700' : 'text-navy-800')}>
                      <span className="transition duration-300 group-hover/link:translate-x-0.5">{link.label}</span>
                      {link.new_tab ? (
                        <Icon name="arrow-up-right" className="size-3 text-navy-300" strokeWidth={2} />
                      ) : (
                        <Icon name="arrow-right" className="size-3 -translate-x-1 text-gold-500 opacity-0 transition duration-300 group-hover/link:translate-x-0 group-hover/link:opacity-100" strokeWidth={2} />
                      )}
                    </SmartLink>
                    {link.children.length > 0 && (
                      <ul className="mb-1 ml-2 border-l border-gold-400/30 pl-2">
                        {link.children.map((leaf) => (
                          <li key={leaf.id}>
                            <SmartLink href={leaf.url} newTab={leaf.new_tab} current={isCurrent(leaf.url, pathname)}
                              className={cx('block rounded-md px-2 py-1 text-[12.5px] transition hover:bg-ivory-100 hover:text-gold-700', isCurrent(leaf.url, pathname) ? 'font-semibold text-gold-700' : 'text-navy-600')}>
                              {leaf.label}
                            </SmartLink>
                          </li>
                        ))}
                      </ul>
                    )}
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

function countLinks(item: MenuItem): number {
  return item.children.reduce((n, c) => n + (c.url ? 1 : 0) + countLinks(c), 0);
}

function MobileMenu({ site, open, onClose }: { site?: Site; open: boolean; onClose: () => void }) {
  useScrollLock(open);

  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && onClose();
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [open, onClose]);

  return (
    <div className={cx('fixed inset-0 z-[55] md:hidden', open ? '' : 'pointer-events-none')} aria-hidden={!open}>
      <div className={cx('absolute inset-0 bg-navy-950/60 backdrop-blur-sm transition-opacity duration-500', open ? 'opacity-100' : 'opacity-0')} onClick={onClose} />
      <div
        id="mobile-menu"
        inert={!open}
        className={cx(
          'absolute inset-y-0 right-0 flex w-[88%] max-w-sm flex-col bg-navy-950 text-white shadow-2xl transition-transform duration-700 ease-out-expo',
          open ? 'translate-x-0' : 'translate-x-full',
        )}
      >
        <div className="flex items-center justify-between border-b border-white/10 px-5 py-4">
          <span className="flex items-center gap-3">
            <Crest settings={site?.settings} className="h-9 w-auto max-w-9" />
            <span className="font-display text-base font-semibold">{site?.settings.short_name || site?.settings.site_name}</span>
          </span>
          <button type="button" onClick={onClose} className="rounded-full p-2 ring-1 ring-white/15 transition hover:rotate-90" aria-label="Close menu">
            <Icon name="close" className="size-5" />
          </button>
        </div>
        <ul className="flex-1 overflow-y-auto overscroll-contain px-3 py-4">
          {(site?.menu ?? []).map((item, i) => (
            <MobileItem key={item.id} item={item} depth={0} onNavigate={onClose} index={i} open={open} />
          ))}
        </ul>
        <div className="grid grid-cols-2 gap-3 border-t border-white/10 p-5">
          <a href={LOGIN_URL} className={cx(button.ghostLight, button.small, 'py-3')}>Login</a>
          <SmartLink href="#register" onClick={onClose} className={cx(button.gold, button.small, 'py-3')}>Register</SmartLink>
        </div>
      </div>
    </div>
  );
}

function MobileItem({ item, depth, onNavigate, index, open }: { item: MenuItem; depth: number; onNavigate: () => void; index: number; open: boolean }) {
  const { pathname } = useLocation();
  const here = containsCurrent(item, pathname);
  const [expanded, setExpanded] = useState(false);
  const hasChildren = item.children.length > 0;
  const toggleOnly = !item.url && hasChildren;

  // Each time the menu opens, the branch of the page being viewed is unfolded (and the rest folded).
  useEffect(() => {
    if (open) setExpanded(hasChildren && here);
  }, [open, hasChildren, here]);

  return (
    <li
      className={cx(depth === 0 && 'transition-all duration-700 ease-out-expo', depth === 0 && (open ? 'translate-x-0 opacity-100' : 'translate-x-6 opacity-0'))}
      style={depth === 0 ? { transitionDelay: open ? `${100 + Math.min(index, 12) * 35}ms` : '0ms' } : undefined}
    >
      <div className="flex items-center">
        {toggleOnly ? (
          <button
            type="button"
            onClick={() => setExpanded((v) => !v)}
            className={cx('flex flex-1 items-center gap-3 rounded-lg px-3 py-3 text-left transition hover:bg-white/5', depth === 0 ? 'font-display text-lg' : 'text-[15px] text-navy-200', here && 'text-gold-300')}
            aria-expanded={expanded}
          >
            {depth === 0 && <Thumb src={item.image_url} />}
            {item.label}
          </button>
        ) : (
          <SmartLink
            href={item.url}
            newTab={item.new_tab}
            onClick={onNavigate}
            current={isCurrent(item.url, pathname)}
            className={cx('flex flex-1 items-center gap-3 rounded-lg px-3 py-3 transition hover:bg-white/5', depth === 0 ? 'font-display text-lg' : 'text-[15px] text-navy-200', here && 'text-gold-300', isCurrent(item.url, pathname) && 'bg-white/5 font-semibold')}
          >
            {depth === 0 && <Thumb src={item.image_url} />}
            {item.label}
          </SmartLink>
        )}
        {hasChildren && (
          <button
            type="button"
            onClick={() => setExpanded((v) => !v)}
            className="rounded-lg p-3 text-gold-300 hover:bg-white/5"
            aria-expanded={expanded}
            aria-label={`${expanded ? 'Collapse' : 'Expand'} ${item.label}`}
          >
            <Icon name="chevron-down" className={cx('size-4 transition duration-300', expanded && 'rotate-180')} strokeWidth={2} />
          </button>
        )}
      </div>
      {hasChildren && (
        <div className={cx('grid transition-[grid-template-rows] duration-500 ease-out-expo', expanded ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]')}>
          <ul className="overflow-hidden border-l border-gold-400/30 pl-3" style={{ marginLeft: depth === 0 ? '0.75rem' : '0.5rem' }}>
            {item.children.map((child, i) => (
              <MobileItem key={child.id} item={child} depth={depth + 1} onNavigate={onNavigate} index={i} open={open} />
            ))}
          </ul>
        </div>
      )}
    </li>
  );
}

/** Small square photo in front of the top-level items of the mobile menu. */
function Thumb({ src }: { src?: string | null }) {
  return src ? (
    <img src={src} alt="" loading="lazy" className="size-9 shrink-0 rounded-lg object-cover ring-1 ring-white/15" />
  ) : (
    <span aria-hidden="true" className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-white/5 ring-1 ring-white/10">
      <span className="size-1.5 rounded-full bg-gold-400" />
    </span>
  );
}
