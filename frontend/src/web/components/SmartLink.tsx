import type { ReactNode } from 'react';
import { Link } from 'react-router';
import { useSite } from '@shared/hooks/queries';
import { useEnquiry } from './Enquiry';

/** Paths served by Laravel, not by the React router. */
const SERVER_PATHS = /^\/(admin|api|storage|media|build|up)(\/|$)/;

/**
 * Renders any CMS link correctly:
 *  - "#enquiry" opens the enquiry drawer, "#register" opens the membership form (or Settings → Register link)
 *  - site paths use client-side navigation, /admin etc. do a full page load
 *  - external links open in a new tab when asked (or always for other domains)
 */
export function SmartLink({
  href,
  newTab = false,
  className,
  children,
  onClick,
  ariaLabel,
  fallback = 'span',
  current = false,
  subject,
}: {
  href: string | null | undefined;
  newTab?: boolean;
  className?: string;
  children: ReactNode;
  onClick?: () => void;
  ariaLabel?: string;
  /** What to render without a link: plain text, or a focusable button (menu parents). */
  fallback?: 'span' | 'button';
  /** Marks the link to the page being viewed (aria-current="page"). */
  current?: boolean;
  /** Subject the enquiry form starts with when the link is "#enquiry". */
  subject?: string;
}) {
  const ariaCurrent = current ? ('page' as const) : undefined;
  const enquiry = useEnquiry();
  const { data: site } = useSite();
  const registerUrl = site?.settings.register_url;

  if (!href) {
    return fallback === 'button' ? (
      <button type="button" className={className} aria-haspopup="true" aria-current={current ? 'true' : undefined} onClick={onClick}>
        {children}
      </button>
    ) : (
      <span className={className}>{children}</span>
    );
  }

  if (href === '#enquiry' || (href === '#register' && !registerUrl)) {
    return (
      <button
        type="button"
        className={className}
        aria-label={ariaLabel}
        onClick={() => {
          onClick?.();
          enquiry.open(href === '#register' ? 'Membership registration' : subject);
        }}
      >
        {children}
      </button>
    );
  }

  const target = href === '#register' && registerUrl ? registerUrl : href;
  const external = /^https?:\/\//i.test(target) && !target.startsWith(window.location.origin);
  const blank = newTab || external;

  if (target.startsWith('/') && !SERVER_PATHS.test(target) && !blank) {
    return (
      <Link to={target} className={className} onClick={onClick} aria-label={ariaLabel} aria-current={ariaCurrent}>
        {children}
      </Link>
    );
  }

  return (
    <a
      href={target}
      className={className}
      onClick={onClick}
      aria-label={ariaLabel}
      aria-current={ariaCurrent}
      {...(blank ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
    >
      {children}
    </a>
  );
}
