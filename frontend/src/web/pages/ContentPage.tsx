import { Link, Navigate, useLocation } from 'react-router';
import { ApiError } from '@shared/api/client';
import { HOME_SLUG, usePageByPath, useSite } from '@shared/hooks/queries';
import { findMenuTrail, menuImage, pathToSlug } from '@shared/lib/menu';
import type { MenuItem, Page, Site } from '@shared/types';
import { useEnquiry } from '../components/Enquiry';
import { Icon } from '../components/Icon';
import { bannerOf, PageHero, type Crumb } from '../components/PageHero';
import { Prose } from '../components/Prose';
import { Reveal } from '../components/Reveal';
import { Seo } from '../components/Seo';
import { SmartLink } from '../components/SmartLink';
import { Spotlight } from '../components/Spotlight';
import { ErrorState, PageSkeleton } from '../components/States';
import { button, cx } from '../components/ui';
import { TeamGrid } from '../sections/TeamGrid';
import { TestimonialsGrid } from '../sections/Testimonials';
import { NotFoundPage } from './NotFoundPage';

/** Paths from earlier versions of this site. */
const RENAMED: Record<string, string> = { '/about': '/about-us', '/contact': '/contact-us' };

/**
 * Any path not handled elsewhere: /about-us → page "about-us", /management/director-council → page
 * "management-director-council" (+ its people). Pages are looked up by address, so a page can also be
 * given its own (Website address field). Pages with a banner but no text, and menu links without a
 * page, get a category landing page.
 */
export function ContentPage() {
  const { pathname } = useLocation();
  const path = decodeURIComponent(pathname).replace(/\/+$/, '') || '/';
  const slug = pathToSlug(path);
  const { data: site } = useSite();
  const query = usePageByPath(path, slug !== '' && slug !== HOME_SLUG && !RENAMED[path]);

  if (RENAMED[path]) return <Navigate to={RENAMED[path]} replace />;
  if (slug === HOME_SLUG) return <Navigate to="/" replace />;
  if (query.isPending) return <PageSkeleton />;

  const trail = site ? findMenuTrail(site.menu, path) : [];
  const crumbs: Crumb[] = trail.slice(0, -1).map((m) => ({ label: m.label, to: m.url && m.url.startsWith('/') ? m.url : null }));

  if (query.isError) {
    if (query.error instanceof ApiError && query.error.isNotFound) {
      const footerLink = site?.footer.flatMap((c) => c.links).find((l) => l.url.replace(/\/+$/, '') === path);
      const label = trail.at(-1)?.label ?? footerLink?.label;
      return label ? <CategoryPage title={label} crumbs={[...crumbs, { label }]} site={site} trail={trail} /> : <NotFoundPage />;
    }
    return <ErrorState message={query.error.message} onRetry={() => query.refetch()} />;
  }

  const page = query.data;
  const group = page.slug.startsWith('management-') ? page.slug.slice('management-'.length) : null;
  const wide = group !== null || page.slug === 'testimonials';

  if (!page.has_content && !wide) {
    const label = trail.at(-1)?.label ?? page.title;
    return <CategoryPage title={label} crumbs={[...crumbs, { label }]} site={site} trail={trail} page={page} />;
  }

  const updated = page.updated_at ? new Date(page.updated_at) : null;
  const banner = bannerOf(page);
  const category = categoryOf(trail);
  const label = trail.at(-1)?.label ?? page.title;

  return (
    <article>
      <Seo title={page.meta.title} description={page.meta.description} siteName={site?.settings.site_name} />
      <PageHero {...banner} image={banner.image ?? menuImage(trail)} crumbs={[...crumbs, { label: page.title }]}>
        {category && !banner.button && <RegisterButton title={label} section={category} />}
      </PageHero>

      {wide ? (
        <div className="container-page py-14 sm:py-20">
          {page.content_html && (
            <Reveal className="mb-14 max-w-3xl">
              <Prose html={page.content_html} />
            </Reveal>
          )}
          {group !== null && <TeamGrid group={group} />}
          {page.slug === 'testimonials' && <TestimonialsGrid />}
          {group !== null && <ManagementLinks current={group} site={site} />}
        </div>
      ) : (
        <div className="container-page grid gap-14 py-14 sm:py-20 lg:grid-cols-12">
          <Reveal className="min-w-0 lg:col-span-8">
            <Prose html={page.content_html} className="prose-table:text-[15px] prose-th:bg-navy-950 prose-th:px-3 prose-th:py-2.5 prose-th:text-white prose-td:px-3 prose-td:py-2.5 prose-tr:border-ivory-200 [&_table]:overflow-hidden [&_table]:rounded-xl [&_table]:ring-1 [&_table]:ring-navy-900/10" />
            {updated && (
              <p className="mt-14 border-t border-ivory-200 pt-6 text-sm text-navy-400">
                Last updated {updated.toLocaleDateString('en-GB', { year: 'numeric', month: 'long', day: 'numeric' })}
              </p>
            )}
          </Reveal>
          <aside className="lg:col-span-4">
            <EnquiryCard site={site} />
          </aside>
          {category && (
            <div className="lg:col-span-12">
              <RelatedLinks trail={trail} title={label} />
            </div>
          )}
        </div>
      )}
    </article>
  );
}

/** Top-level section of a page inside a menu category (Khazana, Health …); null for About Us, More and single links. */
function categoryOf(trail: MenuItem[]): string | null {
  if (trail.length < 2) return null;
  const top = trail[0].label;
  return ['about us', 'more'].includes(top.trim().toLowerCase()) ? null : top;
}

function RegisterButton({ title, section }: { title: string; section: string | null }) {
  const enquiry = useEnquiry();
  return (
    <button type="button" onClick={() => enquiry.open(`Registration: ${section ? `${section} · ` : ''}${title}`)} className={button.gold}>
      Register as {title} <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
    </button>
  );
}

/** The item's children, or its siblings ("More in Lender"). */
function RelatedLinks({ trail, title }: { trail: MenuItem[]; title: string }) {
  const item = trail.at(-1);
  const parent = trail.at(-2);
  const related = (item?.children.length ? item.children : parent?.children ?? []).filter((c) => c.id !== item?.id);
  if (related.length === 0) return null;

  return (
    <div className="mt-2">
      <p className="eyebrow">{item?.children.length ? `Explore ${title}` : `More in ${parent?.label ?? trail[0]?.label}`}</p>
      <ul className="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {related.map((link, i) => (
          <Reveal as="li" key={link.id} delay={(i % 4) * 60}>
            <SmartLink href={link.url} newTab={link.new_tab}
              className="group flex items-center justify-between gap-3 rounded-xl bg-white px-5 py-4 shadow-[0_16px_34px_-28px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/5 transition duration-500 hover:-translate-y-0.5 hover:ring-gold-400/60">
              <span className="font-medium text-navy-900">{link.label}</span>
              <Icon name={link.new_tab ? 'arrow-up-right' : 'arrow-right'} className="size-4 text-gold-500 transition group-hover:translate-x-1" />
            </SmartLink>
          </Reveal>
        ))}
      </ul>
    </div>
  );
}

/** Tabs between the management bodies, taken from the menu. */
function ManagementLinks({ current, site }: { current: string; site?: Site }) {
  const management = site ? findMenuTrail(site.menu, `/management/${current}`).at(-2) : undefined;
  if (!management || management.children.length < 2) return null;

  return (
    <nav aria-label={management.label} className="mt-16 flex flex-wrap gap-2 border-t border-ivory-200 pt-10">
      {management.children.map((child) => {
        const active = child.url === `/management/${current}`;
        return (
          <SmartLink key={child.id} href={child.url} className={cx('rounded-full px-5 py-2.5 text-sm font-semibold transition', active ? 'bg-navy-950 text-gold-300' : 'bg-white text-navy-800 ring-1 ring-navy-900/10 hover:ring-gold-400')}>
            {child.label}
          </SmartLink>
        );
      })}
    </nav>
  );
}

function EnquiryCard({ site }: { site?: Site }) {
  return (
    <Reveal from="right" delay={150} className="sticky top-24">
      <Spotlight dark className="overflow-hidden rounded-2xl bg-navy-950 p-8 text-white shadow-xl">
        <div className="bg-lattice absolute inset-0 opacity-50" />
        <div className="relative">
          <p className="eyebrow text-gold-300">Need help?</p>
          <p className="mt-4 font-display text-2xl leading-snug">Talk to the {site?.settings.short_name || 'IIP'} team</p>
          <p className="mt-3 text-sm leading-6 text-navy-200">Questions about membership, programmes or partnerships — we’re happy to help.</p>
          <SmartLink href="#enquiry" className={`${button.gold} mt-8 w-full`}>Send an enquiry</SmartLink>
          {site?.settings.contact_email && (
            <a href={`mailto:${site.settings.contact_email}`} className="mt-5 flex items-center justify-center gap-2 text-sm text-navy-200 hover:text-white">
              <Icon name="envelope" className="size-4 text-gold-400" /> {site.settings.contact_email}
            </a>
          )}
        </div>
      </Spotlight>
    </Reveal>
  );
}

/**
 * Landing page for a menu link whose page has only a banner (or no page at all yet), e.g.
 * Khazana → Lender → Banks/NBFC: the page's banner, an invitation to register and its neighbours.
 */
function CategoryPage({ title, crumbs, site, trail, page }: { title: string; crumbs: Crumb[]; site?: Site; trail: MenuItem[]; page?: Page }) {
  const enquiry = useEnquiry();
  const item = trail.at(-1);
  const section = trail[0] && trail[0] !== item ? trail[0].label : null;
  const intro = section
    ? `Join the ${section} network of the International Institute of Professionals as ${title}. Register your interest and our team will set up your profile.`
    : `Register your interest in ${title} and our team will get back to you.`;

  const banner = page ? bannerOf(page) : null;

  return (
    <article>
      <Seo title={page?.meta.title ?? title} description={page?.meta.description} siteName={site?.settings.site_name} />
      <PageHero crumbs={crumbs} {...banner} title={banner?.title || title} intro={banner?.intro || intro} image={banner?.image ?? menuImage(trail)}>
        {!banner?.button && <RegisterButton title={title} section={section} />}
        <button type="button" onClick={() => enquiry.open(`Question about ${title}`)} className={button.ghostLight}>Ask a question</button>
      </PageHero>

      <div className="container-page py-14 sm:py-20">
        <div className="grid gap-6 md:grid-cols-3">
          {[
            ['identification', 'Create your profile', 'Tell us who you are and what you offer or need.'],
            ['user-group', 'Get connected', `Meet members across ${section ?? 'IIP'} who match your profile.`],
            ['trending-up', 'Grow together', 'Learn from experts, offer your services and grow your network.'],
          ].map(([icon, heading, text], i) => (
            <Reveal key={heading} delay={i * 100}>
              <Spotlight className="h-full rounded-2xl bg-white p-8 shadow-[0_24px_50px_-34px_rgb(7_15_32/0.45)] ring-1 ring-navy-900/5">
                <span className="flex size-12 items-center justify-center rounded-full bg-gradient-to-br from-gold-300 to-gold-600 text-navy-950">
                  <Icon name={icon} className="size-5" strokeWidth={1.75} />
                </span>
                <p className="mt-6 font-display text-xl font-semibold text-navy-950">{heading}</p>
                <p className="mt-2 text-[15px] leading-7 text-navy-600">{text}</p>
              </Spotlight>
            </Reveal>
          ))}
        </div>

        <div className="mt-16">
          <RelatedLinks trail={trail} title={title} />
        </div>

        <p className="mt-14 text-sm text-navy-500">
          Looking for something else? <Link to="/" className="link-underline font-semibold text-navy-900">Back to the homepage</Link>
        </p>
      </div>
    </article>
  );
}
