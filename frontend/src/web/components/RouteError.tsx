import { ErrorState } from './States';

export function RouteError() {
  return <ErrorState onRetry={() => window.location.reload()} />;
}
