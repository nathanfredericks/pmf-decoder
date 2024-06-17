FROM php:8.3-apache

RUN a2enmod rewrite

COPY . /var/www/html

CMD ["apache2-foreground"]