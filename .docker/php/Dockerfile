FROM php:7.2.3-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends vim curl debconf subversion git apt-transport-https apt-utils \
    build-essential locales acl mailutils zip unzip nmap\
    gnupg gnupg1 gnupg2

RUN docker-php-ext-install pdo pdo_mysql

COPY php.ini /etc/php/7.2.3/php.ini
COPY php-fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf

RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
   mv composer.phar /usr/local/bin/composer

RUN groupadd dev -g 999
RUN useradd dev -g dev -d /home/dev -m

RUN rm -rf /var/lib/apt/lists/*
RUN echo "de_DE.UTF-8 UTF-8" > /etc/locale.gen && \
    locale-gen

WORKDIR /var/www/lms_server/

EXPOSE 9000

CMD ["php-fpm"]