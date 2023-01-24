FROM php:8.1-apache

LABEL maintainer="SND <team-it@snd.gu.se>"

# Extract the PHP source.
RUN docker-php-source extract

# Install libs.
RUN apt-get update && apt-get install -y \
    wget \
    zip \
    unzip

# Install PHP extensions.
RUN docker-php-ext-install opcache

# Copy opcache config.
COPY /server/docker/opcache/opcache.ini /usr/local/etc/php/conf.d/

# Setup sendmail path to php
# (https://r.je/sendmail-php-docker)  - See example for setup
# RUN echo "sendmail_path=/usr/sbin/sendmail -t -i" >> /usr/local/etc/php/conf.d/sendmail.ini

# Sendmail - Set restart command to docker-php-entrypoint
# RUN sed -i '/#!\/bin\/sh/aservice sendmail restart' /usr/local/bin/docker-php-entrypoint

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
RUN a2enmod deflate

# Set permissions for apache.
RUN chown -R www-data:www-data /var/www/html

# Set working directory.
WORKDIR /var/www/html

# Copy web content to container webroot
COPY  ./server/html/ /var/www/html

# Copy PHP backend
COPY  ./server/classes/ /var/www/classes

# PHP Composer stuff
COPY ./server/composer.json /var/www/
COPY ./server/composer.lock /var/www/
RUN composer install -d /var/www/ --no-scripts;


# Convenient stuff
RUN echo 'alias ll="ls -la"' >> ~/.bashrc
RUN apt-get install -y vim

# Install xdebug. - Only for dev env.
#RUN pecl install xdebug \
#    && docker-php-ext-enable xdebug

# Add xdebug configuration.
#COPY /server/docker/xdebug/xdebug.ini /usr/local/etc/php/conf.d/
