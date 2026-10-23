import { useParams } from 'react-router';
import { ApiError } from '@shared/api/client';
import { useSite, useTrainingProgramme } from '@shared/hooks/queries';
import { useEnquiry } from '../components/Enquiry';
import { Icon } from '../components/Icon';
import { PageHero } from '../components/PageHero';
import { Prose } from '../components/Prose';
import { Reveal } from '../components/Reveal';
import { Seo } from '../components/Seo';
import { SmartLink } from '../components/SmartLink';
import { ErrorState, PageSkeleton } from '../components/States';
import { button } from '../components/ui';
import { BatchList, ProgrammeCard } from '../sections/TrainingParts';
import { NotFoundPage } from './NotFoundPage';

/** /training/<slug> — one programme: about, modules, audience, benefits, certificate and its batches. */
export function TrainingProgrammePage() {
  const { slug = '' } = useParams();
  const { data: site } = useSite();
  const query = useTrainingProgramme(slug);
  const enquiry = useEnquiry();

  if (query.isPending) return <PageSkeleton />;
  if (query.isError) {
    if (query.error instanceof ApiError && query.error.isNotFound) return <NotFoundPage />;
    return <ErrorState message={query.error.message} onRetry={() => query.refetch()} />;
  }

  const p = query.data;
  const facts = [
    ['Type', p.type_label, 'tag'],
    ['Duration', p.duration, 'clock'],
    ['Format', p.format, 'globe'],
    ['Fee', p.fee, 'banknotes'],
  ].filter(([, v]) => v) as [string, string, string][];
  const register = p.button_url || '#enquiry';

  return (
    <article>
      <Seo title={p.title} description={p.summary} siteName={site?.settings.site_name} />
      <PageHero title={p.title} intro={p.tagline || p.summary} text={p.tagline ? p.summary : null} image={p.image_url} crumbs={[{ label: 'Training', to: '/training' }, { label: p.title }]}>
        {register === '#enquiry' ? (
          <button type="button" className={button.gold} onClick={() => enquiry.open(`Training: ${p.title}`)}>
            {p.button_text || 'Register interest'} <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
          </button>
        ) : (
          <SmartLink href={register} className={button.gold}>{p.button_text || 'Register'} <Icon name="arrow-right" className="size-4" strokeWidth={2} /></SmartLink>
        )}
        {p.batches.length > 0 && <a href="#batches" className={button.ghostLight}>Scheduled dates</a>}
      </PageHero>

      <div className="container-page grid gap-14 py-14 sm:py-20 lg:grid-cols-12">
        <div className="min-w-0 space-y-14 lg:col-span-8">
          {p.description_html && (
            <Reveal>
              <p className="eyebrow">About the programme</p>
              <Prose html={p.description_html} className="mt-4 [&>p:first-child]:mt-0" />
            </Reveal>
          )}

          {p.modules.length > 0 && (
            <Reveal>
              <p className="eyebrow">Course modules</p>
              <ol className="mt-6 grid gap-3 sm:grid-cols-2">
                {p.modules.map((m, i) => (
                  <li key={i} className="flex items-start gap-4 rounded-xl bg-white p-4 ring-1 ring-navy-900/5 transition hover:ring-gold-400/50">
                    <span className="text-gilded font-display text-2xl font-semibold leading-none [font-variant-numeric:lining-nums]">{String(i + 1).padStart(2, '0')}</span>
                    <span className="pt-0.5 font-medium text-navy-900">{m}</span>
                  </li>
                ))}
              </ol>
            </Reveal>
          )}

          {(p.audience_html || p.outcomes_html) && (
            <div className="grid gap-6 md:grid-cols-2">
              {p.audience_html && (
                <Reveal className="rounded-2xl bg-ivory-100 p-7">
                  <p className="eyebrow">Who should attend</p>
                  <Prose html={p.audience_html} className="prose-base mt-3 [&>ul]:mt-0" />
                </Reveal>
              )}
              {p.outcomes_html && (
                <Reveal delay={100} className="rounded-2xl bg-ivory-100 p-7">
                  <p className="eyebrow">Programme benefits</p>
                  <Prose html={p.outcomes_html} className="prose-base mt-3 [&>ul]:mt-0" />
                </Reveal>
              )}
            </div>
          )}

          {p.certificate && (
            <Reveal className="flex items-start gap-5 rounded-2xl border border-gold-400/50 bg-white p-7">
              <span className="flex size-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-gold-300 to-gold-600 text-navy-950"><Icon name="shield" className="size-6" strokeWidth={1.6} /></span>
              <div>
                <p className="font-display text-xl font-semibold text-navy-950">Certificate</p>
                <p className="mt-1 text-[15px] leading-7 text-navy-600">{p.certificate}</p>
              </div>
            </Reveal>
          )}
        </div>

        <aside className="lg:col-span-4">
          <div className="space-y-6 lg:sticky lg:top-24">
            {facts.length > 0 && (
              <Reveal from="right" className="rounded-2xl bg-white p-7 shadow-[0_30px_60px_-38px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/5">
                <p className="eyebrow">At a glance</p>
                <dl className="mt-5 space-y-4">
                  {facts.map(([label, value, icon]) => (
                    <div key={label} className="flex items-start gap-3">
                      <Icon name={icon} className="mt-0.5 size-5 shrink-0 text-gold-500" />
                      <div>
                        <dt className="text-[11px] font-bold uppercase tracking-[0.18em] text-navy-400">{label}</dt>
                        <dd className="font-medium text-navy-950">{value}</dd>
                      </div>
                    </div>
                  ))}
                </dl>
              </Reveal>
            )}
            <Reveal from="right" delay={100} id="batches" className="scroll-mt-24 rounded-2xl bg-navy-950 p-7 text-white">
              <p className="eyebrow text-gold-300">Scheduled dates</p>
              {p.batches.length > 0 ? (
                <BatchList batches={p.batches} dark programmeTitle={p.title} />
              ) : (
                <>
                  <p className="mt-4 text-sm leading-6 text-navy-200">No open batch right now — this programme runs on request. Tell us what you need and we’ll plan it with you.</p>
                  <button type="button" onClick={() => enquiry.open(`Training: ${p.title}`)} className={`${button.gold} mt-6 w-full`}>Request a batch</button>
                </>
              )}
            </Reveal>
          </div>
        </aside>
      </div>

      {p.related.length > 0 && (
        <section className="bg-ivory-100 py-16 sm:py-20">
          <div className="container-page">
            <h2 className="text-3xl font-semibold text-navy-950">More <em className="text-gilded font-medium">programmes</em></h2>
            <ul className="mt-10 grid gap-6 md:grid-cols-3">
              {p.related.map((r, i) => <Reveal as="li" key={r.id} delay={i * 100} className="h-full"><ProgrammeCard programme={r} index={i} /></Reveal>)}
            </ul>
          </div>
        </section>
      )}
    </article>
  );
}
