# Botathon driver WebSocket relay (Node). Standalone service — not part of the
# PHP/Apache app image. Relays controller input to per-team ESP32 robots on :81.
FROM node:20-alpine

WORKDIR /app

# package-lock.json is present but there is no committed package.json lockfile
# match, so install the single declared dependency from package.json.
COPY package.json package-lock.json* ./
RUN npm install --omit=dev

COPY server.js legacy-server.js ./

# server.js = queue + ^seq ACK variant. Override to legacy-server.js (the
# prod-proven fire-and-forget variant) by setting command in compose/K8s.
EXPOSE 81
CMD ["node", "server.js"]
