/** Shared class names for buttons so links and buttons look the same. */
const base =
  'group/btn relative inline-flex items-center justify-center gap-2.5 overflow-hidden rounded-full font-semibold tracking-wide transition duration-300 ease-out focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60';

export const button = {
  gold: `${base} bg-gradient-to-r from-gold-500 via-gold-400 to-gold-500 bg-[length:200%_100%] bg-left text-navy-950 shadow-[0_10px_30px_-10px_rgb(192_148_67/0.7)] hover:bg-right hover:shadow-[0_14px_36px_-10px_rgb(192_148_67/0.85)] px-7 py-3.5 text-sm`,
  navy: `${base} bg-navy-900 text-white shadow-[0_10px_30px_-12px_rgb(7_15_32/0.6)] hover:bg-navy-800 px-7 py-3.5 text-sm`,
  ghostLight: `${base} text-white ring-1 ring-inset ring-white/35 hover:bg-white/10 hover:ring-white/60 px-7 py-3.5 text-sm backdrop-blur-sm`,
  outline: `${base} text-navy-900 ring-1 ring-inset ring-navy-900/20 hover:ring-navy-900/50 hover:bg-navy-900/[0.03] px-6 py-3 text-sm`,
  small: 'px-4 py-2 text-xs',
};

export function cx(...classes: Array<string | false | null | undefined>): string {
  return classes.filter(Boolean).join(' ');
}
