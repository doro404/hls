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
    libfreetype6-dev

# Instala extensões PHP necessárias
RUN docker-php-ext-configure zip \
    && docker-php-ext-install zip gd

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define diretório da aplicação
WORKDIR /app

# Copia os arquivos do projeto para dentro do container
COPY . .

# Instala dependências do projeto
RUN composer install

# Comando padrão para rodar o app
CMD ["php", "index.php"]
