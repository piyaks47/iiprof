import { createBrowserRouter } from 'react-router';
import { Layout } from './components/Layout';
import { RouteError } from './components/RouteError';
import { PageSkeleton } from './components/States';
import { ContentPage } from './pages/ContentPage';
import { EventPage } from './pages/EventPage';
import { EventsPage } from './pages/EventsPage';
import { HomePage } from './pages/HomePage';
import { LegacyEvent, LegacyLogin, LegacyRegister } from './pages/Legacy';

/** Public site routes. Anything not matched by Laravel (see root .htaccess) lands here. */
export const router = createBrowserRouter([
  {
    element: <Layout />,
    errorElement: <RouteError />,
    // Shown while the code of a page opened directly (not via a link) is still loading.
    hydrateFallbackElement: <PageSkeleton />,
    children: [
      { index: true, element: <HomePage /> },
      { path: 'events', element: <EventsPage /> },
      { path: 'view-events', element: <EventsPage /> },
      { path: 'events/:slug', element: <EventPage /> },
      // Pages opened less often load their code when first visited (smaller first download).
      { path: 'view-gallery', lazy: async () => ({ Component: (await import('./pages/GalleryPage')).GalleryPage }) },
      { path: 'training', lazy: async () => ({ Component: (await import('./pages/TrainingPage')).TrainingPage }) },
      { path: 'training/:slug', lazy: async () => ({ Component: (await import('./pages/TrainingProgrammePage')).TrainingProgrammePage }) },
      { path: 'news', lazy: async () => ({ Component: (await import('./pages/NewsPage')).NewsPage }) },
      { path: 'news/:slug', lazy: async () => ({ Component: (await import('./pages/NewsPostPage')).NewsPostPage }) },
      { path: 'seminars/:slug', lazy: async () => ({ Component: (await import('./pages/SeminarPage')).SeminarPage }) },
      // Addresses of the previous iiprof.com site.
      { path: 'event/:id/:slug', element: <LegacyEvent /> },
      { path: 'member/login', element: <LegacyLogin /> },
      { path: 'member/registration', element: <LegacyRegister /> },
      // Pages (/about-us), management (/management/director-council) and menu categories (/industry/banks-nbfc).
      { path: '*', element: <ContentPage /> },
    ],
  },
]);
