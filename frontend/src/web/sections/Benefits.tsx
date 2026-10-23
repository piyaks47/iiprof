import type { HomeSectionOf } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { Spotlight } from '../components/Spotlight';
import { button } from '../components/ui';

/** What members get, as an icon grid (Homepage → Membership benefits). */
export function Benefits({ section }: { section: HomeSectionOf<'benefits'> }) {
  return (
    <section id="benefits" className="relative overflow-hidden py-16 sm:py-20">
      <div className="absolute -right-40 top-10 -z-10 size-[28rem] rounded-full bg-gold-200/30 blur-3xl" />
      <div className="container-page grid gap-12 lg:grid-cols-12">
        <div className="lg:col-span-4">
          <div className="lg:sticky lg:top-28">
            <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} />
            {section.button_text && (
              <Reveal delay={200} className="mt-8">
                <SmartLink href={section.button_url} className={button.navy}>
                  {section.button_text}
                  <Icon name="arrow-right" className="size-4 text-gold-300 transition group-hover/btn:translate-x-1" strokeWidth={2} />
                </SmartLink>
              </Reveal>
            )}
          </div>
        </div>

        <ul className="grid gap-4 sm:grid-cols-2 lg:col-span-8 xl:grid-cols-3">
          {section.items.map((item, i) => (
            <Reveal as="li" key={item.id} delay={(i % 3) * 90} className="h-full">
              <Spotlight className="group h-full rounded-2xl bg-white p-6 shadow-[0_22px_44px_-34px_rgb(7_15_32/0.45)] ring-1 ring-navy-900/5 transition duration-500 ease-out-expo hover:-translate-y-1 hover:ring-gold-400/50">
                <span className="flex size-12 items-center justify-center rounded-xl bg-ivory-100 text-gold-600 ring-1 ring-gold-400/30 transition duration-500 group-hover:bg-navy-950 group-hover:text-gold-300 group-hover:ring-navy-950">
                  <Icon name={item.icon || 'star'} className="size-6" strokeWidth={1.6} />
                </span>
                <h3 className="mt-5 text-lg font-semibold leading-snug text-navy-950">{item.title}</h3>
                {item.description && <p className="mt-2 text-[14.5px] leading-6 text-navy-600">{item.description}</p>}
              </Spotlight>
            </Reveal>
          ))}
        </ul>
      </div>
    </section>
  );
}
