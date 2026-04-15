import { defineConfig } from "vitest/config";
import react from "@vitejs/plugin-react-swc";
import path from "path";

export default defineConfig({
  plugins: [
    react({
      parserConfig: (id) => {
        if (id.endsWith(".php") || id.endsWith(".tsx")) {
          return {
            syntax: "typescript",
            tsx: true,
          };
        }

        if (id.endsWith(".ts") || id.endsWith(".mts")) {
          return {
            syntax: "typescript",
            tsx: false,
          };
        }

        if (id.endsWith(".jsx") || id.endsWith(".js")) {
          return {
            syntax: "ecmascript",
            jsx: true,
          };
        }
      },
      disableOxcRecommendation: true,
    }),
  ],
  esbuild: {
    include: /\.(?:[jt]sx?|php)$/,
    loader: "tsx",
  },
  test: {
    environment: "jsdom",
    globals: true,
    setupFiles: ["./src/test/setup.ts"],
    include: ["src/**/*.{test,spec}.{ts,tsx}"],
  },
  resolve: {
    alias: { "@": path.resolve(__dirname, "./src") },
    extensions: [".php", ".mjs", ".js", ".mts", ".ts", ".jsx", ".tsx", ".json"],
  },
});
