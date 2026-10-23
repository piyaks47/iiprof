import { useParams } from 'react-router';
import { ApiError } from '@shared/api/client';
import { useEvent, useEvents, useSite } from '@shared/hooks/queries';
import { Icon } from '../components/Icon';
import { PageHero } from '../components/PageHero';
import { Prose } from '../components/Prose';
import { Reveal } from '../components/Reveal';
import { Seo } from '../components/Seo';
import { SmartLink } from '../components/SmartLink';
import { ErrorState, PageSkeleton } from '../components/States';
import { button } from '../components/ui';
import { EventCard, formatDate } from '../sections/EventCard';
import { NotFoundPage } from './NotFoundPage';

export function EventPage() {
  const { slug = '' } = useParams();
  const { data: site } = useSite();
  const query = useEvent(slug);
  const others = useEvents();

  if (query.isPending) return <PageSkeleton />;
  if (query.isError) {
    if (query.error instanceof ApiError && query.error.isNotFound) return <NotFoundPage />;
    return <ErrorState message={query.error.message} onRetry={() => query.refetch()} />;
  }

  const event = query.data;
  const date = formatDate(event.starts_on, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  const more = (others.data ?? []).filter((e) => e.id !== event.id).slice(0, 3);

  return (
    <article>
      <Seo title={event.title} description={event.summary} siteName={site?.settings.site_name} />
      <PageHero title={event.title} intro={event.summary} crumbs={[{ label: 'Events', to: '/events' }, { label: event.title }]} image={event.image_url}>
        <div className="flex flex-wrap gap-3 text-sm">
          {date && <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 ring-1 ring-white/15"><Icon name="calendar" className="size-4 text-gold-300" /> {date}</span>}
          {event.location && <span className="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 ring-1 ring-white/15"><Icon name="map-pin" className="size-4 text-gold-300" /> {event.location}</span>}
        </div>
      </PageHero>

      <div className="container-page grid gap-14 py-14 sm:py-20 lg:grid-cols-12">
        <Reveal className="lg:col-span-8">
          {event.image_url && <img src={event.image_url} alt="" className="mb-12 aspect-[16/9] w-full rounded-2xl object-cover shadow-xl" />}
          {event.description_html ? <Prose html={event.description_html} /> : <p className="text-lg text-navy-600">{event.summary}</p>}
        </Reveal>
        <aside className="lg:col-span-4">
          <Reveal from="right" delay={120} className="sticky top-24 rounded-2xl bg-white p-8 shadow-[0_30px_60px_-35px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/5">
            <p className="eyebrow">Event details</p>
            <dl className="mt-6 space-y-5 text-sm">
              <div>
                <dt className="text-[11px] font-bold uppercase tracking-[0.2em] text-navy-400">Date</dt>
                <dd className="mt-1 font-display text-lg text-navy-950">{date ?? 'To be announced'}</dd>
              </div>
              <div>
                <dt className="text-[11px] font-bold uppercase tracking-[0.2em] text-navy-400">Location</dt>
                <dd className="mt-1 font-display text-lg text-navy-950">{event.location ?? 'To be announced'}</dd>
              </div>
            </dl>
            <SmartLink href={event.button_url || '#enquiry'} subject={`Event: ${event.title}`} className={`${button.gold} mt-8 w-full`}>
              {event.button_text || 'Register interest'}
            </SmartLink>
          </Reveal>
        </aside>
      </div>

      {more.length > 0 && (
        <section className="bg-ivory-100 py-20">
          <div className="container-page">
            <h2 className="text-3xl font-semibold text-navy-950">More <em className="text-gilded">events</em></h2>
            <ul className="mt-10 grid gap-6 md:grid-cols-3">
              {more.map((e, i) => (
                <Reveal as="li" key={e.id} delay={i * 100}><EventCard event={e} index={i} /></Reveal>
              ))}
            </ul>
          </div>
        </section>
      )}
    </article>
  );
}
