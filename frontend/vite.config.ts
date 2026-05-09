import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
  const envDir = path.resolve(import.meta.dirname, '../.envs');
  const env = loadEnv(mode, envDir, '');

  const isProduction = mode === 'production';
  const isDocker = env['APP_RUNNING_IN_CONTAINER'] === 'true';
  const host = isProduction || isDocker ? '0.0.0.0' : 'localhost';

  // Obtener la URL de la aplicación desde las variables de entorno para HMR
  const appUrl = env['VITE_APP_URL'] ?? 'http://localhost:8080';
  // Extraer el hostname de la URL para usarlo en la configuración de HMR
  const appHostname = new URL(appUrl).hostname;

  return {
    envDir,
    server: {
      host,
      port: 5173,
      hmr: {
        // En test-producción o cuando Vite sirve para Docker/LAN, usar el hostname de APP_URL
        host: isProduction || isDocker ? appHostname : 'localhost',
      },
      watch: {
        usePolling: true,
      },
    },
    preview: {
      host,
      port: 5173,
    },
    build: {
      emptyOutDir: true,
      chunkSizeWarningLimit: 1024,
      rollupOptions: {
        output: {
          manualChunks: {
            'react-vendor': ['react', 'react-dom', 'react-day-picker'],
            'ui-vendor': [
              '@radix-ui/react-accordion',
              '@radix-ui/react-alert-dialog',
              '@radix-ui/react-avatar',
              '@radix-ui/react-checkbox',
              '@radix-ui/react-collapsible',
              '@radix-ui/react-dialog',
              '@radix-ui/react-dropdown-menu',
              '@radix-ui/react-icons',
              '@radix-ui/react-label',
              '@radix-ui/react-navigation-menu',
              '@radix-ui/react-popover',
              '@radix-ui/react-progress',
              '@radix-ui/react-radio-group',
              '@radix-ui/react-scroll-area',
              '@radix-ui/react-select',
              '@radix-ui/react-separator',
              '@radix-ui/react-slot',
              '@radix-ui/react-switch',
              '@radix-ui/react-tabs',
              '@radix-ui/react-toast',
              '@radix-ui/react-toggle',
              '@radix-ui/react-toggle-group',
              '@radix-ui/react-tooltip',
              'lucide-react',
              'sonner',
              'class-variance-authority',
              'clsx',
              'tailwind-merge',
            ],
            'tanstack-vendor': ['@tanstack/react-form', '@tanstack/react-query', '@tanstack/react-table'],
            'inertia-vendor': ['@inertiajs/react', '@inertiajs/core', 'axios'],
            'motion-vendor': ['motion', 'tailwindcss-animate'],
          },
        },
      },
    },
    plugins: [
      laravel({
        input: 'src/app.tsx',
        publicDirectory: '../backend/public',
        refresh: true,
      }),
      react(),
      tailwindcss(),
    ],
    resolve: {
      alias: {
        '@': path.resolve(import.meta.dirname, 'src'),
        '/fonts': path.resolve(import.meta.dirname, '../backend/public/fonts'),
      },
      extensions: ['.tsx', '.ts', '.js', '.json'],
    },
  };
});
