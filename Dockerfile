# Imagen oficial de PHP 8.2 con servidor web Apache
FROM php:8.2-apache

# Instalar extensiones de MySQL (mysqli y pdo_mysql) y utilidades necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && docker-php-ext-enable mysqli pdo_mysql

# Habilitar mod_rewrite de Apache (para soporte de rutas y .htaccess)
RUN a2enmod rewrite headers

# Copiar todo el código del backend al directorio público de Apache
COPY . /var/www/html/

# Ajustar permisos para Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Render asigna un puerto dinámico mediante la variable de entorno $PORT (por defecto 10000).
# Este comando adapta Apache automáticamente al puerto que Render le asigne al iniciar.
CMD ["sh", "-c", "sed -i \"s/80/${PORT:-80}/g\" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && apache2-foreground"]