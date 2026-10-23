import type { MouseEvent } from 'react';
import { useNavigate } from 'react-router';
import { useSite } from '@shared/hooks/queries';
import { useEnquiry } from './Enquiry';
import { cx } from './ui';

/** Paths served by Laravel, not by the React router (same as SmartLink). */
const SERVER_PATHS = /^\/(admin|api|storage|media|build|up)(\/|$)/;

/**
 * Sanitised HTML from the API (Laravel strips raw HTML and unsafe links). Links inside behave like
 * SmartLink: "#register" / "#enquiry" open the forms, site paths navigate without a page reload.
 */
export function Prose({ html, className }: { html: string; className?: string }) {
  const navigate = useNavigate();
  const enquiry = useEnquiry();
  const { data: site } = useSite();

  const onClick = (e: MouseEvent<HTMLDivElement>) => {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    const link = (e.target as HTMLElement).closest('a');
    const href = link?.getAttribute('href');
    if (!link || !href || link.target === '_blank') return;

    if (href === '#enquiry' || href === '#register') {
      e.preventDefault();
      const registerUrl = site?.settings.register_url;
      if (href === '#register' && registerUrl) window.location.assign(registerUrl);
      else enquiry.open(href === '#register' ? 'Membership registration' : undefined);
      return;
    }
    if (href.startsWith('/') && !href.startsWith('//') && !SERVER_PATHS.test(href)) {
      e.preventDefault();
      navigate(href);
    }
  };

  return (
    <div
      onClick={onClick}
      className={cx('prose prose-lg prose-executive max-w-none prose-headings:tracking-tight prose-a:font-semibold prose-a:no-underline hover:prose-a:underline prose-img:mx-auto prose-img:max-h-[26rem] prose-img:w-auto prose-img:rounded-2xl prose-img:shadow-[0_30px_60px_-30px_rgb(7_15_32/0.45)]', className)}
      dangerouslySetInnerHTML={{ __html: html }}
    />
  );
}
