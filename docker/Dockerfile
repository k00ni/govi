FROM php:8.2-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        curl git gnupg libicu-dev libpng-dev libzip-dev nano net-tools sudo unzip wget zlib1g-dev

RUN docker-php-ext-install -j$(nproc) intl zip \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) gd

COPY ./default.conf /etc/apache2/sites-enabled/000-default.conf
RUN a2enmod rewrite

RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

WORKDIR /var/www/html

COPY ./run.sh /run.sh
RUN chmod +x /run.sh

EXPOSE 80

CMD ["/run.sh"]
