import { useScrollY } from '../lib/motion';
import { Icon } from './Icon';
import { cx } from './ui';

/** Appears after scrolling; the ring shows how far down the page you are. */
export function BackToTop() {
  const y = useScrollY();
  const max = typeof document === 'undefined' ? 1 : Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
  const progress = Math.min(1, y / max);
  const visible = y > 700;
  const circumference = 2 * Math.PI * 22;

  return (
    <button
      type="button"
      onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}
      className={cx(
        'group fixed bottom-6 right-6 z-40 flex size-12 items-center justify-center rounded-full bg-navy-950 text-gold-300 shadow-xl transition-all duration-500 ease-out-expo hover:-translate-y-1 hover:text-white',
        visible ? 'translate-y-0 opacity-100' : 'pointer-events-none translate-y-6 opacity-0',
      )}
      aria-label="Back to top"
      tabIndex={visible ? 0 : -1}
    >
      <svg viewBox="0 0 48 48" className="absolute inset-0 -rotate-90" aria-hidden="true">
        <circle cx="24" cy="24" r="22" fill="none" stroke="currentColor" strokeOpacity="0.15" strokeWidth="2" />
        <circle cx="24" cy="24" r="22" fill="none" stroke="#d1aa5a" strokeWidth="2" strokeLinecap="round"
          strokeDasharray={circumference} strokeDashoffset={circumference * (1 - progress)} />
      </svg>
      <Icon name="arrow-up" className="size-4 transition group-hover:-translate-y-0.5" strokeWidth={2} />
    </button>
  );
}
