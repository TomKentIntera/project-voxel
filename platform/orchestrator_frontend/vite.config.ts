import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { resolve } from 'path'

const hmrHost = process.env.VITE_HMR_HOST
const hmrClientPort = Number(process.env.VITE_HMR_CLIENT_PORT ?? 80)

export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5174,
    strictPort: true,
    allowedHosts: ['.localhost'],
    ...(hmrHost
      ? {
          hmr: {
            host: hmrHost,
            clientPort: hmrClientPort,
            protocol: 'ws',
          },
        }
      : {}),
  },
})

