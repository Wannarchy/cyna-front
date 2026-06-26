FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    unzip \
    && docker-php-ext-install curl opcache \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN echo "opcache.enable=1\n\
opcache.memory_consumption=64\n\
opcache.max_accelerated_files=5000\n\
opcache.validate_timestamps=0" > /usr/local/etc/php/conf.d/opcache.ini

COPY docker/php-session.ini /usr/local/etc/php/conf.d/99-session.ini
COPY docker/php-upload.ini /usr/local/etc/php/conf.d/99-upload.ini

ENV APACHE_DOCUMENT_ROOT=/var/www/html

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

COPY . .

RUN mkdir -p storage/sessions \
    && chown -R www-data:www-data storage \
    && chmod -R 775 storage

COPY docker/render-entrypoint.sh /usr/local/bin/render-entrypoint.sh
RUN chmod +x /usr/local/bin/render-entrypoint.sh

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/render-entrypoint.sh"]
