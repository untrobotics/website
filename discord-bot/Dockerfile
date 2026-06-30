# UNT Robotics interactive Discord bot (Node). Standalone gateway service — not
# part of the PHP/Apache app image. Maintains one persistent gateway connection,
# so only ONE replica may run (see k8s/base/discord-bot.yaml).
FROM node:20-alpine

WORKDIR /app

# Install production deps only. Copy manifests first for better layer caching.
COPY package.json package-lock.json* ./
RUN npm ci --omit=dev || npm install --omit=dev

# Application source.
COPY src ./src

# Drop root.
USER node

ENV NODE_ENV=production
ENV HEARTBEAT_FILE=/tmp/discord-bot-heartbeat

CMD ["node", "src/index.js"]
