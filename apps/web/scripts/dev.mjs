import { spawn } from "node:child_process";
import { createRequire } from "node:module";
import { createServer } from "node:net";

const PORT_RANGE_START = 4400;
const PORT_RANGE_END = 4499;
const require = createRequire(import.meta.url);

function getExplicitPort(args) {
  if (process.env.PORT) {
    return Number.parseInt(process.env.PORT, 10);
  }

  for (let index = 0; index < args.length; index += 1) {
    const arg = args[index];

    if ((arg === "--port" || arg === "-p") && args[index + 1]) {
      return Number.parseInt(args[index + 1], 10);
    }

    if (arg.startsWith("--port=")) {
      return Number.parseInt(arg.slice("--port=".length), 10);
    }

    if (arg.startsWith("-p=")) {
      return Number.parseInt(arg.slice("-p=".length), 10);
    }
  }

  return null;
}

function canListen(port) {
  return new Promise((resolve) => {
    const server = createServer();

    server.once("error", (error) => {
      if (error.code === "EACCES" || error.code === "EADDRINUSE") {
        resolve(false);
        return;
      }

      resolve(false);
    });

    server.listen(port, () => {
      server.close(() => resolve(true));
    });
  });
}

async function findOpenPort() {
  for (let port = PORT_RANGE_START; port <= PORT_RANGE_END; port += 1) {
    // Match Next's default host binding behavior when probing availability.
    if (await canListen(port)) {
      return port;
    }
  }

  throw new Error(
    `No available dev port found. Tried: ${PORT_RANGE_START}-${PORT_RANGE_END}`
  );
}

async function main() {
  const passthroughArgs = process.argv.slice(2);
  const explicitPort = getExplicitPort(passthroughArgs);
  const nextArgs = ["dev", ...passthroughArgs];

  if (!explicitPort) {
    const port = await findOpenPort();
    nextArgs.push("--port", String(port));
    console.log(`Starting @securecy/web on http://localhost:${port}`);
  }

  const nextCli = require.resolve("next/dist/bin/next");
  const child = spawn(process.execPath, [nextCli, ...nextArgs], {
    stdio: "inherit",
    shell: false,
  });

  child.on("exit", (code, signal) => {
    if (signal) {
      process.kill(process.pid, signal);
      return;
    }

    process.exit(code ?? 0);
  });

  child.on("error", (error) => {
    console.error(error);
    process.exit(1);
  });
}

main().catch((error) => {
  console.error(error.message);
  process.exit(1);
});
