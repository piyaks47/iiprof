import type { MenuItem } from '../types';

/** Path from the top of the menu to the item linking to `path` (for breadcrumbs), or []. */
export function findMenuTrail(menu: MenuItem[], path: string): MenuItem[] {
  const normalise = (url: string) => url.replace(/\/+$/, '') || '/';
  const target = normalise(path);

  for (const item of menu) {
    if (item.url && normalise(item.url) === target) return [item];
    const below = findMenuTrail(item.children, path);
    if (below.length) return [item, ...below];
  }

  return [];
}

/** Turn a site path (/khazana/lenders/banks-nbfc) into the page slug the API uses (khazana-lenders-banks-nbfc). */
export function pathToSlug(path: string): string {
  return path.replace(/^\/+|\/+$/g, '').split('/').filter(Boolean).join('-').toLowerCase();
}

/** Split a value like "250+" into a number to animate and the text around it. */
export function parseStat(value: string): { prefix: string; number: number | null; suffix: string; decimals: number } {
  const match = value.match(/^(\D*?)(\d[\d,]*(?:\.\d+)?)(.*)$/);
  if (!match || /^\d+\/\d+$/.test(value)) return { prefix: '', number: null, suffix: value, decimals: 0 };
  const raw = match[2].replace(/,/g, '');
  return { prefix: match[1], number: Number(raw), suffix: match[3], decimals: raw.includes('.') ? raw.split('.')[1].length : 0 };
}

/** First link found in an item or its descendants (for parents without a link of their own). */
export function firstLink(item: MenuItem): string | null {
  if (item.url) return item.url;
  for (const child of item.children) {
    const url = firstLink(child);
    if (url) return url;
  }
  return null;
}

export const hasGrandchildren = (item: MenuItem) => item.children.some((c) => c.children.length > 0);

/** Featured image for the last item of a menu trail: its own, or the nearest one above it. */
export function menuImage(trail: MenuItem[]): string | null {
  for (let i = trail.length - 1; i >= 0; i--) {
    if (trail[i].image_url) return trail[i].image_url ?? null;
  }
  return null;
}
