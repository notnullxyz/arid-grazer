FROM php:fpm

WORKDIR /code

RUN apt-get update && apt-get install -y git \
	ack-grep \
	zlib1g-dev \
	vim \
	&& apt-get clean all

RUN docker-php-ext-install zip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer global require "laravel/lumen-installer"

# Install app dependencies
RUN composer install --no-interaction 

ENV PATH ~/.composer/vendor/bin:$PATH