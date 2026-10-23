import type { HomeSectionOf } from '@shared/types';
import { Icon } from '../components/Icon';
import { Reveal } from '../components/Reveal';
import { SafeImg } from '../components/SafeImg';
import { SmartLink } from '../components/SmartLink';
import { button } from '../components/ui';
import { Backdrop } from './Backdrop';

/** Full-width call to action on a photo (Homepage → Join banner). */
export function JoinBanner({ section }: { section: HomeSectionOf<'join'> }) {
  return (
    <section id="join" className="relative py-6 sm:py-10">
      <div className="container-page">
        <Reveal from="zoom" className="relative isolate overflow-hidden rounded-[2rem] bg-navy-950 px-7 py-14 text-white shadow-[0_50px_100px_-50px_rgb(7_15_32/0.7)] sm:px-14 sm:py-20">
          <SafeImg src={section.image_url ?? undefined} alt="" loading="lazy" fallback={<Backdrop variant={2} className="-z-20" />}
            className="absolute inset-0 -z-20 size-full animate-ken-burns object-cover" />
          <div className="absolute inset-0 -z-10 bg-gradient-to-r from-navy-950 via-navy-950/85 to-navy-950/30" />
          <div className="absolute -left-20 -top-24 -z-10 size-80 rounded-full bg-gold-400/25 blur-3xl" />

          <div className="max-w-2xl">
            {section.eyebrow && <p className="eyebrow text-gold-300">{section.eyebrow}</p>}
            {(section.title || section.title_accent) && (
              <h2 className="mt-4 text-3xl font-semibold leading-[1.1] sm:text-5xl">
                {section.title}
                {section.title && section.title_accent ? ' ' : null}
                {section.title_accent && <em className="text-gilded font-medium">{section.title_accent}</em>}
              </h2>
            )}
            {section.subtitle && <p className="mt-5 text-base leading-7 text-navy-100 sm:text-lg sm:leading-8">{section.subtitle}</p>}
            {section.button_text && (
              <div className="mt-9 flex flex-wrap gap-3">
                <SmartLink href={section.button_url} className={button.gold}>
                  {section.button_text}
                  <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
                </SmartLink>
              </div>
            )}
          </div>
        </Reveal>
      </div>
    </section>
  );
}
