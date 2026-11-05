# Packe on Render: PHP 8.3 + Apache
FROM php:8.3-apache

RUN docker-php-ext-install mysqli \
 && a2enmod rewrite headers \
 && sed -ri 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

COPY docker/php.ini /usr/local/etc/php/conf.d/packe.ini
COPY docker/start.sh /usr/local/bin/packe-start
COPY . /var/www/html/

RUN chmod +x /usr/local/bin/packe-start \
 && mkdir -p /var/www/html/images/products /var/www/html/images/designs /var/www/html/error \
 && chown -R www-data:www-data /var/www/html/images /var/www/html/error

CMD ["packe-start"]
