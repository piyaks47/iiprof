import { formatFee } from '@shared/lib/format';
import type { HomeSectionOf } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { button, cx } from '../components/ui';

/** How to apply (steps) next to the fee table (Membership → How to apply, Membership fees). */
export function MembershipApply({ section }: { section: HomeSectionOf<'membership_apply'> }) {
  const fees = section.extra.fees;
  const lifetime = fees.some((f) => f.lifetime_fee);

  return (
    <section id="membership_apply" className="relative bg-white py-16 sm:py-20">
      <div className={cx('container-page grid gap-12', fees.length > 0 && 'lg:grid-cols-12')}>
        <div className={fees.length > 0 ? 'lg:col-span-5' : ''}>
          <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} />
          <ol className="relative mt-10 space-y-7 before:absolute before:bottom-4 before:left-6 before:top-4 before:w-px before:bg-gradient-to-b before:from-gold-400 before:to-gold-400/10">
            {section.items.map((step, i) => (
              <Reveal as="li" key={step.id} delay={i * 110} className="relative flex gap-5">
                <span className="relative z-10 flex size-12 shrink-0 items-center justify-center rounded-full bg-navy-950 text-gold-300 shadow-[0_12px_30px_-12px_rgb(7_15_32/0.7)] ring-4 ring-white">
                  {step.icon ? <Icon name={step.icon} className="size-5" strokeWidth={1.75} /> : <span className="font-display text-lg">{i + 1}</span>}
                </span>
                <div className="pt-1">
                  <p className="text-[11px] font-bold uppercase tracking-[0.2em] text-gold-700">Step {i + 1}</p>
                  <h3 className="mt-1 text-lg font-semibold text-navy-950">{step.title}</h3>
                  {step.description && <p className="mt-1 text-[14.5px] leading-6 text-navy-600">{step.description}</p>}
                </div>
              </Reveal>
            ))}
          </ol>
          {section.button_text && (
            <Reveal delay={300} className="mt-10">
              <SmartLink href={section.button_url} className={button.gold}>
                {section.button_text} <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
              </SmartLink>
            </Reveal>
          )}
        </div>

        {fees.length > 0 && (
          <Reveal from="right" delay={100} className="lg:col-span-7">
            <div className="overflow-hidden rounded-2xl bg-white shadow-[0_30px_60px_-38px_rgb(7_15_32/0.55)] ring-1 ring-navy-900/10">
              <div className="flex items-center justify-between gap-4 bg-navy-950 px-6 py-5 text-white">
                <p className="font-display text-xl">Membership fees</p>
                <span className="text-xs text-navy-200">Indian rupees</span>
              </div>
              <div className="max-h-[34rem] overflow-auto">
                <table className="w-full min-w-[30rem] text-left text-[14px]">
                  <thead className="sticky top-0 z-10 bg-ivory-100 text-[11px] font-bold uppercase tracking-[0.16em] text-navy-500">
                    <tr>
                      <th scope="col" className="px-6 py-3">Grade</th>
                      <th scope="col" className="px-4 py-3 text-right">1 year</th>
                      {lifetime && <th scope="col" className="px-4 py-3 text-right">Life time</th>}
                      <th scope="col" className="px-6 py-3"><span className="sr-only">Apply</span></th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-ivory-200">
                    {fees.map((fee) => (
                      <tr key={fee.id} className="group transition hover:bg-ivory-50">
                        <th scope="row" className="px-6 py-3.5 font-normal">
                          <span className="flex flex-wrap items-center gap-2">
                            <span className="font-semibold text-navy-950">{fee.grade}</span>
                            {fee.code && <span className="rounded-full bg-navy-950 px-2 py-0.5 text-[10px] font-bold tracking-[0.12em] text-gold-300">{fee.code}</span>}
                          </span>
                          {fee.note && <span className="mt-0.5 block text-xs text-navy-400">{fee.note}</span>}
                        </th>
                        <td className="whitespace-nowrap px-4 py-3.5 text-right font-semibold tabular-nums text-navy-900">{formatFee(fee.annual_fee) ?? '—'}</td>
                        {lifetime && <td className="whitespace-nowrap px-4 py-3.5 text-right tabular-nums text-navy-700">{formatFee(fee.lifetime_fee) ?? '—'}</td>}
                        <td className="px-6 py-3.5 text-right">
                          <SmartLink href={fee.button_url || '#register'} ariaLabel={`Apply for ${fee.grade}`}
                            className="inline-flex items-center gap-1 text-xs font-semibold text-gold-700 opacity-70 transition group-hover:opacity-100">
                            Apply <Icon name="arrow-right" className="size-3.5" strokeWidth={2} />
                          </SmartLink>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              {section.body && <p className="border-t border-ivory-200 px-6 py-4 text-xs leading-5 text-navy-500">{section.body}</p>}
            </div>
          </Reveal>
        )}
      </div>
    </section>
  );
}
