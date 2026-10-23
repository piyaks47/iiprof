import { useSearchParams } from 'react-router';
import { useNews, useSite } from '@shared/hooks/queries';
import { Icon } from '../components/Icon';
import { PathBanner } from '../components/PageHero';
import { Reveal } from '../components/Reveal';
import { Seo } from '../components/Seo';
import { ErrorState, PageSkeleton } from '../components/States';
import { button, cx } from '../components/ui';
import { NewsCard } from '../sections/NewsCard';

/** /news — every news item, blog post and article, with a tab per kind and "Load more". */
export function NewsPage() {
  const { data: site } = useSite();
  const [params, setParams] = useSearchParams();
  const type = params.get('type') ?? '';
  const query = useNews(type);

  if (query.isPending) return <PageSkeleton />;
  if (query.isError) return <ErrorState message={query.error.message} onRetry={() => query.refetch()} />;

  const pages = query.data.pages;
  const posts = pages.flatMap((p) => p.data);
  const types = pages[0]?.types ?? [];
  const total = pages[0]?.meta.total ?? posts.length;

  return (
    <>
      <Seo title="News & blogs" siteName={site?.settings.site_name} />
      <PathBanner path="/news" title="News & blogs" intro="Updates, stories and articles from the IIP community." crumbs={[{ label: 'News & blogs' }]} />

      <div className="container-page py-14 sm:py-20">
        {types.length > 1 && (
          <div className="scrollbar-none -mx-4 mb-10 overflow-x-auto px-4">
            <div role="tablist" aria-label="Kinds of posts" className="inline-flex gap-1 rounded-full bg-white p-1 shadow-sm ring-1 ring-navy-900/10">
              {[{ key: '', label: 'All' }, ...types].map((t) => (
                <button key={t.key || 'all'} type="button" role="tab" aria-selected={type === t.key}
                  onClick={() => setParams(t.key ? { type: t.key } : {}, { replace: true, preventScrollReset: true })}
                  className={cx('whitespace-nowrap rounded-full px-5 py-2 text-[13px] font-semibold transition', type === t.key ? 'bg-navy-950 text-gold-300' : 'text-navy-600 hover:bg-ivory-100')}>
                  {t.label}
                </button>
              ))}
            </div>
          </div>
        )}

        {posts.length === 0 ? (
          <p className="py-16 text-center text-navy-500">New posts will be published here soon.</p>
        ) : (
          <ul className={cx('grid gap-5 sm:grid-cols-2 lg:grid-cols-3', query.isPlaceholderData && 'opacity-60 transition-opacity')}>
            {posts.map((post, i) => (
              <Reveal as="li" key={post.id} delay={(i % 3) * 90} className={cx(i === 0 && !type && 'sm:col-span-2 lg:col-span-3')}>
                <NewsCard post={post} index={i} large={i === 0 && !type} />
              </Reveal>
            ))}
          </ul>
        )}

        <div className="mt-12 flex flex-col items-center gap-3">
          <p className="text-sm text-navy-400">Showing {posts.length} of {total}</p>
          {query.hasNextPage && (
            <button type="button" onClick={() => query.fetchNextPage()} disabled={query.isFetchingNextPage} className={button.outline}>
              {query.isFetchingNextPage ? 'Loading…' : 'Load more'}
              <Icon name="arrow-down" className="size-4" strokeWidth={2} />
            </button>
          )}
        </div>
      </div>
    </>
  );
}
