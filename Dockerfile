FROM php:8.5-apache

RUN docker-php-ext-install mysqli \
    && a2enmod rewrite

COPY docker/php/galaxy.ini /usr/local/etc/php/conf.d/galaxy.ini

RUN sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p cache log \
    && chown -R www-data:www-data cache log
