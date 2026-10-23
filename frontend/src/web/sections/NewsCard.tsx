import type { PostSummary } from '@shared/types';
import { Icon } from '../components/Icon';
import { SafeImg } from '../components/SafeImg';
import { SmartLink } from '../components/SmartLink';
import { cx } from '../components/ui';
import { Backdrop } from './Backdrop';
import { formatDate } from './EventCard';

/** Card of a news item / blog post. `large` is the lead story of the homepage grid. */
export function NewsCard({ post, index = 0, large = false, className }: { post: PostSummary; index?: number; large?: boolean; className?: string }) {
  const date = formatDate(post.published_on, { day: 'numeric', month: 'short', year: 'numeric' });

  return (
    <SmartLink
      href={post.external_url || post.path}
      className={cx(
        'group relative isolate flex h-full w-full flex-col justify-end overflow-hidden rounded-2xl bg-navy-950 text-left text-white shadow-[0_30px_60px_-38px_rgb(7_15_32/0.6)] ring-1 ring-navy-900/10 transition duration-500 ease-out-expo hover:-translate-y-1 hover:shadow-[0_44px_80px_-38px_rgb(7_15_32/0.65)]',
        large ? 'min-h-[22rem] p-7 pt-20 sm:min-h-[28rem] sm:p-9 sm:pt-20' : 'min-h-[16rem] p-6 pt-16',
        className,
      )}
    >
      <SafeImg src={post.image_url ?? undefined} alt="" loading="lazy" decoding="async" fallback={<Backdrop variant={index} rings={false} className="-z-20" />}
        className="absolute inset-0 -z-20 size-full object-cover transition duration-[1400ms] ease-out-expo group-hover:scale-[1.06]" />
      <span className="absolute inset-0 -z-10 bg-gradient-to-t from-navy-950 via-navy-950/65 to-navy-950/10 transition duration-500 group-hover:via-navy-950/75" />

      <span className="absolute left-5 top-5 inline-flex items-center gap-2 sm:left-6 sm:top-6">
        <span className="rounded-full bg-gold-400 px-3 py-1 text-[10.5px] font-bold uppercase tracking-[0.16em] text-navy-950">{post.type_label}</span>
        {post.is_featured && large && <span className="rounded-full bg-white/15 px-3 py-1 text-[10.5px] font-bold uppercase tracking-[0.16em] text-white ring-1 ring-white/25 backdrop-blur">Featured</span>}
      </span>

      <span className="flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] font-semibold uppercase tracking-[0.14em] text-gold-200/90">
        {date && <span>{date}</span>}
        {post.reading_minutes > 0 && <span className="inline-flex items-center gap-1"><Icon name="clock" className="size-3.5" />{post.reading_minutes} min read</span>}
      </span>
      <span className={cx('mt-2 font-display font-semibold leading-tight', large ? 'text-3xl sm:text-[2.35rem]' : 'line-clamp-3 text-xl')}>{post.title}</span>
      {post.excerpt && (
        <span className={cx('mt-3 max-w-xl text-sm leading-6 text-navy-100/90', large ? 'line-clamp-3 sm:text-[15px] sm:leading-7' : 'line-clamp-2')}>{post.excerpt}</span>
      )}
      <span className="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-gold-200">
        {post.external_url ? 'Open article' : 'Read more'}
        <Icon name={post.external_url ? 'arrow-up-right' : 'arrow-right'} className="size-4 transition duration-300 group-hover:translate-x-1" strokeWidth={2} />
      </span>
      <span className="absolute inset-x-0 bottom-0 h-0.5 origin-left scale-x-0 bg-gradient-to-r from-gold-300 via-gold-500 to-transparent transition-transform duration-700 ease-out-expo group-hover:scale-x-100" />
    </SmartLink>
  );
}
