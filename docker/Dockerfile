FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        curl git gnupg libicu-dev libpng-dev libzip-dev nano net-tools sudo unzip wget zlib1g-dev

RUN docker-php-ext-install -j$(nproc) intl zip

RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

RUN useradd -r --home /home/govi -u 1000 govi
RUN usermod -a -G www-data govi
RUN mkdir /home/govi
RUN chown govi:www-data /home/govi
RUN adduser govi sudo

RUN mkdir /govi
WORKDIR /govi

CMD ["tail -f /dev/null"]
