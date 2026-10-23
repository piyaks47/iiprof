/** React 19 hoists <title>/<meta> into <head> automatically. */
export function Seo({ title, description, siteName }: { title?: string; description?: string | null; siteName?: string }) {
  const full = title && siteName && title !== siteName ? `${title} · ${siteName}` : title || siteName || '';

  return (
    <>
      <title>{full}</title>
      {description ? <meta name="description" content={description} /> : null}
      <meta property="og:title" content={full} />
      {description ? <meta property="og:description" content={description} /> : null}
    </>
  );
}
