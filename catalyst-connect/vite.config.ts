import fs from "node:fs";
import { defineConfig, type Plugin } from "vite";
import react from "@vitejs/plugin-react-swc";
import path from "path";
import { componentTagger } from "lovable-tagger";

const phpEntryPath = path.resolve(__dirname, "index.php");
const tempHtmlEntryPath = path.resolve(__dirname, "index.html");

const phpFrontendEntry = (): Plugin => {
  let buildOutDir = "";

  const readPhpEntry = () => fs.readFileSync(phpEntryPath, "utf8");

  return {
    name: "php-frontend-entry",
    config() {
      return {
        build: {
          rollupOptions: {
            input: tempHtmlEntryPath,
          },
        },
        esbuild: {
          include: /\.(?:[jt]sx?|php)$/,
          loader: "tsx",
        },
      };
    },
    configResolved(config) {
      buildOutDir = path.resolve(__dirname, config.build.outDir);
    },
    configureServer(server) {
      server.middlewares.use(async (req, res, next) => {
        const requestPath = (req.url ?? "").split("?")[0];

        if (requestPath !== "/" && requestPath !== "/index.php") {
          next();
          return;
        }

        const html = await server.transformIndexHtml(req.url ?? "/", readPhpEntry());
        res.setHeader("Content-Type", "text/html");
        res.end(html);
      });
    },
    handleHotUpdate({ file, server }) {
      if (path.resolve(file) === phpEntryPath) {
        server.ws.send({ type: "full-reload" });
        return [];
      }
    },
    buildStart() {
      fs.writeFileSync(tempHtmlEntryPath, readPhpEntry());
    },
    closeBundle() {
      if (fs.existsSync(tempHtmlEntryPath)) {
        fs.rmSync(tempHtmlEntryPath);
      }

      const builtHtmlEntry = path.join(buildOutDir, "index.html");
      const builtPhpEntry = path.join(buildOutDir, "index.php");

      if (fs.existsSync(builtHtmlEntry)) {
        fs.copyFileSync(builtHtmlEntry, builtPhpEntry);
      }
    },
  };
};

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => ({
  base: '/public/frontend/',
  server: {
    host: "::",
    port: 8080,
    hmr: {
      overlay: false,
    },
  },
  plugins: [
    phpFrontendEntry(),
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
    mode === "development" && componentTagger(),
  ].filter(Boolean),
  resolve: {
    extensions: [".php", ".mjs", ".js", ".mts", ".ts", ".jsx", ".tsx", ".json"],
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
}));
