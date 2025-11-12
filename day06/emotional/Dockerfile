FROM node:lts

WORKDIR /emoji_profile

RUN useradd appuser && chown -R appuser /emoji_profile

USER appuser

COPY package*.json ./

USER root
RUN npm install

USER appuser

COPY server.js .
COPY flag.txt .
COPY views views/
COPY public public/

EXPOSE 3000

CMD ["node", "server.js"]
