# Dev-oriented image: runs the Vite dev server with HMR, matching local `npm run dev`.
# For a production deployment, build a static bundle (`npm run build`) and serve
# the `dist/` folder from a static host or a separate nginx image instead.
FROM node:20-alpine

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
