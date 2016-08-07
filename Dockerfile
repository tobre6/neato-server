FROM php:7.0-cli

COPY . /usr/src/app
WORKDIR /usr/src/app

CMD [ "php", "./src/server.php" ]