/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  images: {
    remotePatterns: [
      { protocol: 'https', hostname: 'regularswitch.com' },
      { protocol: 'https', hostname: 'wp.regularswitch.com' },
    ],
  },
  
}

module.exports = nextConfig
