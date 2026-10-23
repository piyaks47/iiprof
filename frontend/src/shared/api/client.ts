/**
 * Minimal fetch client shared by web and React Native.
 * Call configureApi() once at start-up (web: relative "/api/v1"; native: absolute URL).
 */

export class ApiError extends Error {
  constructor(
    message: string,
    public readonly status: number,
    public readonly body?: unknown,
  ) {
    super(message);
    this.name = 'ApiError';
  }

  get isNotFound(): boolean {
    return this.status === 404;
  }
}

interface ApiConfig {
  baseUrl: string;
  headers?: Record<string, string>;
  timeoutMs?: number;
}

const config: ApiConfig = { baseUrl: '/api/v1', timeoutMs: 15000 };

export function configureApi(options: Partial<ApiConfig>): void {
  Object.assign(config, options);
  config.baseUrl = config.baseUrl.replace(/\/+$/, '');
}

async function request<T>(method: 'GET' | 'POST', path: string, body?: unknown, signal?: AbortSignal): Promise<T> {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), config.timeoutMs);
  signal?.addEventListener('abort', () => controller.abort());

  try {
    const response = await fetch(`${config.baseUrl}${path}`, {
      method,
      headers: {
        Accept: 'application/json',
        ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
        ...config.headers,
      },
      body: body !== undefined ? JSON.stringify(body) : undefined,
      signal: controller.signal,
    });

    const json = await response.json().catch(() => undefined);

    if (!response.ok) {
      const message = (json as { message?: string } | undefined)?.message ?? `Request failed (${response.status})`;
      throw new ApiError(message, response.status, json);
    }

    return json as T;
  } finally {
    clearTimeout(timer);
  }
}

export function apiGet<T>(path: string, init?: { signal?: AbortSignal }): Promise<T> {
  return request<T>('GET', path, undefined, init?.signal);
}

export function apiPost<T>(path: string, body: unknown, init?: { signal?: AbortSignal }): Promise<T> {
  return request<T>('POST', path, body, init?.signal);
}

/** Field errors of a Laravel 422 response: { email: ['…'] } → { email: '…' }. */
export function fieldErrors(error: unknown): Record<string, string> {
  if (!(error instanceof ApiError) || error.status !== 422) return {};
  const errors = (error.body as { errors?: Record<string, string[]> } | undefined)?.errors ?? {};
  return Object.fromEntries(Object.entries(errors).map(([field, messages]) => [field, messages[0] ?? '']));
}
