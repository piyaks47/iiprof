import { useState, type ImgHTMLAttributes, type ReactNode } from 'react';

/** <img> that renders `fallback` instead when the image cannot be loaded. */
export function SafeImg({ fallback = null, src, ...props }: ImgHTMLAttributes<HTMLImageElement> & { fallback?: ReactNode }) {
  const [failed, setFailed] = useState<string | null>(null);

  if (!src || failed === src) return <>{fallback}</>;

  return <img src={src} decoding="async" onError={() => setFailed(src)} {...props} />;
}
