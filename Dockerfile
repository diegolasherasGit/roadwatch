FROM php:8.3-apache

# Extensión para hablar con MySQL desde PDO + mod_rewrite de Apache
RUN docker-php-ext-install pdo_mysql && a2enmod rewrite

# Apache sirve desde /public (así src/ y db/ no son accesibles por URL)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf
