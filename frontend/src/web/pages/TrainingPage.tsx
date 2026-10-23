import { useState } from 'react';
import { useSite, useTraining } from '@shared/hooks/queries';
import { Icon } from '../components/Icon';
import { PathBanner } from '../components/PageHero';
import { Reveal } from '../components/Reveal';
import { Seo } from '../components/Seo';
import { ErrorState, PageSkeleton } from '../components/States';
import { cx } from '../components/ui';
import { ProgrammeCard, RegisterBatch } from '../sections/TrainingParts';

/** /training — all programmes (tabs by type) and the training calendar. The banner comes from the page at /training if there is one. */
export function TrainingPage() {
  const { data: site } = useSite();
  const query = useTraining();
  const [tab, setTab] = useState('all');

  if (query.isPending) return <PageSkeleton />;
  if (query.isError) return <ErrorState message={query.error.message} onRetry={() => query.refetch()} />;

  const { programmes, batches, types } = query.data;
  const present = types.filter((t) => programmes.some((p) => p.type === t.key));
  const shown = programmes.filter((p) => tab === 'all' || p.type === tab);

  return (
    <>
      <Seo title="Training & development" description="Training programmes and the training calendar of the International Institute of Professionals." siteName={site?.settings.site_name} />
      <PathBanner path="/training" title="Training & development" intro="Skills, employability, leadership and entrepreneurship programmes — in open batches, at your organisation or online." crumbs={[{ label: 'Training' }]} />

      <div className="container-page py-14 sm:py-20">
        {present.length > 1 && (
          <div role="tablist" aria-label="Types of programme" className="scrollbar-none mb-8 flex gap-2 overflow-x-auto pb-1">
            {[{ key: 'all', label: 'All programmes' }, ...present].map((t) => (
              <button key={t.key} type="button" role="tab" aria-selected={tab === t.key} onClick={() => setTab(t.key)}
                className={cx('shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition', tab === t.key ? 'bg-navy-950 text-gold-300' : 'bg-white text-navy-800 ring-1 ring-navy-900/10 hover:ring-gold-400')}>
                {t.label}
              </button>
            ))}
          </div>
        )}
        {shown.length === 0 ? (
          <p className="text-center text-navy-500">New programmes will be announced soon.</p>
        ) : (
          <ul key={tab} className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {shown.map((p, i) => (
              <Reveal as="li" key={p.id} delay={(i % 3) * 100} className="h-full"><ProgrammeCard programme={p} index={i} /></Reveal>
            ))}
          </ul>
        )}
      </div>

      <section id="calendar" className="scroll-mt-24 bg-ivory-100 py-14 sm:py-20">
        <div className="container-page">
          <p className="eyebrow">Training calendar</p>
          <h2 className="mt-3 text-3xl font-semibold text-navy-950 sm:text-[2.4rem]">Upcoming <em className="text-gilded font-medium">batches</em></h2>
          {batches.length === 0 ? (
            <p className="mt-8 text-navy-500">New dates will be announced soon — send an enquiry to be told first.</p>
          ) : (
            <Reveal className="mt-10 overflow-hidden rounded-2xl bg-white shadow-[0_30px_60px_-38px_rgb(7_15_32/0.5)] ring-1 ring-navy-900/10">
              <div className="overflow-x-auto">
                <table className="w-full min-w-[44rem] text-left text-[14px]">
                  <thead className="bg-navy-950 text-[11px] font-bold uppercase tracking-[0.16em] text-gold-200">
                    <tr>
                      <th scope="col" className="px-6 py-4">Programme</th>
                      <th scope="col" className="px-4 py-4">Dates</th>
                      <th scope="col" className="px-4 py-4">Format</th>
                      <th scope="col" className="px-4 py-4">Place</th>
                      <th scope="col" className="px-6 py-4"><span className="sr-only">Register</span></th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-ivory-200">
                    {batches.map((b) => (
                      <tr key={b.id} className="transition hover:bg-ivory-50">
                        <th scope="row" className="px-6 py-4 font-normal">
                          <a href={b.programme?.path} className="font-semibold text-navy-950 hover:text-gold-700">{b.programme?.title}</a>
                          {(b.batch_code || b.schedule) && <span className="mt-0.5 block text-xs text-navy-400">{[b.batch_code, b.schedule].filter(Boolean).join(' · ')}</span>}
                        </th>
                        <td className="whitespace-nowrap px-4 py-4 font-medium text-navy-900"><Icon name="calendar" className="mr-1.5 inline size-4 text-gold-500" />{b.dates}</td>
                        <td className="px-4 py-4 text-navy-700">{b.format_label}</td>
                        <td className="px-4 py-4 text-navy-700">{b.location ?? '—'}</td>
                        <td className="px-6 py-4 text-right">
                          <RegisterBatch batch={b} title={b.programme?.title ?? 'Training'} className="rounded-full bg-navy-950 px-4 py-2 text-xs font-semibold text-white transition hover:bg-gold-400 hover:text-navy-950" />
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </Reveal>
          )}
        </div>
      </section>
    </>
  );
}
