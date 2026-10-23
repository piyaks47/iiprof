import { useEvents, useSite } from '@shared/hooks/queries';
import { PathBanner } from '../components/PageHero';
import { Reveal } from '../components/Reveal';
import { Seo } from '../components/Seo';
import { ErrorState, PageSkeleton } from '../components/States';
import { EventCard } from '../sections/EventCard';

export function EventsPage() {
  const { data: site } = useSite();
  const query = useEvents();

  if (query.isPending) return <PageSkeleton />;
  if (query.isError) return <ErrorState message={query.error.message} onRetry={() => query.refetch()} />;

  return (
    <>
      <Seo title="Events" siteName={site?.settings.site_name} />
      <PathBanner path="/view-events" title="Events" intro="Programmes, tests and gatherings for our members and partners." crumbs={[{ label: 'Events' }]} />
      <div className="container-page py-14 sm:py-20">
        {query.data.length === 0 ? (
          <p className="text-center text-navy-500">New events will be announced soon.</p>
        ) : (
          <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {query.data.map((event, i) => (
              <Reveal as="li" key={event.id} delay={(i % 3) * 100}><EventCard event={event} index={i} /></Reveal>
            ))}
          </ul>
        )}
      </div>
    </>
  );
}
