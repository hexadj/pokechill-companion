FROM node:22-alpine AS frontend-builder

WORKDIR /app

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci

COPY frontend/ ./
RUN npm run build

FROM svenstaro/miniserve:latest

COPY --from=frontend-builder /app/dist /srv

EXPOSE 8080

CMD ["miniserve", "/srv", "-p", "8080", "--spa"]
