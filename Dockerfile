FROM php:7.4-apache

# Copia os arquivos do projeto para o diretório padrão do Apache
COPY . /var/www/html/

# Configura permissões
RUN chown -R www-data:www-data /var/www/html

# Instala dependências básicas e bibliotecas necessárias
RUN apt-get update && apt-get install -y \
    ffmpeg \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev

# Instala extensões PHP
RUN docker-php-ext-configure zip \
    && docker-php-ext-install zip gd

# Instala o Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define o diretório da aplicação
WORKDIR /var/www/html

# Instala dependências PHP via Composer
RUN composer install --no-dev --prefer-dist

# Substitui o arquivo de configuração do Apache (000-default.conf)
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Ativa reescrita de URLs, se necessário
RUN a2enmod rewrite

# Expondo a porta padrão do Apache
EXPOSE 80

# Inicia o Apache em modo foreground
CMD ["apache2-foreground"]
