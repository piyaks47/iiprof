import type { CSSProperties, ElementType, ReactNode } from 'react';
import { useInView } from '../lib/motion';

type Direction = 'up' | 'left' | 'right' | 'zoom';

/** Fades + slides its content in when it scrolls into view. `delay` in ms staggers siblings. */
export function Reveal({
  as: Tag = 'div',
  from = 'up',
  delay = 0,
  className,
  children,
  ...rest
}: {
  as?: ElementType;
  from?: Direction;
  delay?: number;
  className?: string;
  children: ReactNode;
  id?: string;
}) {
  const [ref, shown] = useInView<HTMLElement>();

  return (
    <Tag
      ref={ref}
      data-reveal={from === 'up' ? '' : from}
      data-shown={shown ? '' : undefined}
      style={{ '--reveal-delay': `${delay}ms` } as CSSProperties}
      className={className}
      {...rest}
    >
      {children}
    </Tag>
  );
}
