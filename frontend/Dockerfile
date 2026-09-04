# syntax=docker/dockerfile:1

###############################################################################
# Frontend - Vue 3 + Vite SPA
#
# Stage 1 builds the static bundle with Node 22 (npm ci, deterministic via
# package-lock.json). VITE_* variables are inlined into the bundle at BUILD
# time, so they are passed as build args.
# Stage 2 serves the bundle with nginx:
#   - SPA history-mode fallback (vue-router createWebHistory)
#   - optional same-origin proxy for /api and /storage to the backend service
###############################################################################

# ---- Stage 1: build ----------------------------------------------------------
FROM node:22-alpine AS build

WORKDIR /app

ARG VITE_API_URL=http://localhost:8000/api
ARG VITE_CLOUDINARY_CLOUD_NAME=dqhf9ljat
ARG VITE_CLOUDINARY_PRODUCT_PRESET=product_preset
ARG VITE_CLOUDINARY_CATEGORY_PRESET=category_preset
ARG VITE_CLOUDINARY_PROFILE_PRESET=profile_preset
ENV VITE_API_URL=$VITE_API_URL \
    VITE_CLOUDINARY_CLOUD_NAME=$VITE_CLOUDINARY_CLOUD_NAME \
    VITE_CLOUDINARY_PRODUCT_PRESET=$VITE_CLOUDINARY_PRODUCT_PRESET \
    VITE_CLOUDINARY_CATEGORY_PRESET=$VITE_CLOUDINARY_CATEGORY_PRESET \
    VITE_CLOUDINARY_PROFILE_PRESET=$VITE_CLOUDINARY_PROFILE_PRESET

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .
RUN npm run build

# ---- Stage 2: serve ------------------------------------------------------------
FROM nginx:1.27-alpine

COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD wget -q --spider http://127.0.0.1/ || exit 1
