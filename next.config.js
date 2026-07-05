/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  images: {
    remotePatterns: [
      { protocol: 'https', hostname: 'regularswitch.com' },
      { protocol: 'https', hostname: 'wp.regularswitch.com' },
      { protocol: 'http', hostname: 'regularswitch-wp.local' },
    ],
    // WP local (Local app) resolve para 127.0.0.1 — Next 16 bloqueia por padrão
    ...(process.env.NODE_ENV === 'development' && {
      dangerouslyAllowLocalIP: true,
    }),
  },
}

module.exports = nextConfig
