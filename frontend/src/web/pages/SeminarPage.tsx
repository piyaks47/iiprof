import { Link, useParams } from 'react-router';
import { ApiError } from '@shared/api/client';
import { useSeminar, useSite } from '@shared/hooks/queries';
import { Icon } from '../components/Icon';
import { PageHero } from '../components/PageHero';
import { Prose } from '../components/Prose';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { Seo } from '../components/Seo';
import { SmartLink } from '../components/SmartLink';
import { ErrorState, PageSkeleton } from '../components/States';
import { button } from '../components/ui';
import { Backdrop } from '../sections/Backdrop';
import { formatDate } from '../sections/EventCard';
import { NotFoundPage } from './NotFoundPage';

const chip = 'inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 ring-1 ring-white/15';

/** /seminars/:slug — the seminar behind a homepage banner. */
export function SeminarPage() {
  const { slug = '' } = useParams();
  const { data: site } = useSite();
  const query = useSeminar(slug);

  if (query.isPending) return <PageSkeleton />;
  if (query.isError) {
    if (query.error instanceof ApiError && query.error.isNotFound) return <NotFoundPage />;
    return <ErrorState message={query.error.message} onRetry={() => query.refetch()} />;
  }

  const seminar = query.data;
  const date = formatDate(seminar.starts_on, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
  // The destination link, unless it points back to this page.
  const cta = seminar.link && seminar.link !== seminar.path ? seminar.link : '#enquiry';

  return (
    <article>
      <Seo title={seminar.title} description={seminar.summary} siteName={site?.settings.site_name} />
      <PageHero title={seminar.title} intro={seminar.summary} image={seminar.image_url} crumbs={[{ label: 'Seminars' }, { label: seminar.title }]}>
        <div className="flex flex-wrap gap-3 text-sm">
          {date && <span className={chip}><Icon name="calendar" className="size-4 text-gold-300" /> {date}</span>}
          {seminar.location && <span className={chip}><Icon name="map-pin" className="size-4 text-gold-300" /> {seminar.location}</span>}
        </div>
      </PageHero>

      <div className="container-page grid gap-12 py-14 sm:py-20 lg:grid-cols-12">
        <Reveal className="lg:col-span-8">
          {seminar.image_url && <img src={seminar.image_url} alt="" decoding="async" className="mb-12 aspect-[16/9] w-full rounded-2xl object-cover shadow-xl" />}
          {seminar.description_html ? <Prose html={seminar.description_html} /> : <p className="text-lg text-navy-600">{seminar.summary}</p>}
        </Reveal>
        <aside className="lg:col-span-4">
          <Reveal from="right" delay={120} className="sticky top-24 rounded-2xl bg-white p-8 shadow-[0_30px_60px_-35px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/5">
            <p className="eyebrow">Seminar details</p>
            <dl className="mt-6 space-y-5 text-sm">
              <div>
                <dt className="text-[11px] font-bold uppercase tracking-[0.2em] text-navy-400">Date</dt>
                <dd className="mt-1 font-display text-lg text-navy-950">{date ?? 'To be announced'}</dd>
              </div>
              <div>
                <dt className="text-[11px] font-bold uppercase tracking-[0.2em] text-navy-400">Venue</dt>
                <dd className="mt-1 font-display text-lg text-navy-950">{seminar.location ?? 'To be announced'}</dd>
              </div>
            </dl>
            <SmartLink href={cta} subject={`Event: ${seminar.title}`} className={`${button.gold} mt-8 w-full`}>
              {seminar.button_text || 'Register interest'}
            </SmartLink>
          </Reveal>
        </aside>
      </div>

      {seminar.others.length > 0 && (
        <section className="bg-ivory-100 py-20">
          <div className="container-page">
            <h2 className="text-3xl font-semibold text-navy-950">More <em className="text-gilded">seminars</em></h2>
            <ul className="mt-10 grid gap-6 md:grid-cols-3">
              {seminar.others.slice(0, 3).map((s, i) => (
                <Reveal as="li" key={s.id} delay={i * 100}>
                  <Link to={s.path} className="group block overflow-hidden rounded-2xl bg-white shadow-[0_24px_50px_-34px_rgb(7_15_32/0.45)] ring-1 ring-navy-900/5 transition duration-500 hover:-translate-y-1">
                    <span className="block aspect-[16/9] overflow-hidden bg-navy-950">
                      <SafeImg src={s.image_url ?? undefined} alt="" loading="lazy" decoding="async" fallback={<Backdrop variant={i} rings={false} />}
                        className="size-full object-cover transition duration-[1200ms] ease-out-expo group-hover:scale-105" />
                    </span>
                    <span className="block p-6">
                      {s.starts_on && <span className="text-[11px] font-bold uppercase tracking-[0.16em] text-gold-700">{formatDate(s.starts_on)}</span>}
                      <span className="mt-1 block font-display text-xl font-semibold text-navy-950 group-hover:text-gold-700">{s.title}</span>
                    </span>
                  </Link>
                </Reveal>
              ))}
            </ul>
          </div>
        </section>
      )}
    </article>
  );
}
