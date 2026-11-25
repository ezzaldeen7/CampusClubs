FROM php:8.2-apache
RUN docker-php-ext-install pdo pdo_mysql \
    && rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork rewrite
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html/images
CMD ["sh", "-c", "sed -i \"s/80/${PORT:-80}/g\" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && apache2-foreground"]