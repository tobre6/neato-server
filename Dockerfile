FROM resin/rpi-raspbian
#FROM debian:jessie

MAINTAINER tobre6

RUN apt-get update
RUN apt-get install -y php5-cli php5-curl curl git

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /usr/src/app
WORKDIR /usr/src/app

RUN composer update

CMD [ "php", "./src/server.php" ]