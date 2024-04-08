FROM php:7.4-cli
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
COPY . /usr/src/crawler
WORKDIR /usr/src/crawler
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN apt update
RUN apt install -y \
         libzip-dev \
         && docker-php-ext-install zip
RUN composer update
CMD [ "php", "./run.php"]