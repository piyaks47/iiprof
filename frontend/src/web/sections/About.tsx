import { useSite } from '@shared/hooks/queries';
import type { HomeSection } from '@shared/types';
import { Crest } from '../components/Brand';
import { Icon } from '../components/Icon';
import { Prose } from '../components/Prose';
import { Reveal } from '../components/Reveal';
import { SectionHeading } from '../components/SectionHeading';
import { SmartLink } from '../components/SmartLink';
import { button } from '../components/ui';
import { Backdrop } from './Backdrop';
import { SafeImg } from '../components/SafeImg';
import { firstLink } from '@shared/lib/menu';

export function About({ section }: { section: Extract<HomeSection, { key: 'about' }> }) {
  const { data: site } = useSite();
  // Chips: top-level menu items that open a drop-down (Hunar, Jeevika, Khazana …).
  const pillars = (site?.menu ?? [])
    .filter((m) => m.children.length > 0 && !['about us', 'more'].includes(m.label.toLowerCase()))
    .slice(0, 6);

  return (
    <section id="about" className="relative overflow-hidden py-16 sm:py-20">
      <div className="absolute -left-40 top-20 -z-10 size-[30rem] rounded-full bg-gold-200/30 blur-3xl" />
      <div className="container-page grid items-center gap-12 lg:grid-cols-12 lg:gap-16">
        <Reveal from="left" className="relative mx-auto w-full max-w-[18rem] sm:max-w-sm lg:col-span-4 lg:max-w-none">
          <div className="absolute -inset-2 translate-x-4 translate-y-4 rounded-[2rem] border border-gold-400/50" aria-hidden="true" />
          <SafeImg
            src={section.image_url ?? undefined}
            alt=""
            className="relative aspect-square w-full rounded-[2rem] bg-white object-contain p-6 shadow-2xl ring-1 ring-navy-900/5"
            fallback={
            <div className="relative aspect-square overflow-hidden rounded-[2rem] shadow-2xl">
              <Backdrop variant={1} />
              <div className="relative flex h-full flex-col items-center justify-center p-10 text-center">
                <Crest settings={site?.settings} className="h-24 w-auto drop-shadow-[0_20px_40px_rgb(0_0_0/0.4)]" />
                {site?.settings.tagline && <p className="eyebrow mt-6 text-gold-300 before:hidden">{site.settings.tagline}</p>}
                <p className="mt-3 max-w-xs font-display text-xl leading-snug text-white">{site?.settings.site_name}</p>
              </div>
            </div>
            }
          />
        </Reveal>

        <div className="lg:col-span-8">
          <SectionHeading eyebrow={section.eyebrow} title={section.title} accent={section.title_accent} />
          {section.subtitle && (
            <Reveal delay={100}>
              <p className="mt-5 border-l-2 border-gold-400 pl-5 font-display text-lg italic leading-relaxed text-navy-800 sm:text-xl">{section.subtitle}</p>
            </Reveal>
          )}
          {section.body_html && (
            <Reveal delay={180} className="mt-6">
              <Prose html={section.body_html} className="prose-base max-w-none [&>p:first-child]:mt-0" />
            </Reveal>
          )}
          {(section.button_text || pillars.length > 0) && (
            <Reveal delay={260} className="mt-8 flex flex-col gap-6 border-t border-ivory-200 pt-7 sm:flex-row sm:items-center sm:justify-between">
              {pillars.length > 0 && (
                <div>
                  <p className="text-[10px] font-bold uppercase tracking-[0.25em] text-gold-600">Our platforms</p>
                  <ul className="mt-3 flex flex-wrap gap-2">
                    {pillars.map((p) => (
                      <li key={p.id}>
                        <SmartLink href={firstLink(p)} className="inline-flex rounded-full bg-white px-3.5 py-1.5 text-xs font-semibold text-navy-900 ring-1 ring-navy-900/10 transition duration-300 hover:-translate-y-0.5 hover:bg-navy-950 hover:text-gold-200 hover:ring-navy-950">
                          {p.label}
                        </SmartLink>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
              {section.button_text && (
                <SmartLink href={section.button_url} className={`${button.navy} shrink-0`}>
                  {section.button_text}
                  <Icon name="arrow-right" className="size-4 text-gold-300 transition group-hover/btn:translate-x-1" strokeWidth={2} />
                </SmartLink>
              )}
            </Reveal>
          )}
        </div>
      </div>
    </section>
  );
}
