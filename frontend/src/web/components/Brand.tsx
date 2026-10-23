import { useId, useState } from 'react';
import type { SiteSettings } from '@shared/types';
import { cx } from './ui';

/** Logo from Settings, or an engraved monogram crest. */
export function Crest({ settings, className = 'size-14' }: { settings?: SiteSettings; className?: string }) {
  const uid = useId().replace(/:/g, '');
  const [failed, setFailed] = useState(false);
  if (settings?.logo_url && !failed) {
    return <img src={settings.logo_url} alt="" className={cx(className, 'object-contain')} onError={() => setFailed(true)} />;
  }

  const initials = (settings?.short_name || initialsOf(settings?.site_name || 'IIP')).slice(0, 4).toUpperCase();

  return (
    <svg viewBox="0 0 64 72" className={className} aria-hidden="true">
      <defs>
        <linearGradient id={`g${uid}`} x1="0" y1="0" x2="1" y2="1">
          <stop offset="0" stopColor="#ebd8a6" />
          <stop offset="0.5" stopColor="#c09443" />
          <stop offset="1" stopColor="#dfc17a" />
        </linearGradient>
        <linearGradient id={`n${uid}`} x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stopColor="#1d355f" />
          <stop offset="1" stopColor="#070f20" />
        </linearGradient>
      </defs>
      <path d="M32 2 60 11v22c0 17.5-11.6 30.6-28 37C15.6 63.6 4 50.5 4 33V11L32 2Z" fill={`url(#n${uid})`} stroke={`url(#g${uid})`} strokeWidth="2" />
      <path d="M32 8.5 54 15.6v17.3c0 14-9 24.7-22 30.2C19 57.6 10 46.9 10 32.9V15.6L32 8.5Z" fill="none" stroke={`url(#g${uid})`} strokeOpacity="0.45" strokeWidth="0.8" />
      <path d="M17 48c4.5 3.6 9.6 5.6 15 5.6S42.5 51.6 47 48" fill="none" stroke={`url(#g${uid})`} strokeWidth="1.2" strokeLinecap="round" />
      <path d="M32 14.5l1.4 2.9 3.2.5-2.3 2.2.5 3.2-2.8-1.5-2.8 1.5.5-3.2-2.3-2.2 3.2-.5z" fill={`url(#g${uid})`} />
      <text x="32" y="41" textAnchor="middle" fontFamily="'Playfair Display Variable', Georgia, serif" fontWeight="700"
        fontSize={initials.length > 3 ? 11 : 14} letterSpacing="1" fill={`url(#g${uid})`}>{initials}</text>
    </svg>
  );
}

export function initialsOf(name: string): string {
  const words = name.split(/\s+/).filter((w) => w && !/^(of|the|and|&)$/i.test(w));
  return words.length > 1 ? words.map((w) => w[0]).join('') : name.slice(0, 3);
}
