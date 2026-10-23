import { useMemo, useState } from 'react';
import type { HomeSectionOf } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { button, cx } from '../components/ui';
import { NewsCard } from './NewsCard';

/**
 * News & blogs (News & seminars → News & blogs): a gallery grid with the lead story large,
 * a swipeable carousel on phones, and chips to show one kind of post.
 */
export function NewsGallery({ section }: { section: HomeSectionOf<'news'> }) {
  const [type, setType] = useState('');
  const types = useMemo(() => {
    const seen = new Map<string, string>();
    section.items.forEach((p) => seen.set(p.type, p.type_label));
    return [...seen].map(([key, label]) => ({ key, label }));
  }, [section.items]);

  const list = type ? section.items.filter((p) => p.type === type) : section.items;
  // Lead story large; the next ones fill the space beside it, any others go below in a row.
  const n = list.length;
  const beside = Math.min(n - 1, 4);
  const below = Math.max(n - 5, 0);
  const span = (i: number) => {
    if (i === 0) return n === 1 ? 'sm:col-span-2 lg:col-span-4' : n === 2 ? 'sm:col-span-2 lg:col-span-2' : 'sm:col-span-2 lg:row-span-2';
    const sm = i === n - 1 && (n - 1) % 2 === 1 ? 'sm:col-span-2 ' : '';
    if (i <= 4) return sm + (beside === 1 || beside === 2 || (beside === 3 && i === 1) ? 'lg:col-span-2' : 'lg:col-span-1');
    return sm + (below === 1 ? 'lg:col-span-4' : 'lg:col-span-2');
  };

  return (
    <section id="news" className="relative bg-ivory-100/70 py-16 sm:py-20">
      <div className="container-page">
        <div className="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
          <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} />
          <Reveal delay={150} className="shrink-0">
            <SmartLink href={section.button_url || '/news'} className={button.outline}>
              {section.button_text || 'All news & blogs'}
              <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
            </SmartLink>
          </Reveal>
        </div>

        {types.length > 1 && (
          <Reveal delay={100} className="mt-8 flex flex-wrap gap-2" >
            {[{ key: '', label: 'All' }, ...types].map((t) => (
              <button key={t.key || 'all'} type="button" onClick={() => setType(t.key)} aria-pressed={type === t.key}
                className={cx('rounded-full px-4 py-2 text-[13px] font-semibold transition', type === t.key ? 'bg-navy-950 text-gold-300 shadow' : 'bg-white text-navy-700 ring-1 ring-navy-900/10 hover:ring-navy-900/30')}>
                {t.label}
              </button>
            ))}
          </Reveal>
        )}

        <ul key={type} className={cx(
          'scrollbar-none -mx-4 mt-8 flex snap-x snap-mandatory scroll-px-4 gap-4 overflow-x-auto px-4 pb-4 sm:mx-0 sm:grid sm:grid-cols-2 sm:gap-5 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-4',
          n >= 3 && 'lg:auto-rows-[minmax(16rem,auto)]',
        )}>
          {list.map((post, i) => (
            <Reveal as="li" key={post.id} delay={Math.min(i, 4) * 90} className={cx('w-[86%] shrink-0 snap-start sm:w-auto', span(i))}>
              <NewsCard post={post} index={i} large={i === 0} />
            </Reveal>
          ))}
        </ul>
      </div>
    </section>
  );
}
