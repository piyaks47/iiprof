import { useHome, useSite } from '@shared/hooks/queries';
import type { HomeSection } from '@shared/types';
import { Seo } from '../components/Seo';
import { ErrorState, HomeSkeleton } from '../components/States';
import { About } from '../sections/About';
import { BoardMembers } from '../sections/BoardMembers';
import { Benefits } from '../sections/Benefits';
import { Contact } from '../sections/Contact';
import { Events } from '../sections/Events';
import { Faq } from '../sections/Faq';
import { Faculty } from '../sections/Faculty';
import { FeatureTiles } from '../sections/FeatureTiles';
import { Gallery } from '../sections/Gallery';
import { Hero } from '../sections/Hero';
import { Highlights } from '../sections/Highlights';
import { Impact } from '../sections/Impact';
import { JoinBanner } from '../sections/JoinBanner';
import { CorporateMembers, Institutions } from '../sections/MemberOrganisations';
import { Membership } from '../sections/Membership';
import { MembershipApply } from '../sections/MembershipApply';
import { MembershipHub } from '../sections/MembershipHub';
import { MembershipObjectives } from '../sections/MembershipObjectives';
import { NewsGallery } from '../sections/NewsGallery';
import { NextEvent } from '../sections/NextEvent';
import { Partners } from '../sections/Partners';
import { PastEvents } from '../sections/PastEvents';
import { Programmes } from '../sections/Programmes';
import { SeminarBanners } from '../sections/SeminarBanners';
import { Stats } from '../sections/Stats';
import { HomeTestimonials } from '../sections/Testimonials';
import { TrainingBenefits } from '../sections/TrainingBenefits';
import { TrainingBlock } from '../sections/TrainingBlock';
import { TrainingFormats } from '../sections/TrainingFormats';

/** Homepage: sections in the order and visibility set in Admin → Homepage → Sections. */
export function HomePage() {
  const { data: site } = useSite();
  const home = useHome();

  if (home.isPending) return <HomeSkeleton />;
  if (home.isError) return <ErrorState message={home.error.message} onRetry={() => home.refetch()} />;

  const sections = home.data.sections;

  return (
    <>
      <Seo title={site?.settings.site_name} description={site?.settings.meta_description} />
      {sections.length === 0 && (
        <div className="container-page py-32 text-center">
          <p className="eyebrow justify-center">{site?.settings.tagline}</p>
          <h1 className="mt-5 text-5xl font-semibold text-navy-950">{site?.settings.site_name}</h1>
        </div>
      )}
      {sections.map((section, i) => (
        <SectionView key={section.key} section={section} previous={sections[i - 1]?.key} next={sections[i + 1]?.key} />
      ))}
    </>
  );
}

function SectionView({ section, previous, next }: { section: HomeSection; previous?: string; next?: string }) {
  switch (section.key) {
    case 'hero':
      return <Hero section={section} overlapped={next === 'stats'} />;
    case 'stats':
      return <Stats section={section} overlap={previous === 'hero'} />;
    case 'about':
      return <About section={section} />;
    case 'highlights':
      return <Highlights section={section} />;
    case 'events':
      return <Events section={section} />;
    case 'gallery':
      return <Gallery section={section} />;
    case 'pillars':
      return <FeatureTiles section={section} />;
    case 'impact':
      return <Impact section={section} />;
    case 'benefits':
      return <Benefits section={section} />;
    case 'membership':
      return <Membership section={section} />;
    case 'programmes':
      return <Programmes section={section} />;
    case 'testimonials':
      return <HomeTestimonials section={section} />;
    case 'partners':
      return <Partners section={section} />;
    case 'faq':
      return <Faq section={section} />;
    case 'join':
      return <JoinBanner section={section} />;
    case 'membership_hub':
      return <MembershipHub section={section} />;
    case 'membership_objectives':
      return <MembershipObjectives section={section} />;
    case 'membership_apply':
      return <MembershipApply section={section} />;
    case 'training':
      return <TrainingBlock section={section} />;
    case 'training_formats':
      return <TrainingFormats section={section} />;
    case 'training_benefits':
      return <TrainingBenefits section={section} />;
    case 'faculty':
      return <Faculty section={section} />;
    case 'next_event':
      return <NextEvent section={section} />;
    case 'past_events':
      return <PastEvents section={section} />;
    case 'news':
      return <NewsGallery section={section} />;
    case 'seminars':
      return <SeminarBanners section={section} />;
    case 'board':
      return <BoardMembers section={section} />;
    case 'corporate_members':
      return <CorporateMembers section={section} />;
    case 'institutions':
      return <Institutions section={section} />;
    case 'contact':
      return <Contact section={section} />;
    default:
      return null;
  }
}
