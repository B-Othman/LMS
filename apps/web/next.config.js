/** @type {import('next').NextConfig} */
const nextConfig = {
  output: "standalone",
  transpilePackages: ["@securecy/ui", "@securecy/config", "@securecy/types"],
};

module.exports = nextConfig;
