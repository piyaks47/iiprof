import { useGallery, useSite } from '@shared/hooks/queries';
import { PathBanner } from '../components/PageHero';
import { Seo } from '../components/Seo';
import { ErrorState, PageSkeleton } from '../components/States';
import { GalleryGrid } from '../sections/Gallery';

export function GalleryPage() {
  const { data: site } = useSite();
  const query = useGallery();

  if (query.isPending) return <PageSkeleton />;
  if (query.isError) return <ErrorState message={query.error.message} onRetry={() => query.refetch()} />;

  return (
    <>
      <Seo title="Gallery" siteName={site?.settings.site_name} />
      <PathBanner path="/view-gallery" title="Gallery" intro="Moments from IIP events, meetings and seminars." crumbs={[{ label: 'Gallery' }]} />
      <div className="container-page py-14 sm:py-20">
        {query.data.length ? <GalleryGrid items={query.data} /> : <p className="text-center text-navy-500">Photos will be added soon.</p>}
      </div>
    </>
  );
}
