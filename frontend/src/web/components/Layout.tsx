import { Outlet, ScrollRestoration, useLocation } from 'react-router';
import { useSite } from '@shared/hooks/queries';
import { BackToTop } from './BackToTop';
import { EnquiryProvider } from './Enquiry';
import { Footer } from './Footer';
import { Header } from './Header';
import { TopBar } from './TopBar';

export function Layout() {
  const { data: site } = useSite();
  const { pathname } = useLocation();

  return (
    <EnquiryProvider>
      <div className="flex min-h-screen flex-col overflow-x-clip">
        <a href="#main" className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[70] focus:rounded-full focus:bg-white focus:px-5 focus:py-2.5 focus:text-sm focus:font-semibold focus:shadow-lg">
          Skip to content
        </a>
        <TopBar site={site} />
        <Header site={site} />
        <main id="main" className="flex-1">
          <div key={pathname} className="animate-fade-in">
            <Outlet />
          </div>
        </main>
        <Footer site={site} />
        <BackToTop />
        <ScrollRestoration />
      </div>
    </EnquiryProvider>
  );
}
