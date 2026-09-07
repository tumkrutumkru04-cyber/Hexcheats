FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && a2dismod mpm_event mpm_worker mpm_prefork 2>/dev/null || true \
    && a2enmod mpm_prefork rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod 750 /var/www/html/data

EXPOSE 8080

CMD ["sh", "-c", "set -eu; PORT=\"${PORT:-8080}\"; for f in /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf; do [ -e \"$f\" ] || continue; case \"$f\" in *mpm_prefork*) ;; *) rm -f \"$f\" ;; esac; done; a2enmod mpm_prefork >/dev/null; sed -ri \"s/^Listen [0-9]+/Listen ${PORT}/\" /etc/apache2/ports.conf; sed -ri \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/\" /etc/apache2/sites-enabled/000-default.conf; apachectl -t; exec apache2-foreground"]
