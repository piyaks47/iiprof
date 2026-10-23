import { Link } from 'react-router';
import type { Site } from '@shared/types';
import { Crest } from './Brand';
import { Icon } from './Icon';
import { Reveal } from './Reveal';
import { SmartLink } from './SmartLink';
import { button, cx } from './ui';

export function Footer({ site }: { site?: Site }) {
  const s = site?.settings;
  const year = new Date().getFullYear();

  return (
    <footer className="relative isolate overflow-hidden bg-navy-950 text-navy-200">
      <div className="bg-lattice absolute inset-0 -z-10 opacity-50 [mask-image:radial-gradient(ellipse_at_top,black,transparent_70%)]" />
      <div className="absolute -top-40 left-1/2 -z-10 h-80 w-[60rem] -translate-x-1/2 rounded-full bg-gold-500/10 blur-3xl" />

      {/* Call to action */}
      <div className="container-page">
        <Reveal className="flex flex-col items-start justify-between gap-8 border-b border-white/10 py-14 md:flex-row md:items-center">
          <div>
            <p className="eyebrow text-gold-300">{s?.tagline || 'Join us'}</p>
            <h2 className="mt-4 max-w-2xl text-3xl font-semibold leading-tight text-white sm:text-4xl">
              Become part of a network that <em className="text-gilded not-italic">grows together</em>.
            </h2>
          </div>
          <div className="flex shrink-0 flex-wrap gap-3">
            <SmartLink href="#register" className={button.gold}>
              Register as a member <Icon name="arrow-right" className="size-4 transition group-hover/btn:translate-x-1" strokeWidth={2} />
            </SmartLink>
            <SmartLink href="#enquiry" className={button.ghostLight}>Make an enquiry</SmartLink>
          </div>
        </Reveal>
      </div>

      <div className="container-page grid gap-12 py-16 lg:grid-cols-12">
        <div className="lg:col-span-4">
          <Link to="/" className="flex items-center gap-4">
            <Crest settings={s} className="size-14" />
            <span className="font-display text-xl font-semibold leading-tight text-white">{s?.site_name}</span>
          </Link>
          {s?.meta_description && <p className="mt-6 max-w-sm text-sm leading-7 text-navy-300">{s.meta_description}</p>}
          <ul className="mt-6 space-y-3 text-sm">
            {s?.address && (
              <li className="flex gap-3">
                <Icon name="map-pin" className="mt-0.5 size-4 shrink-0 text-gold-400" />
                <span className="whitespace-pre-line">{s.address}</span>
              </li>
            )}
            {s?.contact_email && (
              <li className="flex gap-3">
                <Icon name="envelope" className="mt-0.5 size-4 shrink-0 text-gold-400" />
                <a href={`mailto:${s.contact_email}`} className="link-underline hover:text-white">{s.contact_email}</a>
              </li>
            )}
            {s?.contact_phone && (
              <li className="flex gap-3">
                <Icon name="phone" className="mt-0.5 size-4 shrink-0 text-gold-400" />
                <a href={`tel:${s.contact_phone.replace(/[^\d+]/g, '')}`} className="link-underline hover:text-white">{s.contact_phone}</a>
              </li>
            )}
          </ul>
        </div>

        <div className={cx('grid grid-cols-2 gap-x-6 gap-y-10 lg:col-span-8', (site?.footer.length ?? 0) >= 3 && 'md:grid-cols-3')}>
          {(site?.footer ?? []).map((column, i) => (
            <Reveal key={column.id} delay={i * 90}>
              <h3 className="font-sans text-[11px] font-bold uppercase tracking-[0.25em] text-gold-300">{column.title}</h3>
              <ul className="mt-5 space-y-3 text-sm">
                {column.links.map((link) => (
                  <li key={link.id}>
                    <SmartLink href={link.url} newTab={link.new_tab} className="group inline-flex items-center gap-1.5 text-navy-200 transition hover:text-white">
                      <span className="-mr-1.5 h-px w-0 bg-gold-400 transition-all duration-500 ease-out-expo group-hover:mr-0 group-hover:w-3" />
                      {link.label}
                      {link.new_tab && <Icon name="arrow-up-right" className="size-3 opacity-50" strokeWidth={2} />}
                    </SmartLink>
                  </li>
                ))}
              </ul>
            </Reveal>
          ))}
        </div>
      </div>

      <div className="border-t border-white/10">
        <div className="container-page flex flex-col gap-4 py-6 text-xs text-navy-400 md:flex-row md:items-center md:justify-between">
          <p>
            © {year} {s?.site_name}. {s?.footer_text || 'All rights reserved.'}
          </p>
          {site?.socials.length ? (
            <ul className="flex flex-wrap gap-2">
              {site.socials.map((social) => (
                <li key={social.key}>
                  <a href={social.url} target="_blank" rel="noopener noreferrer"
                    className="inline-flex rounded-full px-3 py-1.5 font-semibold uppercase tracking-wider text-navy-200 ring-1 ring-white/10 transition hover:bg-gold-400 hover:text-navy-950 hover:ring-gold-400">
                    {social.label}
                  </a>
                </li>
              ))}
            </ul>
          ) : null}
        </div>
      </div>
    </footer>
  );
}
