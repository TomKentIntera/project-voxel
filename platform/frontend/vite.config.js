import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

const hmrHost = process.env.VITE_HMR_HOST
const hmrClientPort = Number(process.env.VITE_HMR_CLIENT_PORT ?? 80)

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 5173,
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
