# Utiliser une image PHP avec Apache
FROM php:7.4-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install mysqli

# Copier le code source de l'application
COPY app/ /var/www/html/

# Exposer le port 80
EXPOSE 80
