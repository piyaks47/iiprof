import type { HomeSectionOf } from '@shared/types';
import { Prose } from '../components/Prose';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { SectionHeading } from '../components/SectionHeading';

/** Why join: an introduction next to the numbered objectives (Membership → Membership objectives). */
export function MembershipObjectives({ section }: { section: HomeSectionOf<'membership_objectives'> }) {
  return (
    <section id="membership_objectives" className="relative bg-white py-16 sm:py-20">
      <div className="container-page grid gap-12 lg:grid-cols-12">
        <div className="lg:col-span-5">
          <div className="lg:sticky lg:top-28">
            <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} />
            {section.body_html && (
              <Reveal delay={120} className="mt-5">
                <Prose html={section.body_html} className="prose-base [&>p:first-child]:mt-0" />
              </Reveal>
            )}
            {section.image_url && (
              <Reveal delay={200} className="relative mt-8 hidden lg:block">
                <div className="absolute -inset-2 translate-x-3 translate-y-3 rounded-[1.6rem] border border-gold-400/50" aria-hidden="true" />
                <SafeImg src={section.image_url} alt="" loading="lazy" className="relative aspect-[16/10] w-full rounded-[1.5rem] object-cover shadow-xl" />
              </Reveal>
            )}
          </div>
        </div>

        <ol className="grid gap-4 sm:grid-cols-2 lg:col-span-7">
          {section.items.map((item, i) => (
            <Reveal as="li" key={item.id} delay={(i % 2) * 100} className="h-full">
              <div className="group relative h-full overflow-hidden rounded-2xl bg-ivory-50 p-6 ring-1 ring-navy-900/5 transition duration-500 ease-out-expo hover:-translate-y-1 hover:bg-white hover:shadow-[0_26px_50px_-36px_rgb(7_15_32/0.5)]">
                <span className="text-gilded font-display text-4xl font-semibold leading-none [font-variant-numeric:lining-nums]">{String(i + 1).padStart(2, '0')}</span>
                <h3 className="mt-4 text-lg font-semibold leading-snug text-navy-950">{item.title}</h3>
                {item.description && <p className="mt-2 text-[14.5px] leading-6 text-navy-600">{item.description}</p>}
                <span className="absolute inset-x-0 bottom-0 h-0.5 origin-left scale-x-0 bg-gradient-to-r from-gold-300 via-gold-500 to-transparent transition-transform duration-700 ease-out-expo group-hover:scale-x-100" />
              </div>
            </Reveal>
          ))}
        </ol>
      </div>
    </section>
  );
}
