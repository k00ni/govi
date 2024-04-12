FROM php:8.3-cli

# install and setup required system library
RUN apt-get update && apt-get install -y --no-install-recommends \
        curl git gnupg libicu-dev libpng-dev libzip-dev nano net-tools raptor2-utils sudo unzip wget zlib1g-dev
RUN docker-php-ext-install -j$(nproc) intl zip

# install Composer globally (required to install PHP vendors)
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# add a non-root user to limit user rights
RUN useradd -r --home /home/govi -u 1000 govi
RUN usermod -a -G www-data govi
RUN mkdir /home/govi
RUN chown govi:www-data /home/govi
RUN adduser govi sudo

# add custom PHP.ini settings
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
COPY ./custom.ini /usr/local/etc/php/conf.d/custom.ini

# create and mark working dir
RUN mkdir /govi
WORKDIR /govi

# run this after container started to keep it alive
CMD ["tail -f /dev/null"]
