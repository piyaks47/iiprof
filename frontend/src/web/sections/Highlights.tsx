import type { HomeSection } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SectionHeading } from '../components/SectionHeading';
import { cx } from '../components/ui';
import { Spotlight } from '../components/Spotlight';

export function Highlights({ section }: { section: Extract<HomeSection, { key: 'highlights' }> }) {
  const items = section.items;

  return (
    <section id="highlights" className="relative isolate overflow-hidden bg-navy-950 py-16 text-white sm:py-20">
      {section.image_url && <img src={section.image_url} alt="" className="absolute inset-0 -z-20 size-full object-cover opacity-25" />}
      <div className="bg-lattice absolute inset-0 -z-10 opacity-60 [mask-image:radial-gradient(ellipse_at_center,black,transparent_75%)]" />
      <div className="absolute left-1/2 top-0 -z-10 h-[30rem] w-[50rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-gold-500/15 blur-3xl" />

      <div className="container-page">
        <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} subtitle={section.subtitle} align="center" dark />

        <ul className={cx('mt-10 grid gap-5', items.length >= 3 ? 'md:grid-cols-3' : items.length === 2 ? 'md:grid-cols-2' : '')}>
          {items.map((item, i) => (
            <Reveal as="li" key={item.id} delay={i * 120} className="h-full">
              <Spotlight dark className="group h-full overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] p-8 backdrop-blur-sm transition duration-500 ease-out-expo hover:-translate-y-2 hover:border-gold-400/40 hover:bg-white/[0.06] hover:shadow-[0_30px_80px_-30px_rgb(209_170_90/0.45)] sm:p-8">
                <span className="pointer-events-none absolute -right-2 -top-6 font-display text-[5.5rem] font-bold leading-none text-transparent [-webkit-text-stroke:1px_rgb(209_170_90/0.18)] transition duration-500 group-hover:[-webkit-text-stroke:1px_rgb(209_170_90/0.4)]">
                  {String(i + 1).padStart(2, '0')}
                </span>
                <span className="relative flex size-12 items-center justify-center rounded-full bg-gradient-to-br from-gold-300 to-gold-600 text-navy-950 shadow-[0_10px_30px_-10px_rgb(209_170_90/0.8)] transition duration-500 group-hover:scale-110 group-hover:rotate-[8deg]">
                  <Icon name={item.icon} className="size-6" strokeWidth={1.75} />
                </span>
                <h3 className="relative mt-6 text-xl font-semibold leading-snug">{item.title}</h3>
                {item.description && <p className="relative mt-2.5 text-[15px] leading-7 text-navy-200">{item.description}</p>}
                <span className="absolute inset-x-0 bottom-0 h-px origin-left scale-x-0 bg-gradient-to-r from-gold-300 via-gold-500 to-transparent transition-transform duration-700 ease-out-expo group-hover:scale-x-100" />
              </Spotlight>
            </Reveal>
          ))}
        </ul>
      </div>
    </section>
  );
}
