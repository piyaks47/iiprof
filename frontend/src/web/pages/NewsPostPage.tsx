import { useState } from 'react';
import { useParams } from 'react-router';
import { ApiError } from '@shared/api/client';
import { usePost, useSite } from '@shared/hooks/queries';
import { Icon } from '../components/Icon';
import { PageHero } from '../components/PageHero';
import { Prose } from '../components/Prose';
import { Reveal } from '../components/Reveal';
import { Seo } from '../components/Seo';
import { SmartLink } from '../components/SmartLink';
import { ErrorState, PageSkeleton } from '../components/States';
import { button } from '../components/ui';
import { formatDate } from '../sections/EventCard';
import { NewsCard } from '../sections/NewsCard';
import { NotFoundPage } from './NotFoundPage';

const chip = 'inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 ring-1 ring-white/15';

/** /news/:slug — one news item or blog post. */
export function NewsPostPage() {
  const { slug = '' } = useParams();
  const { data: site } = useSite();
  const query = usePost(slug);
  const [copied, setCopied] = useState(false);

  if (query.isPending) return <PageSkeleton />;
  if (query.isError) {
    if (query.error instanceof ApiError && query.error.isNotFound) return <NotFoundPage />;
    return <ErrorState message={query.error.message} onRetry={() => query.refetch()} />;
  }

  const post = query.data;
  const date = formatDate(post.published_on);
  const share = async () => {
    const url = window.location.href;
    try {
      if (navigator.share) await navigator.share({ title: post.title, url });
      else {
        await navigator.clipboard.writeText(url);
        setCopied(true);
        window.setTimeout(() => setCopied(false), 2500);
      }
    } catch {
      /* closed by the user */
    }
  };

  return (
    <article>
      <Seo title={post.title} description={post.excerpt} siteName={site?.settings.site_name} />
      <PageHero title={post.title} intro={post.excerpt} image={post.image_url} crumbs={[{ label: 'News & blogs', to: '/news' }, { label: post.title }]}>
        <div className="flex flex-wrap gap-3 text-sm">
          <span className={chip}><Icon name="tag" className="size-4 text-gold-300" /> {post.type_label}</span>
          {date && <span className={chip}><Icon name="calendar" className="size-4 text-gold-300" /> {date}</span>}
          {post.author && <span className={chip}><Icon name="user" className="size-4 text-gold-300" /> {post.author}</span>}
          {post.reading_minutes > 0 && <span className={chip}><Icon name="clock" className="size-4 text-gold-300" /> {post.reading_minutes} min read</span>}
        </div>
      </PageHero>

      <div className="container-page grid gap-12 py-14 sm:py-20 lg:grid-cols-12">
        <Reveal className="lg:col-span-8">
          {post.body_html ? <Prose html={post.body_html} /> : <p className="text-lg text-navy-600">{post.excerpt}</p>}
          {post.external_url && (
            <SmartLink href={post.external_url} className={`${button.gold} mt-10`}>
              Read the full article <Icon name="arrow-up-right" className="size-4" strokeWidth={2} />
            </SmartLink>
          )}
        </Reveal>
        <aside className="lg:col-span-4">
          <Reveal from="right" delay={120} className="sticky top-24 space-y-4 rounded-2xl bg-white p-7 shadow-[0_30px_60px_-35px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/5">
            <p className="eyebrow">Share</p>
            <button type="button" onClick={share} className={`${button.outline} w-full`}>
              <Icon name={copied ? 'check' : 'link'} className="size-4" strokeWidth={2} /> {copied ? 'Link copied' : 'Share this post'}
            </button>
            <SmartLink href="/news" className={`${button.navy} w-full`}>
              <Icon name="arrow-left" className="size-4" strokeWidth={2} /> All news &amp; blogs
            </SmartLink>
          </Reveal>
        </aside>
      </div>

      {post.related.length > 0 && (
        <section className="bg-ivory-100 py-20">
          <div className="container-page">
            <h2 className="text-3xl font-semibold text-navy-950">Keep <em className="text-gilded">reading</em></h2>
            <ul className="mt-10 grid gap-5 md:grid-cols-3">
              {post.related.slice(0, 3).map((p, i) => (
                <Reveal as="li" key={p.id} delay={i * 100}><NewsCard post={p} index={i} /></Reveal>
              ))}
            </ul>
          </div>
        </section>
      )}
    </article>
  );
}
