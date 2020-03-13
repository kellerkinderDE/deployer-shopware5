FROM php:7.2-stretch

ADD https://getcomposer.org/composer-stable.phar /usr/local/bin/composer

RUN apt-get -qq update && apt-get -qq install -y git zip mysql-client rsync acl \
    && docker-php-ext-install pdo_mysql \
    && chmod +x /usr/local/bin/composer \
    && composer global require hirak/prestissimo \
    && composer global clearcache \
    && rm -rf /var/lib/apt/lists/*
