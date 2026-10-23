import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { fileURLToPath, URL } from 'node:url';

/**
 * Production build is served from public_html/frontend, so assets live under /frontend/.
 * The SPA itself answers at "/" thanks to the root .htaccess.
 */
export default defineConfig(({ command, mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const backend = env.VITE_DEV_BACKEND || 'http://127.0.0.1:8000';

  return {
    base: command === 'build' ? '/frontend/' : '/',
    plugins: [react(), tailwindcss()],
    resolve: {
      alias: {
        '@shared': fileURLToPath(new URL('./src/shared', import.meta.url)),
        '@web': fileURLToPath(new URL('./src/web', import.meta.url)),
      },
    },
    server: {
      port: 5173,
      // In development, forward API, uploaded files and bundled media to `php artisan serve`.
      proxy: {
        '/api': backend,
        '/storage': backend,
        '/media': backend,
      },
    },
    build: {
      outDir: 'dist',
      emptyOutDir: true,
      sourcemap: false,
    },
  };
});
