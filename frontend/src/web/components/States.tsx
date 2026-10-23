import { Link } from 'react-router';
import { button, cx } from './ui';

export function PageSkeleton() {
  return (
    <div aria-busy="true" aria-label="Loading">
      <div className="h-72 animate-pulse bg-navy-950 sm:h-96" />
      <div className="container-page space-y-4 py-16">
        {Array.from({ length: 6 }).map((_, i) => (
          <div key={i} className="h-4 animate-pulse rounded-full bg-ivory-200" style={{ width: `${88 - i * 8}%` }} />
        ))}
      </div>
    </div>
  );
}

export function HomeSkeleton() {
  return (
    <div aria-busy="true" aria-label="Loading">
      <div className="relative h-[min(88svh,820px)] min-h-[560px] overflow-hidden bg-navy-950">
        <div className="container-page flex h-full flex-col justify-center gap-6">
          <div className="h-3 w-48 rounded-full bg-white/10" />
          <div className="h-14 w-3/4 rounded-2xl bg-white/10" />
          <div className="h-14 w-1/2 rounded-2xl bg-white/10" />
          <div className="h-4 w-2/5 rounded-full bg-white/5" />
        </div>
        <div className="absolute inset-0 animate-shimmer bg-[linear-gradient(110deg,transparent_40%,rgb(255_255_255/0.05)_50%,transparent_60%)] bg-[length:200%_100%]" />
      </div>
    </div>
  );
}

export function ErrorState({ message, onRetry }: { message?: string; onRetry?: () => void }) {
  return (
    <div className="container-page py-28 text-center">
      <p className="eyebrow justify-center">Something went wrong</p>
      <h1 className="mt-5 text-4xl font-semibold text-navy-950">We couldn’t load this page</h1>
      <p className="mt-4 text-navy-600">{message ?? 'Please check your connection and try again.'}</p>
      <div className="mt-10 flex justify-center gap-3">
        {onRetry && (
          <button type="button" onClick={onRetry} className={button.navy}>Try again</button>
        )}
        <Link to="/" className={cx(button.outline)}>Go home</Link>
      </div>
    </div>
  );
}
