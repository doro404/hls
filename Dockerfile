FROM php:7.4-cli

# Instala FFmpeg e dependências básicas
RUN apt-get update && apt-get install -y \
    ffmpeg \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    nginx  # Instala o servidor web Nginx

# Instala extensões PHP necessárias
RUN docker-php-ext-configure zip \
    && docker-php-ext-install zip gd

# Instala o Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define o diretório da aplicação
WORKDIR /app

# Copia os arquivos do projeto para dentro do container
COPY . .

# Instala as dependências do projeto via Composer
RUN composer install --no-dev --prefer-dist

# Configura o Nginx
COPY docker/000-default.conf /etc/nginx/sites-available/default


# Expondo a porta 80 para o Nginx
EXPOSE 80

# Comando para rodar o Nginx (não executar o PHP automaticamente)
CMD service nginx start && tail -f /dev/null
