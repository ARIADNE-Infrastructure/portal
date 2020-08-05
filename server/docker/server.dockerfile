FROM php:7.4-apache

LABEL maintainer="SND <team-it@snd.gu.se>"

# Extract the PHP source.
RUN docker-php-source extract

# Install libs.
RUN apt-get update && apt-get install -y \
    wget \
    zip \
    unzip

# Copy the development PHP config from the PHP source.
RUN cp /usr/src/php/php.ini-development /usr/local/etc/php/php.ini

# Delete the PHP source.
RUN docker-php-source delete

# Copy the Apache configuration.
COPY server/docker/apache/default.conf /etc/apache2/sites-available/000-default.conf

# Copy PHP composer vendor folder
# COPY server/vendor/ /var/www/vendor
# COPY server/classes/ /var/www/classes
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

# Enable Apache mods.
RUN a2enmod rewrite

# Set permissions for apache.
RUN chown -R www-data:www-data /var/www/html

# Set working directory.
WORKDIR /var/www/html

# Convenient stuff
RUN echo 'alias ll="ls -la"' >> ~/.bashrc
RUN apt-get install -y vim

# Install xdebug.
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Add xdebug configuration.
COPY /server/docker/xdebug/xdebug.ini /usr/local/etc/php/conf.d/
