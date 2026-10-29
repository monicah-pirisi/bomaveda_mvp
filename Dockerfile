FROM php:8.5-apache
RUN docker-php-ext-install pdo_mysql \
 && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && a2disconf other-vhosts-access-log \
 && sed -i 's#CustomLog .*#CustomLog /dev/null combined#; s#/var/www/html#/var/www/html/public#' /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-bomaveda.ini
COPY docker/apache.conf /etc/apache2/conf-available/bomaveda.conf
RUN a2enconf bomaveda
COPY app /var/www/html/app
COPY public /var/www/html/public
COPY bin /var/www/html/bin
COPY tests /var/www/html/tests
