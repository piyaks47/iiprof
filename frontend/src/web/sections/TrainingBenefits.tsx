import type { HomeSectionOf } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { button } from '../components/ui';

/** Why train with IIP: a dark band with the benefits (Training → Why train with IIP). */
export function TrainingBenefits({ section }: { section: HomeSectionOf<'training_benefits'> }) {
  return (
    <section id="training_benefits" className="relative isolate overflow-hidden bg-navy-950 py-16 text-white sm:py-24">
      <SafeImg src={section.image_url ?? undefined} alt="" loading="lazy" className="absolute inset-0 -z-20 size-full object-cover opacity-15" />
      <div className="absolute inset-0 -z-10 bg-gradient-to-br from-navy-950 via-navy-950/95 to-navy-900/80" />
      <div className="bg-lattice absolute inset-0 -z-10 opacity-40 [mask-image:radial-gradient(ellipse_at_left,black,transparent_70%)]" />

      <div className="container-page grid gap-12 lg:grid-cols-12">
        <div className="lg:col-span-4">
          <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} dark />
          {section.button_text && (
            <Reveal delay={200} className="mt-8">
              <SmartLink href={section.button_url} className={button.gold}>
                {section.button_text} <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
              </SmartLink>
            </Reveal>
          )}
        </div>
        <ul className="grid gap-x-8 gap-y-9 sm:grid-cols-2 lg:col-span-8">
          {section.items.map((item, i) => (
            <Reveal as="li" key={item.id} delay={(i % 2) * 100} className="group flex gap-5">
              <span className="flex size-12 shrink-0 items-center justify-center rounded-full text-gold-300 ring-1 ring-gold-400/40 transition duration-500 group-hover:bg-gold-400 group-hover:text-navy-950">
                <Icon name={item.icon || 'check'} className="size-5" strokeWidth={1.75} />
              </span>
              <div>
                <h3 className="text-lg font-semibold">{item.title}</h3>
                {item.description && <p className="mt-1.5 text-[14.5px] leading-6 text-navy-200">{item.description}</p>}
              </div>
            </Reveal>
          ))}
        </ul>
      </div>
    </section>
  );
}
