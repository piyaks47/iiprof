import { useSite } from '@shared/hooks/queries';
import type { HomeSection } from '@shared/types';
import { Icon } from '../components/Icon';
import { Prose } from '../components/Prose';
import { Reveal } from '../components/Reveal';
import { SmartLink } from '../components/SmartLink';
import { button } from '../components/ui';
import { Backdrop } from './Backdrop';
import { SafeImg } from '../components/SafeImg';

export function Contact({ section }: { section: Extract<HomeSection, { key: 'contact' }> }) {
  const { data: site } = useSite();
  const s = site?.settings;

  return (
    <section id="contact" className="relative overflow-hidden py-16 sm:py-20">
      <div className="absolute right-0 top-0 -z-10 size-[34rem] rounded-full bg-gold-100/60 blur-3xl" />
      <div className="container-page grid gap-8 lg:grid-cols-12 lg:gap-14">
        <Reveal from="left" className="group/contact relative isolate overflow-hidden rounded-[2rem] p-7 text-white shadow-2xl sm:p-10 lg:col-span-5">
          <Backdrop variant={2} className="-z-10" rings={false} />
          {section.image_url && (
            <>
              <SafeImg src={section.image_url} alt="" loading="lazy" className="absolute inset-0 -z-10 size-full object-cover opacity-35 transition duration-[1600ms] ease-out-expo group-hover/contact:scale-105" />
              <div className="absolute inset-0 -z-10 bg-gradient-to-br from-navy-950/90 via-navy-900/80 to-navy-800/55" />
            </>
          )}
          {section.eyebrow && <p className="eyebrow text-gold-300">{section.eyebrow}</p>}
          <h2 className="mt-4 text-3xl font-semibold leading-tight sm:text-[2.4rem]">{section.title || 'Get in touch'}</h2>

          <ul className="mt-8 space-y-5 text-[15px]">
            {s?.address && (
              <li className="flex gap-4">
                <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-white/10 text-gold-300 ring-1 ring-white/15"><Icon name="map-pin" /></span>
                <span className="whitespace-pre-line leading-7 text-navy-100">{s.address}</span>
              </li>
            )}
            {s?.contact_email && (
              <li className="flex items-center gap-4">
                <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-white/10 text-gold-300 ring-1 ring-white/15"><Icon name="envelope" /></span>
                <a href={`mailto:${s.contact_email}`} className="link-underline text-white">{s.contact_email}</a>
              </li>
            )}
            {s?.contact_phone && (
              <li className="flex items-center gap-4">
                <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-white/10 text-gold-300 ring-1 ring-white/15"><Icon name="phone" /></span>
                <a href={`tel:${s.contact_phone.replace(/[^\d+]/g, '')}`} className="link-underline text-white">{s.contact_phone}</a>
              </li>
            )}
          </ul>

          {site?.socials.length ? (
            <div className="mt-8 border-t border-white/10 pt-6">
              <p className="text-[11px] font-bold uppercase tracking-[0.25em] text-navy-300">Follow us</p>
              <ul className="mt-4 flex flex-wrap gap-2">
                {site.socials.map((social) => (
                  <li key={social.key}>
                    <a href={social.url} target="_blank" rel="noopener noreferrer" className="inline-flex rounded-full px-4 py-2 text-xs font-semibold text-white ring-1 ring-white/20 transition hover:bg-gold-400 hover:text-navy-950 hover:ring-gold-400">
                      {social.label}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          ) : null}
        </Reveal>

        <Reveal from="right" delay={120} className="flex flex-col justify-center lg:col-span-7">
          <svg viewBox="0 0 64 48" className="h-10 w-14 text-gold-400" aria-hidden="true">
            <path fill="currentColor" d="M0 48V28C0 12.5 8.2 3.2 24.6 0l2.6 5.5C18.4 8.4 14 13.6 13.4 21H26v27H0Zm37.6 0V28c0-15.5 8.2-24.8 24.6-28l2.6 5.5C56 8.4 51.6 13.6 51 21h12.6v27H37.6Z" opacity="0.9" />
          </svg>
          {section.body_html ? (
            <blockquote className="mt-6">
              <Prose html={section.body_html} className="prose-lg font-display italic leading-relaxed text-navy-900 prose-p:leading-[1.6] sm:prose-xl" />
            </blockquote>
          ) : null}
          {section.subtitle && (
            <p className="mt-6 flex items-center gap-4 text-sm font-semibold uppercase tracking-[0.2em] text-navy-600">
              <span className="h-px w-12 bg-gold-400" /> {section.subtitle}
            </p>
          )}
          {section.button_text && (
            <div className="mt-8">
              <SmartLink href={section.button_url || '#enquiry'} className={button.gold}>
                {section.button_text}
                <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
              </SmartLink>
            </div>
          )}
        </Reveal>
      </div>
    </section>
  );
}
