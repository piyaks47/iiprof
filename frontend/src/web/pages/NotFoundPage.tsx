import { Link } from 'react-router';
import { Seo } from '../components/Seo';
import { SmartLink } from '../components/SmartLink';
import { button } from '../components/ui';

export function NotFoundPage() {
  return (
    <section className="container-page flex min-h-[60vh] flex-col items-center justify-center py-28 text-center">
      <Seo title="Page not found" />
      <p className="text-gilded animate-shimmer font-display text-[8rem] font-semibold leading-none sm:text-[11rem]">404</p>
      <h1 className="mt-4 text-3xl font-semibold text-navy-950 sm:text-4xl">Page not found</h1>
      <p className="mt-4 max-w-md text-navy-600">The page you are looking for has moved or no longer exists.</p>
      <div className="mt-10 flex flex-wrap justify-center gap-3">
        <Link to="/" className={button.navy}>Back to home</Link>
        <SmartLink href="#enquiry" className={button.outline}>Contact us</SmartLink>
      </div>
    </section>
  );
}
