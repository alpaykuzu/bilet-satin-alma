#!/bin/sh
# Bu script, container her başladığında çalışacak.

# Veritabanı dosyasının tam yolunu bir değişkene atayalım.
DB_FILE="/var/www/html/veritabani.sqlite"

# Eğer veritabanı dosyası MEVCUT DEĞİLSE...
if [ ! -f "$DB_FILE" ]; then
    echo "Veritabanı dosyası bulunamadı. Otomatik olarak oluşturuluyor..."
    
    # setup_database.php script'ini PHP ile çalıştır.
    php /var/www/html/setup_database.php
    
    # Güvenlik ve sahiplik ayarları (ÖNEMLİ!)
    # Apache'nin veritabanı dosyasına yazabilmesi için dosyanın sahibini www-data kullanıcısı yap.
    chown www-data:www-data "$DB_FILE"
    
    echo "Veritabanı başarıyla oluşturuldu."
else
    echo "Veritabanı dosyası zaten mevcut. Kurulum atlanıyor."
fi

# Bu script'in işi bittikten sonra, container'ın asıl görevini yapmasını sağla.
# Bu satır, Apache sunucusunu başlatır. EĞER BU SATIR OLMAZSA, WEB SUNUCUSU ASLA BAŞLAMAZ!
exec "$@"