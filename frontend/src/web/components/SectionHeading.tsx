import { Reveal } from './Reveal';
import { cx } from './ui';

/** Gold eyebrow + display heading with an italic gold accent ("About *Us*"). */
export function SectionHeading({
  eyebrow,
  title,
  accent,
  subtitle,
  align = 'left',
  dark = false,
  className,
  as: Heading = 'h2',
}: {
  eyebrow?: string | null;
  title?: string | null;
  accent?: string | null;
  subtitle?: string | null;
  align?: 'left' | 'center';
  dark?: boolean;
  className?: string;
  as?: 'h1' | 'h2';
}) {
  if (!eyebrow && !title && !accent && !subtitle) return null;

  return (
    <Reveal className={cx(align === 'center' && 'mx-auto text-center', 'max-w-3xl', className)}>
      {eyebrow && <p className={cx('eyebrow', dark && 'text-gold-300', align === 'center' && 'justify-center')}>{eyebrow}</p>}
      {(title || accent) && (
        <Heading className={cx('mt-3 text-3xl font-semibold leading-[1.12] sm:text-[2.6rem]', dark ? 'text-white' : 'text-navy-950')}>
          {title}
          {title && accent ? ' ' : null}
          {accent && <em className="text-gilded font-medium">{accent}</em>}
        </Heading>
      )}
      {subtitle && <p className={cx('mt-4 text-base leading-7 sm:text-lg sm:leading-8', dark ? 'text-navy-200' : 'text-navy-600')}>{subtitle}</p>}
    </Reveal>
  );
}
