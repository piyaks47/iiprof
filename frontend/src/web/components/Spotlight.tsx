import type { ElementType, ReactNode } from 'react';
import { cx } from './ui';

/** Soft gold light that follows the cursor across a card (CSS variables, no re-render). */
export function Spotlight({ as: Tag = 'div', className, children, dark = false }: { as?: ElementType; className?: string; children: ReactNode; dark?: boolean }) {
  return (
    <Tag
      className={cx('spotlight', dark && 'spotlight-dark', className)}
      onPointerMove={(e: React.PointerEvent<HTMLElement>) => {
        const rect = e.currentTarget.getBoundingClientRect();
        e.currentTarget.style.setProperty('--mx', `${e.clientX - rect.left}px`);
        e.currentTarget.style.setProperty('--my', `${e.clientY - rect.top}px`);
      }}
    >
      {children}
    </Tag>
  );
}
