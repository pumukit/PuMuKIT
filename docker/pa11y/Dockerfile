FROM codeception/codeceptjs:latest

RUN mkdir /app
WORKDIR /app

COPY pa11y.json /app/pay11y.json

RUN npm install pa11y && \
    ln -s /app/node_modules/.bin/pa11y /bin/pa11y

ENTRYPOINT ["pa11y", "-c","/app/pay11y.json"]