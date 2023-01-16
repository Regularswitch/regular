/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  swcMinify: true,
  images: {
    domains: ['regularswitch.com']
  },
  
}

module.exports = nextConfig
