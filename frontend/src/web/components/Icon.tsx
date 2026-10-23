import { ICON_PATHS } from '../lib/icon-paths';

export function Icon({ name, className = 'size-5', strokeWidth = 1.5, title }: { name: string; className?: string; strokeWidth?: number; title?: string }) {
  const d = ICON_PATHS[name] ?? ICON_PATHS['sparkles'];

  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
      strokeWidth={strokeWidth}
      stroke="currentColor"
      className={className}
      aria-hidden={title ? undefined : true}
      role={title ? 'img' : undefined}
    >
      {title ? <title>{title}</title> : null}
      {d.split(/(?= M)/).map((part, i) => (
        <path key={i} strokeLinecap="round" strokeLinejoin="round" d={part.trim()} />
      ))}
    </svg>
  );
}
