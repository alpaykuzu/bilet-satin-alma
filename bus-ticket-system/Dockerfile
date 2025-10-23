# 1. Temel imaj
FROM php:8.2-apache

# 2. Gerekli sistem kütüphaneleri
# YENİ: 'dos2unix' eklendi (Windows'tan Linux'a dosya formatı dönüştürücü)
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libpng-dev \
    libjpeg-dev \
    libicu-dev \
    dos2unix \
    && rm -rf /var/lib/apt/lists/*

# 3. Gerekli PHP eklentileri
RUN docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install pdo pdo_sqlite gd intl

# 4. Başlangıç script'ini kopyala
COPY docker-entrypoint.sh /usr/local/bin/

# 5. Script'i hem çalıştırılabilir yap HEM DE Windows formatından Linux formatına dönüştür
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && dos2unix /usr/local/bin/docker-entrypoint.sh

# 6. Çalışma dizini
WORKDIR /var/www/html

# 7. Proje dosyaları
COPY . .

# 8. Başlangıç script'i
ENTRYPOINT ["docker-entrypoint.sh"]

# 9. Apache komutu
CMD ["apache2-foreground"]