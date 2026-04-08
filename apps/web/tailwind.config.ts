import type { Config } from "tailwindcss";
import sharedConfig from "@securecy/config/tailwind";

const config: Config = {
  content: [
    "./src/**/*.{ts,tsx}",
    "../../packages/ui/src/**/*.{ts,tsx}",
  ],
  presets: [sharedConfig as Config],
};

export default config;
