import { cx } from '../components/ui';

const palettes = [
  'from-navy-950 via-navy-900 to-navy-700',
  'from-navy-950 via-[#13233f] to-[#2b2a3f]',
  'from-[#06101f] via-navy-800 to-[#153a56]',
];

/** Elegant generated background for slides and cards without a photo. */
export function Backdrop({ variant = 0, className, rings = true }: { variant?: number; className?: string; rings?: boolean }) {
  return (
    <div className={cx('absolute inset-0 overflow-hidden bg-gradient-to-br', palettes[variant % palettes.length], className)} aria-hidden="true">
      <div className="bg-lattice absolute inset-0 opacity-70 [mask-image:radial-gradient(ellipse_at_70%_40%,black,transparent_75%)]" />
      <div className="absolute -right-32 top-1/4 size-[36rem] animate-float rounded-full bg-gold-500/15 blur-3xl" />
      <div className="absolute -bottom-40 left-1/4 size-[28rem] animate-float rounded-full bg-navy-400/20 blur-3xl [animation-delay:-3s]" />
      {rings && (
        <svg viewBox="0 0 600 600" className="absolute -right-1/3 top-1/2 h-[130%] -translate-y-1/2 animate-spin-slow text-gold-300 opacity-[0.16] md:-right-[12%] md:h-[125%]">
          {[280, 240, 200, 160].map((r, i) => (
            <circle key={r} cx="300" cy="300" r={r} fill="none" stroke="currentColor" strokeWidth={i === 0 ? 1.2 : 0.6} strokeDasharray={i % 2 ? '2 10' : undefined} />
          ))}
          {Array.from({ length: 24 }).map((_, i) => {
            const a = (i / 24) * Math.PI * 2;
            return <line key={i} x1={300 + Math.cos(a) * 280} y1={300 + Math.sin(a) * 280} x2={300 + Math.cos(a) * 292} y2={300 + Math.sin(a) * 292} stroke="currentColor" strokeWidth="1" />;
          })}
        </svg>
      )}
      <div className="bg-grain absolute inset-0" />
    </div>
  );
}
