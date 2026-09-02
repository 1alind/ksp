import http from "http";

const server = http.createServer((_req, res) => {
  res.writeHead(200, { "Content-Type": "text/plain; charset=utf-8" });
  res.end("PHP Application Workspace - All source code located in /website");
});

server.listen(3000, "0.0.0.0");
