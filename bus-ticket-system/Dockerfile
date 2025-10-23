# 1. Temel imaj
FROM php:8.2-apache

# 2. Gerekli sistem kütüphanelerini kur
# YENİ: libicu-dev eklendi (intl eklentisi için gerekli)
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libicu-dev \ 
    && rm -rf /var/lib/apt/lists/*

# 3. Gerekli PHP eklentilerini kur
# YENİ: intl eklentisi eklendi
RUN docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install pdo pdo_sqlite gd intl

# 4. Başlangıç script'ini kopyala ve çalıştırılabilir yap
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# 5. Çalışma dizinini ayarla
WORKDIR /var/www/html

# 6. Proje dosyalarını kopyala
COPY . .

# 7. Başlangıç script'ini ana komut yap
ENTRYPOINT ["docker-entrypoint.sh"]

# 8. Apache'yi başlatma komutu
CMD ["apache2-foreground"]