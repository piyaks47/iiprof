import type { ReactNode } from 'react';
import { Link } from 'react-router';
import { usePageByPath, useSite } from '@shared/hooks/queries';
import type { Page } from '@shared/types';
import { Backdrop } from '../sections/Backdrop';
import { Icon } from './Icon';
import { SafeImg } from './SafeImg';
import { SmartLink } from './SmartLink';
import { button as btn } from './ui';

export interface Crumb {
  label: string;
  to?: string | null;
}

export interface BannerButton {
  text: string;
  url: string;
}

export interface PageHeroProps {
  title: string;
  /** Excerpt: short italic lead under the title. */
  intro?: string | null;
  /** Description: one or more paragraphs under the lead. */
  text?: string | null;
  button?: BannerButton | null;
  crumbs?: Crumb[];
  /** Banner image; falls back to Settings → Default page banner. */
  image?: string | null;
  /** Skip the default banner (e.g. events with their own cover missing). */
  noDefaultImage?: boolean;
  children?: ReactNode;
}

/** Banner fields of a page, ready for <PageHero>. */
export function bannerOf(page: Pick<Page, 'banner' | 'excerpt'>): Pick<PageHeroProps, 'title' | 'intro' | 'text' | 'button' | 'image'> {
  const { banner } = page;
  return {
    title: banner.title,
    intro: page.excerpt,
    text: banner.text,
    button: banner.button_text && banner.button_url ? { text: banner.button_text, url: banner.button_url } : null,
    image: banner.image_url,
  };
}

/**
 * Banner at the top of inner pages: image, breadcrumb, title, excerpt, description and an optional
 * button — all edited per page under Website → Pages → Banner.
 */
export function PageHero({ title, intro, text, button, crumbs = [], image, noDefaultImage = false, children }: PageHeroProps) {
  const { data: site } = useSite();
  const src = image || (noDefaultImage ? null : site?.settings.default_banner_url);
  const paragraphs = (text ?? '').split(/\n\s*\n|\n/).map((p) => p.trim()).filter(Boolean);

  return (
    <header className="relative isolate overflow-hidden bg-navy-950 text-white">
      <Backdrop variant={0} className="-z-10" />
      {src && (
        <>
          <SafeImg key={src} src={src} alt="" className="absolute inset-0 -z-10 size-full animate-ken-burns object-cover opacity-45" />
          <div className="absolute inset-0 -z-10 bg-gradient-to-r from-navy-950 via-navy-950/85 to-navy-950/25" />
          <div className="absolute inset-x-0 bottom-0 -z-10 h-1/2 bg-gradient-to-t from-navy-950/80 to-transparent" />
        </>
      )}

      <div className="container-page relative py-14 sm:py-20 lg:py-24">
        <nav aria-label="Breadcrumb" className="animate-fade-up text-[13px] text-navy-300">
          <ol className="flex flex-wrap items-center gap-2">
            <li><Link to="/" className="transition hover:text-gold-300">Home</Link></li>
            {crumbs.map((c, i) => (
              <li key={i} className="flex items-center gap-2">
                <span className="text-gold-500/60">/</span>
                {c.to ? <Link to={c.to} className="transition hover:text-gold-300">{c.label}</Link> : <span className={i === crumbs.length - 1 ? 'text-white' : ''}>{c.label}</span>}
              </li>
            ))}
          </ol>
        </nav>

        <h1 className="mt-5 max-w-4xl animate-fade-up text-4xl font-semibold leading-[1.08] [animation-delay:100ms] sm:text-5xl lg:text-[3.5rem]">{title}</h1>

        {intro && (
          <p className="mt-5 flex max-w-2xl animate-fade-up gap-4 font-display text-xl italic leading-snug text-gold-100/95 [animation-delay:200ms] sm:text-2xl">
            <span aria-hidden="true" className="mt-3.5 h-px w-8 shrink-0 bg-gold-400" />
            <span>{intro}</span>
          </p>
        )}

        {paragraphs.length > 0 && (
          <div className="mt-4 max-w-2xl animate-fade-up space-y-3 text-[15px] leading-7 text-navy-200 [animation-delay:280ms] sm:text-base">
            {paragraphs.map((p, i) => <p key={i}>{p}</p>)}
          </div>
        )}

        {(button || children) && (
          <div className="mt-8 flex animate-fade-up flex-wrap items-center gap-3 [animation-delay:360ms]">
            {button && (
              <SmartLink href={button.url} className={btn.gold}>
                {button.text} <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
              </SmartLink>
            )}
            {children}
          </div>
        )}
      </div>
      <div className="h-px bg-gradient-to-r from-transparent via-gold-400/70 to-transparent" />
    </header>
  );
}

/**
 * Banner of the page at `path` (as edited in the admin), or the given defaults when there is no such page.
 * Used by pages the React app renders itself, e.g. Events (/view-events) and Gallery (/view-gallery).
 */
export function PathBanner({ path, ...fallback }: { path: string } & PageHeroProps) {
  const { data: page } = usePageByPath(path);
  const banner = page ? bannerOf(page) : null;

  return (
    <PageHero
      {...fallback}
      title={banner?.title || fallback.title}
      intro={banner?.intro ?? fallback.intro}
      text={banner?.text ?? fallback.text}
      button={banner?.button ?? fallback.button}
      image={banner?.image ?? fallback.image}
    />
  );
}
